<?php
declare(strict_types=1);
require getcwd().'/vendor/autoload.php';
$kernel=Contao\ManagerBundle\HttpKernel\ContaoKernel::fromInput(getcwd(),new Symfony\Component\Console\Input\ArgvInput());
if(getenv('SCHEMA_TEST_DB_TCP')==='1'){
 foreach(['_SERVER','_ENV'] as $scope){if(isset($GLOBALS[$scope]['DATABASE_URL'])){$GLOBALS[$scope]['DATABASE_URL']=str_replace('@localhost','@127.0.0.1',$GLOBALS[$scope]['DATABASE_URL']);}}
 if(isset($_SERVER['DATABASE_URL'])){putenv('DATABASE_URL='.$_SERVER['DATABASE_URL']);}
}
$kernel->boot();$c=$kernel->getContainer();$c->get('contao.framework')->initialize();$db=$c->get('database_connection');
$stack=$c->get('request_stack');$tags=$c->get('contao.cache.tag_manager');
$resolver=new VHUG\SchemaManagerBundle\Metadata\PageImageResolver($c->get('contao.image.studio'),$stack,new Symfony\Component\HttpFoundation\UrlHelper($stack),$tags,$c->get('contao.string.html_decoder'),new VHUG\SchemaManagerBundle\Metadata\ImageSelection(),new Psr\Log\NullLogger());
$metadata=new VHUG\SchemaManagerBundle\EventListener\PageMetadataListener($stack,$resolver,$c->get('contao.routing.content_url_generator'),$db,$tags);
$check=static function($ok,$message){if(!$ok){throw new RuntimeException($message);}};
$page=null;
foreach($db->fetchFirstColumn("SELECT id FROM tl_page WHERE type='regular' AND published='1' AND requireItem='' ORDER BY id") as $id){
 $p=Contao\PageModel::findById($id);$p->loadDetails();$r=Contao\PageModel::findById($p->rootId);
 if($p->language==='en' && $r->schemaFallbackImage){$page=clone $p;$root=$r;break;}
}
if(!$page){throw new RuntimeException('Pilot requires an EN page with configured fallback image.');}
$news=$db->fetchAssociative("SELECT * FROM tl_news WHERE published='1' AND addImage='1' AND singleSRC IS NOT NULL ORDER BY id LIMIT 1");
if(!$news){throw new RuntimeException('Pilot requires a published news image.');}
$origin=getenv('SCHEMA_TEST_ORIGIN') ?: 'https://example.test';
$c->get('router')->getContext()->fromRequest(Symfony\Component\HttpFoundation\Request::create($origin));
$run=static function(string $mode,bool $reader,bool $hero=false)use($page,$root,$news,$stack,$resolver,$metadata,$origin):array{
 $p=clone $page;$p->schemaImageMode=$mode;$p->schemaPageImage=$root->schemaFallbackImage;$p->schemaPageType='ContactPage';
 $request=Symfony\Component\HttpFoundation\Request::create($origin.'/en/fixture.html?tracking=discard');
 $request->attributes->set('pageModel',$p);
 if($reader){$request->attributes->set('_schema_manager_news',[['record'=>$news,'reader'=>true,'detail'=>true]]);}
 if($hero){$request->attributes->set('_schema_manager_hero',['uuid'=>$news['singleSRC'],'alt'=>'Marked hero']);}
 $context=new Contao\CoreBundle\Routing\ResponseContext\ResponseContext();
 $manager=new Contao\CoreBundle\Routing\ResponseContext\JsonLd\JsonLdManager($context);
 $context->add($manager);
 $head=new Contao\CoreBundle\Routing\ResponseContext\HtmlHeadBag\HtmlHeadBag();
 $head->setTitle('Resolved reader title')->setMetaDescription('Resolved description')->setCanonicalUri($origin.'/canonical.html');
 $context->add($head);
 $request->attributes->set(Contao\CoreBundle\Routing\ResponseContext\ResponseContext::REQUEST_ATTRIBUTE_NAME,$context);
 $graph=$manager->getGraphForSchema($manager::SCHEMA_ORG);
 $graph->set((new Spatie\SchemaOrg\WebPage())->setProperty('about',['@id'=>'https://example.org/#subject']));
 $crumb=(new Spatie\SchemaOrg\BreadcrumbList())->setProperty('itemListElement',[['@type'=>'ListItem','position'=>1,'name'=>'Home','item'=>$origin.'/']]);
 $graph->set($crumb);
 if($reader){$graph->set((new Spatie\SchemaOrg\BlogPosting())->setProperty('@id',$news['schemaIdentity'])->setProperty('mainEntityOfPage',['@id'=>$origin.'/old#webpage']),$news['schemaIdentity']);}
 $stack->push($request);
 try{
  $social=new VHUG\SchemaManagerBundle\EventListener\SocialMetadataListener(new Contao\CoreBundle\Routing\ResponseContext\ResponseContextAccessor($stack),$stack,$resolver);
  $template=new Contao\CoreBundle\Twig\LayoutTemplate('page/layout',static fn()=>new Symfony\Component\HttpFoundation\Response());
  $template->set('response_context',(object)['head'=>$head]);
  $social->onLayout(new Contao\CoreBundle\Event\LayoutEvent($template,$p,null));
  $template->get('response_context')->head;
  $social($p); // Legacy hook and modern preparation must not duplicate tags.
  $image=$resolver->resolve($p,$root);
  if($image){$graph->set($manager->createSchemaOrgTypeFromArray($image['schema']),$image['schema']['@id']);}
  $event=new Contao\CoreBundle\Event\JsonLdEvent();$event->setResponseContext($context);$metadata($event);
  return [$graph->toArray()['@graph'],$image,$head->getMetaTags()];
 }finally{$stack->pop();}
};
[$nodes,$image,$metaTags]=$run('auto',true);
$pages=array_values(array_filter($nodes,static fn($n)=>in_array($n['@type'],['WebPage','ContactPage'],true)));
$check(count($pages)===1 && $pages[0]['@type']==='ContactPage','Exactly one page node of selected subtype');
$p=$pages[0];
$check($p['name']==='Resolved reader title' && $p['description']==='Resolved description','Use resolved head metadata');
$check($p['about']['@id']==='https://example.org/#subject','Preserve existing page relations');
$check($p['url']===$origin.'/canonical.html','Explicit canonical URL respected');
$check($image['source']==='news' && $p['primaryImageOfPage']['@id']===$image['schema']['@id'],'News primary source');
$check(count(array_filter($nodes,static fn($n)=>$n['@type']==='ImageObject'))===1,'Reuse existing ImageObject');
$check(isset($p['breadcrumb']['@id']),'Connect core breadcrumb');
$site=array_values(array_filter($nodes,static fn($n)=>$n['@type']==='WebSite'))[0];
$check($site['url']===$origin.'/' && in_array('de',$site['inLanguage']) && in_array('en',$site['inLanguage']),'One domain website, both languages');
[, $image]=$run('override',true);
$check($image['source']==='page','Explicit page override wins over news');
[, $image]=$run('auto',false,true);
$check($image['source']==='hero','Marked hero wins over page fallback');
[, $image]=$run('auto',false);
$check($image['source']==='page','Page fallback for imageless page');
[$nodes,$image]=$run('none',true);
$p=array_values(array_filter($nodes,static fn($n)=>$n['@type']==='ContactPage'))[0];
$check($image===null && empty($p['primaryImageOfPage']),'Disabled mode emits no representative image');
$articles=array_values(array_filter($run('auto',true)[0],static fn($n)=>$n['@type']==='BlogPosting'));
$check($articles[0]['mainEntityOfPage']['@id']===$origin.'/canonical.html#webpage','Article refers to canonical page');
$imageTags=array_values(array_filter($metaTags,static fn($t)=>($t['property']??'')==='og:image'));
$check(count($imageTags)===1 && $imageTags[0]['content']===$run('auto',true)[1]['url'],'Modern and legacy metadata agree without duplicates');
echo "PASS: 14 real Contao metadata, page type, website, image and routing checks; no database writes.\n";
