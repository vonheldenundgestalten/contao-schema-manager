<?php
declare(strict_types=1);
require getcwd().'/vendor/autoload.php';
$kernel=Contao\ManagerBundle\HttpKernel\ContaoKernel::fromInput(getcwd(),new Symfony\Component\Console\Input\ArgvInput());
if(getenv('SCHEMA_TEST_DB_TCP')==='1'){
 foreach(['_SERVER','_ENV'] as $scope){if(isset($GLOBALS[$scope]['DATABASE_URL'])){$GLOBALS[$scope]['DATABASE_URL']=str_replace('@localhost','@127.0.0.1',$GLOBALS[$scope]['DATABASE_URL']);}}
 if(isset($_SERVER['DATABASE_URL'])){putenv('DATABASE_URL='.$_SERVER['DATABASE_URL']);}
}
$kernel->boot();$c=$kernel->getContainer();$c->get('contao.framework')->initialize();$db=$c->get('database_connection');


$stack=$c->get('request_stack');$page=null;
foreach($db->fetchFirstColumn("SELECT id FROM tl_page WHERE type='regular' AND published='1' AND requireItem='' ORDER BY id") as $id){
 $candidate=Contao\PageModel::findById($id);$candidate->loadDetails();
 if(!$candidate->protected){$page=clone $candidate;break;}
}
if(!$page){throw new RuntimeException('A public regular page is required');}
$page->schemaPageType='ContactPage';
$root=Contao\PageModel::findById($page->rootId);$savedRoot=$root->row();
$root->schemaWebsiteRoot=0;$root->schemaWebsiteHome=$page->id;$root->schemaWebsiteId='https://example.test/&#35;manually-restored';
$root->schemaImportedActive='1';$root->schemaImportedData=json_encode(['@type'=>'WebSite','@id'=>'https://example.test/#outdated-import']);
$request=Symfony\Component\HttpFoundation\Request::create(getenv('SCHEMA_TEST_ORIGIN') ?: 'https://example.test/');
$request->attributes->set('pageModel',$page);
$context=new Contao\CoreBundle\Routing\ResponseContext\ResponseContext();
$manager=new Contao\CoreBundle\Routing\ResponseContext\JsonLd\JsonLdManager($context);$context->add($manager);
$head=new Contao\CoreBundle\Routing\ResponseContext\HtmlHeadBag\HtmlHeadBag();
$head->setTitle('Page title')->setMetaDescription('Description')->setCanonicalUri('https://example.test/contact');$context->add($head);
$graph=$manager->getGraphForSchema($manager::SCHEMA_ORG);
$graph->set((new Spatie\SchemaOrg\WebPage())->setProperty('about',['@id'=>'https://example.test/#company'])->setProperty('primaryImageOfPage',['@id'=>'https://example.test/#core-image']));
$graph->set((new Spatie\SchemaOrg\ImageObject())->setProperty('@id','https://example.test/#core-image'),'https://example.test/#core-image');
$graph->set((new Spatie\SchemaOrg\BreadcrumbList())->setProperty('itemListElement',[['@type'=>'ListItem','position'=>1,'name'=>'Home','item'=>'https://example.test/']]));
$event=new Contao\CoreBundle\Event\JsonLdEvent();$event->setResponseContext($context);
$stack->push($request);
try{
 $listener=new VHUG\SchemaManagerBundle\EventListener\PageMetadataListener($stack,$c->get('contao.routing.content_url_generator'),$db,$c->get('contao.cache.tag_manager'));
 $listener($event);
 $nodes=$graph->toArray()['@graph'];$pages=array_values(array_filter($nodes,static fn($n)=>in_array($n['@type'],['WebPage','ContactPage'],true)));
 if(count($pages)!==1 || $pages[0]['@type']!=='ContactPage'){throw new RuntimeException('Page type replacement failed');}
 $p=$pages[0];
 if($p['name']!=='Page title' || $p['description']!=='Description' || $p['url']!=='https://example.test/contact'){throw new RuntimeException('Head metadata lost');}
 if($p['about']['@id']!=='https://example.test/#company' || $p['primaryImageOfPage']['@id']!=='https://example.test/#core-image'){throw new RuntimeException('Existing relationships overwritten');}
 if(count(array_filter($nodes,static fn($n)=>$n['@type']==='ImageObject'))!==1 || !isset($p['breadcrumb']['@id'])){throw new RuntimeException('Core image/breadcrumb not preserved');}
 $sites=array_values(array_filter($nodes,static fn($n)=>$n['@type']==='WebSite'));
 if(count($sites)!==1||$sites[0]['@id']!=='https://example.test/#manually-restored'||$p['isPartOf']['@id']!==$sites[0]['@id'])throw new RuntimeException('Imported data overrode the corrected website ID or references');
 echo "PASS: core page type, canonical metadata, existing image and breadcrumb preserved; no database writes.\n";
}finally{foreach($savedRoot as $field=>$value)$root->$field=$value;$stack->pop();}
