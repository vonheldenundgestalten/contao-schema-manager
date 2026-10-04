<?php
declare(strict_types=1);
// Run from the installed Contao application's root; all fixture writes roll back.
require getcwd().'/vendor/autoload.php';
$kernel = Contao\ManagerBundle\HttpKernel\ContaoKernel::fromInput(getcwd(), new Symfony\Component\Console\Input\ArgvInput());
if (getenv('SCHEMA_TEST_DB_TCP') === '1') {
    foreach (['_SERVER', '_ENV'] as $scope) {
        if (isset($GLOBALS[$scope]['DATABASE_URL'])) {
            $GLOBALS[$scope]['DATABASE_URL'] = str_replace('@localhost', '@127.0.0.1', $GLOBALS[$scope]['DATABASE_URL']);
        }
    }
    if (isset($_SERVER['DATABASE_URL'])) { putenv('DATABASE_URL='.$_SERVER['DATABASE_URL']); }
}
$kernel->boot();
$c = $kernel->getContainer();
$c->get('contao.framework')->initialize();
$db = $c->get('database_connection');

$check=static function(bool $ok,string $message):void{if(!$ok)throw new RuntimeException($message);};
$entities=new VHUG\SchemaManagerBundle\Schema\EntityGraph($db,$c->get('contao.routing.content_url_generator'),$c->get('contao.cache.tag_manager'),new VHUG\SchemaManagerBundle\Schema\EntityMapper(),$c->get('request_stack'));
$events=new VHUG\SchemaManagerBundle\Schema\CalendarEventGraph($db,$entities,$c->get('contao.routing.content_url_generator'),$c->get('contao.cache.tag_manager'));
$db->beginTransaction();
try{
 $record=$db->fetchAssociative("SELECT * FROM tl_calendar_events WHERE alias='schema-test-workshop-en'");$check((bool)$record,'Run against installed dev event fixtures');$id=(int)$record['id'];$calendar=(int)$record['pid'];$key='#/schema/events/'.$id;
 $render=function(bool $hasCore=true)use($events,$id,$key){$context=new Contao\CoreBundle\Routing\ResponseContext\ResponseContext();$manager=new Contao\CoreBundle\Routing\ResponseContext\JsonLd\JsonLdManager($context);$graph=$manager->getGraphForSchema($manager::SCHEMA_ORG);if($hasCore)$graph->set((new Spatie\SchemaOrg\Event())->setProperty('@id',$key)->setProperty('name','Core name')->setProperty('startDate','2026-11-10T10:00:00+01:00')->setProperty('endDate','2026-11-10T16:00:00+01:00')->setProperty('description','Core description')->setProperty('image','https://example.org/image.jpg'),$key);$graph->set((new Spatie\SchemaOrg\Event())->setProperty('@id','unrelated'),'unrelated');$emitted=[];$subjects=$events->apply([['id'=>$id,'key'=>$key]],'en',$manager,$emitted);return [$graph->toArray()['@graph'],$subjects];};
 [$nodes,$subjects]=$render();$node=array_values(array_filter($nodes,fn($node)=>($node['@id']??'')===$record['schemaIdentity']))[0];
 $check($node['name']==='Core name'&&$node['description']==='Core description'&&$node['startDate']==='2026-11-10T10:00:00+01:00'&&$node['image']==='https://example.org/image.jpg','Core facts preserved');
 $check(!empty($node['organizer']['@id'])&&$subjects===[['@id'=>$record['schemaIdentity']]],'Calendar organizer inheritance and reader subject');
 $check(count(array_filter($nodes,fn($node)=>($node['@type']??'')==='Event'))===2,'Only exact core event replaced');

 foreach(['tl_calendar','tl_calendar_events'] as $table){$check(!in_array('schemaLatitude',VHUG\SchemaManagerBundle\Ai\FieldPolicy::fields($table,''),true)&&!in_array('schemaLongitude',VHUG\SchemaManagerBundle\Ai\FieldPolicy::fields($table,''),true),'Coordinates excluded from AI policy');}
 $db->update('tl_calendar',['schemaLatitude'=>'48.7758','schemaLongitude'=>'9.1829'],['id'=>$calendar]);
 $db->update('tl_calendar_events',['schemaLatitude'=>'','schemaLongitude'=>''],['id'=>$id]);
 $eventNode=function()use($render,$record){[$nodes]=$render();return array_values(array_filter($nodes,fn($n)=>($n['@id']??'')===$record['schemaIdentity']))[0];};
 $check($eventNode()['location']['geo']['latitude']===48.7758,'Calendar coordinate pair inherited');
 $db->update('tl_calendar_events',['schemaLatitude'=>'0','schemaLongitude'=>'0'],['id'=>$id]);
 $check($eventNode()['location']['geo']['latitude']===0.0&&$eventNode()['location']['geo']['longitude']===0.0,'Zero coordinates override defaults');
 $db->update('tl_calendar_events',['schemaLongitude'=>''],['id'=>$id]);
 $check(!isset($eventNode()['location']['geo']),'Partial event coordinates never mix with calendar defaults');
 $db->update('tl_calendar_events',['schemaLatitude'=>''],['id'=>$id]);
 [$nodes,$subjects]=$render(false);$check(count($nodes)===1&&!$subjects,'No event is fabricated on teaser lists');
 $db->update('tl_calendar',['schemaMode'=>'suppress'],['id'=>$calendar]);[$nodes,$subjects]=$render();$check(count($nodes)===1&&!$subjects,'Calendar suppression preserves unrelated events');
 $db->update('tl_calendar',['schemaMode'=>'enrich'],['id'=>$calendar]);
 $db->update('tl_calendar_events',['schemaAttendanceMode'=>'OnlineEventAttendanceMode','schemaEventUrl'=>'https://example.org/online','schemaEventStatus'=>'EventCancelled'],['id'=>$id]);
 [$nodes]=$render();$node=array_values(array_filter($nodes,fn($node)=>($node['@id']??'')===$record['schemaIdentity']))[0];$check($node['location']['@type']==='VirtualLocation'&&str_ends_with($node['eventStatus'],'EventCancelled'),'Event overrides calendar mode and status');
 $db->update('tl_calendar_events',['published'=>0],['id'=>$id]);[$nodes]=$render();$check(count($nodes)===1,'Unpublished event is not emitted');$db->update('tl_calendar_events',['published'=>1],['id'=>$id]);
 $user=Contao\BackendUser::getInstance();$user->id=3;$user->admin='1';$user->username='calendar-regression';
 $security=new class implements Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface {public function isGranted(mixed $attribute,mixed $subject=null):bool{return false;}};
 $source=new VHUG\SchemaManagerBundle\Backend\ContentMapSource($db,$c->get('contao.routing.content_url_generator'),$security);
 $inventory=new VHUG\SchemaManagerBundle\Ai\SiteInventory($db,$source);$data=$inventory->collect(2,$user,true);
 $check(isset($data['sources']['event:'.$id],$data['records']['event:'.$id]),'Published calendar event is an editable AI source');
 $db->update('tl_calendar_events',['published'=>0],['id'=>$id]);$check(!isset($inventory->collect(2,$user,true)['sources']['event:'.$id]),'AI excludes inactive event');$db->update('tl_calendar_events',['published'=>1],['id'=>$id]);
 $policy=new VHUG\SchemaManagerBundle\Ai\FieldPolicy(new VHUG\SchemaManagerBundle\EventListener\BusinessDetailsListener($db),new VHUG\SchemaManagerBundle\EventListener\DataContainerListener($db),new VHUG\SchemaManagerBundle\EventListener\EntityDetailsListener($db));
 $engine=new VHUG\SchemaManagerBundle\Ai\ProposalEngine($db,$policy,$c->get('contao.cache.tag_manager'));
 $service=(int)$db->fetchOne("SELECT id FROM tl_schema_entity WHERE entityType='Service' AND published='1' ORDER BY id LIMIT 1");
 $run=['mode'=>'improve','stage'=>'content','inventory'=>$data,'proposals'=>[],'mapped'=>[],'decisions'=>[]];$sourceText=$data['sources']['event:'.$id];
 $engine->ingest($run,[['action'=>'add','target'=>'event:'.$id,'field'=>'schemaAbout','value'=>'entity:'.$service,'source'=>'event:'.$id,'quote'=>mb_substr($sourceText['text'],0,120),'reason'=>'Fixture event topic']],[$sourceText['id']=>$sourceText]);
 $check($run['proposals'][0]['status']==='pending','Calendar topic suggestion validates');$engine->apply($run,[0],$user);
 $check(in_array($service,array_map('intval',Contao\StringUtil::deserialize($db->fetchOne('SELECT schemaAbout FROM tl_calendar_events WHERE id=?',[$id]),true)),true),'Calendar topic applies to the event record');
 $setup=(new VHUG\SchemaManagerBundle\Ai\SetupPlanner($db,$inventory,new VHUG\SchemaManagerBundle\Ai\RunStore($db)))->prepare($user,2,true);
 $review=(new VHUG\SchemaManagerBundle\Ai\RunStore($db))->get($setup,3);$check(isset($review['inventory']['records']['calendar:'.$calendar]),'Calendar defaults are reviewable in parent stage');
 echo "PASS: Calendar inheritance, event overrides, core facts, exact replacement, suppression, inactive events, AI topic apply and parent review.\n";
}finally{$db->rollBack();echo "Calendar fixtures rolled back.\n";}
