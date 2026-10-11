<?php
declare(strict_types=1);
// Run from the installed Contao application's root. Error pages (error_401/403/404 ...) are not routable:
// the JSON-LD listeners must skip URL-derived output instead of letting RouteNotFoundException cause an HTTP 500.
require getcwd().'/vendor/autoload.php';
$kernel=Contao\ManagerBundle\HttpKernel\ContaoKernel::fromInput(getcwd(),new Symfony\Component\Console\Input\ArgvInput());
if(getenv('SCHEMA_TEST_DB_TCP')==='1'){
 foreach(['_SERVER','_ENV'] as $scope){if(isset($GLOBALS[$scope]['DATABASE_URL'])){$GLOBALS[$scope]['DATABASE_URL']=str_replace('@localhost','@127.0.0.1',$GLOBALS[$scope]['DATABASE_URL']);}}
 if(isset($_SERVER['DATABASE_URL'])){putenv('DATABASE_URL='.$_SERVER['DATABASE_URL']);}
}
$kernel->boot();$c=$kernel->getContainer();$c->get('contao.framework')->initialize();$db=$c->get('database_connection');

$stack=$c->get('request_stack');$urls=$c->get('contao.routing.content_url_generator');$tags=$c->get('contao.cache.tag_manager');
$root=Contao\PageModel::findOneBy(['type=?'],['root']);
if(!$root){throw new RuntimeException('A root page is required');}
// In-memory error page: never saved, so the database stays untouched.
$page=new Contao\PageModel();
$page->setRow(['id'=>999999,'pid'=>$root->id,'type'=>'error_404','title'=>'Not found','language'=>$root->language,'rootId'=>$root->id,'published'=>'1']);
$request=Symfony\Component\HttpFoundation\Request::create('https://example.test/missing');
$request->attributes->set('pageModel',$page);
$newEvent=static function() use(&$graph):Contao\CoreBundle\Event\JsonLdEvent{
 $context=new Contao\CoreBundle\Routing\ResponseContext\ResponseContext();
 $manager=new Contao\CoreBundle\Routing\ResponseContext\JsonLd\JsonLdManager($context);$context->add($manager);
 $graph=$manager->getGraphForSchema($manager::SCHEMA_ORG);
 $event=new Contao\CoreBundle\Event\JsonLdEvent();$event->setResponseContext($context);
 return $event;
};
$stack->push($request);
try{
 $entities=new VHUG\SchemaManagerBundle\Schema\EntityGraph($db,$urls,$tags,new VHUG\SchemaManagerBundle\Schema\EntityMapper(),$stack);
 $news=new VHUG\SchemaManagerBundle\Schema\NewsGraph($db,$entities,$urls,$c->get('contao.string.html_decoder'),$tags);
 $listeners=[
  'JsonLdListener'=>new VHUG\SchemaManagerBundle\EventListener\JsonLdListener($db,$stack,$urls,$tags,$entities,$news),
  'PageMetadataListener'=>new VHUG\SchemaManagerBundle\EventListener\PageMetadataListener($stack,$urls,$db,$tags),
 ];
 foreach($listeners as $name=>$listener){
  foreach(['error_401','error_403','error_404','error_503'] as $type){
   $page->type=$type;
   $event=$newEvent();
   try{$listener($event);}catch(Throwable $e){throw new RuntimeException($name.' threw on '.$type.': '.$e->getMessage());}
   foreach($graph->toArray()['@graph']??[] as $node){
    if(isset($node['url'])||isset($node['@id'])&&str_contains((string)$node['@id'],'#webpage')){throw new RuntimeException($name.' emitted a URL-derived node on '.$type);}
   }
  }
 }
 // Not routable but not an error page: generate() failures must be swallowed as well.
 $page->type='regular';$page->id=999999;
 $event=$newEvent();
 try{$listeners['PageMetadataListener']($event);}catch(Symfony\Component\Routing\Exception\RoutingException $e){throw new RuntimeException('Unroutable page escaped the try/catch');}
 echo "PASS: error pages and unroutable pages do not throw and emit no URL-derived JSON-LD.\n";
}finally{$stack->pop();}
