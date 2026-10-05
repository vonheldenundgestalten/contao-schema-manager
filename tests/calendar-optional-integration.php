<?php
declare(strict_types=1);
require getcwd().'/vendor/autoload.php';
$kernel=Contao\ManagerBundle\HttpKernel\ContaoKernel::fromInput(getcwd(),new Symfony\Component\Console\Input\ArgvInput());
if(getenv('SCHEMA_TEST_DB_TCP')==='1'){
 foreach(['_SERVER','_ENV'] as $scope)if(isset($GLOBALS[$scope]['DATABASE_URL']))$GLOBALS[$scope]['DATABASE_URL']=str_replace('@localhost','@127.0.0.1',$GLOBALS[$scope]['DATABASE_URL']);
 if(isset($_SERVER['DATABASE_URL']))putenv('DATABASE_URL='.$_SERVER['DATABASE_URL']);
}
$kernel->boot();$c=$kernel->getContainer();$c->get('contao.framework')->initialize();$db=$c->get('database_connection');

if(class_exists(Contao\CalendarEventsModel::class))throw new RuntimeException('Run this regression with Calendar uninstalled');
$check=static function(bool $ok,string $why):void{if(!$ok)throw new RuntimeException($why);};
$user=Contao\BackendUser::getInstance();$user->id=1;$user->username='read-only-calendar-check';$user->admin='1';
$security=new class implements Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface { public function isGranted(mixed $attribute,mixed $subject=null):bool{return true;} };
$content=new VHUG\SchemaManagerBundle\Backend\ContentMapSource($db,$c->get('contao.routing.content_url_generator'),$security);
$map=$content->load($user);$check($map['events']===[],'Uninstalled calendar must not appear in graph sources');
$inventory=new VHUG\SchemaManagerBundle\Ai\SiteInventory($db,$content);$roots=$inventory->roots();$root=(int)array_key_first($roots);$data=$inventory->collect($root,$user,true);
foreach(array_keys($data['records']) as $key)$check(!str_starts_with($key,'event:'),'Retained event not scanned');
// Keep the review run entirely in memory; the installed database is read-only.
$memory=Doctrine\DBAL\DriverManager::getConnection(['driver'=>'pdo_sqlite','memory'=>true]);
$memory->executeStatement('CREATE TABLE tl_schema_ai_run (id INTEGER PRIMARY KEY AUTOINCREMENT,tstamp INTEGER,owner INTEGER,root INTEGER,data TEXT)');
$store=new VHUG\SchemaManagerBundle\Ai\RunStore($memory);
$planner=new VHUG\SchemaManagerBundle\Ai\SetupPlanner($db,$inventory,$store);$id=$planner->prepare($user,$root,true);$run=$store->get($id,1);
foreach(array_keys($run['inventory']['records']) as $key)$check(!str_starts_with($key,'calendar:'),'Retained calendar must not appear in parent setup');
$policy=new VHUG\SchemaManagerBundle\Ai\FieldPolicy(new VHUG\SchemaManagerBundle\EventListener\BusinessDetailsListener($db),new VHUG\SchemaManagerBundle\EventListener\DataContainerListener($db),new VHUG\SchemaManagerBundle\EventListener\EntityDetailsListener($db));
$engine=new VHUG\SchemaManagerBundle\Ai\ProposalEngine($db,$policy,$c->get('contao.cache.tag_manager'));
$resolve=new ReflectionMethod($engine,'resolve');
foreach(['event:1','calendar:1'] as $key){try{$resolve->invoke($engine,$key,['configuration'=>true,'inventory'=>['records'=>[$key=>['id'=>1]]]]);throw new LogicException('Stale calendar target accepted');}catch(InvalidArgumentException $e){$check(str_contains($e->getMessage(),'no longer installed'),'Clear stale-target message');}}
Contao\Controller::loadDataContainer('tl_calendar');Contao\Controller::loadDataContainer('tl_calendar_events');
foreach(['tl_calendar','tl_calendar_events'] as $table)$check(empty($GLOBALS['TL_DCA'][$table]['fields']['schemaLocationMode']),'Absent Calendar DCA must not gain schema fields');
$entities=new VHUG\SchemaManagerBundle\Schema\EntityGraph($db,$c->get('contao.routing.content_url_generator'),$c->get('contao.cache.tag_manager'),new VHUG\SchemaManagerBundle\Schema\EntityMapper(),$c->get('request_stack'));
$manager=new Contao\CoreBundle\Routing\ResponseContext\JsonLd\JsonLdManager(new Contao\CoreBundle\Routing\ResponseContext\ResponseContext());$emitted=[];
$calendar=new VHUG\SchemaManagerBundle\Schema\CalendarEventGraph($db,$entities,$c->get('contao.routing.content_url_generator'),$c->get('contao.cache.tag_manager'));
$check($calendar->apply([['id'=>1,'key'=>'unused']],'en',$manager,$emitted)===[],'Event enrichment silently skips absent Calendar');
$place=(int)$db->fetchOne("SELECT id FROM tl_schema_entity WHERE entityType='Place' AND published='1' LIMIT 1");
if($place)$check(($entities->emit($place,'en',$manager,$emitted)['@type']??'')==='Place','Standalone Place works without Calendar');
$node=(new VHUG\SchemaManagerBundle\Schema\EntityMapper())->map(['entityType'=>'Event','entityId'=>'https://example.test/#event','name'=>'Standalone','eventStatus'=>'EventScheduled','locationName'=>'Hall'],null,null);
$check($node['location']['@type']==='Place','Standalone Event remains usable');
echo "PASS: absent Calendar ignored by DCA, graph, inventory and parent setup; stale targets rejected; standalone Place/Event work. No installed-database writes or AI requests.\n";
