<?php
declare(strict_types=1);
require getcwd().'/vendor/autoload.php';
$kernel=Contao\ManagerBundle\HttpKernel\ContaoKernel::fromInput(getcwd(),new Symfony\Component\Console\Input\ArgvInput());
if(getenv('SCHEMA_TEST_DB_TCP')==='1'){
 foreach(['_SERVER','_ENV'] as $scope)if(isset($GLOBALS[$scope]['DATABASE_URL']))$GLOBALS[$scope]['DATABASE_URL']=str_replace('@localhost','@127.0.0.1',$GLOBALS[$scope]['DATABASE_URL']);
 if(isset($_SERVER['DATABASE_URL']))putenv('DATABASE_URL='.$_SERVER['DATABASE_URL']);
}
$kernel->boot();$c=$kernel->getContainer();$c->get('contao.framework')->initialize();$db=$c->get('database_connection');
$check=static function(bool $ok,string $message):void{if(!$ok)throw new RuntimeException($message);};
$page=null;foreach(Contao\PageModel::findBy(['type=?','published=?'],['regular','1'])??[] as $candidate){$candidate->loadDetails();if(!$candidate->protected&&!$candidate->requireItem){$page=$candidate;break;}}
if(!$page)throw new RuntimeException('Requires a public regular page');
$stack=$c->get('request_stack');$request=Symfony\Component\HttpFoundation\Request::create('https://example.test/');$request->attributes->set('pageModel',$page);$stack->push($request);
$entities=new VHUG\SchemaManagerBundle\Schema\EntityGraph($db,$c->get('contao.routing.content_url_generator'),$c->get('contao.cache.tag_manager'),new VHUG\SchemaManagerBundle\Schema\EntityMapper(),$stack);
$newManager=static fn()=>new Contao\CoreBundle\Routing\ResponseContext\JsonLd\JsonLdManager(new Contao\CoreBundle\Routing\ResponseContext\ResponseContext());
$db->beginTransaction();
try{
 $ids=[];
 foreach(['Place','LocalBusiness'] as $type){$db->insert('tl_schema_entity',['entityType'=>$type,'name'=>'Location regression '.$type,'entityId'=>'https://example.test/#location-regression-'.strtolower($type),'identityBase'=>'https://example.test','published'=>'1','streetAddress'=>'Test street 1','addressLocality'=>'Test city','latitude'=>'48.5','longitude'=>'9.2']);$ids[$type]=(int)$db->lastInsertId();}
 $db->insert('tl_schema_entity',['entityType'=>'Event','name'=>'Regression event','entityId'=>'https://example.test/#location-regression-event','identityBase'=>'https://example.test','published'=>'1','eventLocationMode'=>'existing','eventAttendanceMode'=>'OfflineEventAttendanceMode','eventPlace'=>$ids['Place'],'locationName'=>'Stale venue','streetAddress'=>'Wrong street']);$eventId=(int)$db->lastInsertId();
 foreach($ids as $type=>$id){
  $db->update('tl_schema_entity',['eventPlace'=>$id],['id'=>$eventId]);$manager=$newManager();$emitted=[];$node=$entities->emit($eventId,$page->language,$manager,$emitted);
  $check($node['location']['@type']===$type && $node['location']['address']['streetAddress']==='Test street 1' && isset($node['location']['geo']),'Standalone reusable '.$type.' includes address and geo');
 }
 $db->update('tl_schema_entity',['eventAttendanceMode'=>'OnlineEventAttendanceMode','eventUrl'=>'https://example.test/live'],['id'=>$eventId]);$manager=$newManager();$emitted=[];$node=$entities->emit($eventId,$page->language,$manager,$emitted);$check($node['location']['@type']==='VirtualLocation','Online excludes existing venue');
 $db->update('tl_schema_entity',['eventAttendanceMode'=>'MixedEventAttendanceMode'],['id'=>$eventId]);$manager=$newManager();$emitted=[];$node=$entities->emit($eventId,$page->language,$manager,$emitted);$check(count($node['location'])===2,'Hybrid includes both');
 $db->update('tl_schema_entity',['published'=>''],['id'=>$ids['LocalBusiness']]);$db->update('tl_schema_entity',['eventAttendanceMode'=>'OfflineEventAttendanceMode'],['id'=>$eventId]);$manager=$newManager();$emitted=[];$node=$entities->emit($eventId,$page->language,$manager,$emitted);$check(!isset($node['location']),'Unpublished venue does not expose stale address');
 if(class_exists(Contao\CalendarEventsModel::class)){
  $event=$db->fetchAssociative("SELECT e.* FROM tl_calendar_events e JOIN tl_calendar c ON c.id=e.pid WHERE e.published='1' AND c.protected='' LIMIT 1");
  if(!$event)throw new RuntimeException('Requires a public Calendar event fixture');
  $db->update('tl_calendar',['schemaMode'=>'enrich'],['id'=>$event['pid']]);
  $db->update('tl_calendar_events',['schemaLocationMode'=>'existing','schemaPlace'=>$ids['Place'],'schemaAttendanceMode'=>'OfflineEventAttendanceMode','schemaLocationName'=>'Stale name','schemaStreetAddress'=>'Wrong street','start'=>'','stop'=>''],['id'=>$event['id']]);
  $calendarGraph=new VHUG\SchemaManagerBundle\Schema\CalendarEventGraph($db,$entities,$c->get('contao.routing.content_url_generator'),$c->get('contao.cache.tag_manager'));
  $manager=$newManager();$emitted=[];$graph=$manager->getGraphForSchema($manager::SCHEMA_ORG);$graph->set((new Spatie\SchemaOrg\Event())->name('Core event')->setProperty('startDate','2030-01-01'),'core-event');
  $refs=$calendarGraph->apply([['id'=>$event['id'],'key'=>'core-event']],$page->language,$manager,$emitted);
  $node=$graph->get(Spatie\SchemaOrg\Event::class,$refs[0]['@id'])->toArray();
  $check($node['location']['@id']==='https://example.test/#location-regression-place' && $node['location']['address']['streetAddress']==='Test street 1','Calendar existing venue replaces core/custom address');
  $check($node['startDate']==='2030-01-01','Core event dates preserved');
 }
 echo "PASS: real entity/calendar graph, Place and compact LocalBusiness venues, unpublished suppression and online/hybrid handling. All fixture writes rolled back.\n";
}finally{$db->rollBack();$stack->pop();}
