<?php

declare(strict_types=1);
require getcwd().'/vendor/autoload.php';
$kernel=Contao\ManagerBundle\HttpKernel\ContaoKernel::fromInput(getcwd(),new Symfony\Component\Console\Input\ArgvInput());$kernel->boot();$c=$kernel->getContainer();$c->get('contao.framework')->initialize();$db=$c->get('database_connection');
$checks=0;$check=static function(bool $ok,string $message)use(&$checks){if(!$ok)throw new RuntimeException($message);++$checks;};
use VHUG\SchemaManagerBundle\EventListener\ManagedAuthorListener;
$check(ManagedAuthorListener::references(['@type'=>'Person','name'=>'Backend only'],[])===[], 'Name-only users are not public authors.');
$check(ManagedAuthorListener::references(['@id'=>'https://example.test/#draft'],[])===[], 'An unresolved Person reference is removed.');
$check(ManagedAuthorListener::references([['@id'=>'https://example.test/#person'],['@type'=>'Person','name'=>'Backend'],['@id'=>'https://example.test/#person']],['https://example.test/#person'=>true])===[['@id'=>'https://example.test/#person']], 'Published managed references survive once.');
$db->beginTransaction();$stack=$c->get('request_stack');$pushed=false;
try {
 $token=bin2hex(random_bytes(8));
 $db->insert('tl_user',['username'=>'schema-author-test-'.$token,'name'=>'Backend Testauthor','disable'=>'1']);$account=(int)$db->lastInsertId();
 $db->insert('tl_schema_entity',['name'=>'Managed Testauthor','entityType'=>'Person','entityId'=>'https://example.test/#author-'.$token,'identityBase'=>'https://example.test','published'=>'1']);$person=(int)$db->lastInsertId();
 $source=$db->fetchAssociative("SELECT n.* FROM tl_news n INNER JOIN tl_news_archive a ON a.id=n.pid WHERE n.published='1' AND a.jumpTo>0 ORDER BY n.id LIMIT 1");
 $check((bool)$source,'A real news reader is available.');
 $archive=(int)$source['pid'];$db->update('tl_news_archive',['schemaType'=>'','schemaPublisher'=>0],['id'=>$archive]);
 $db->update('tl_news',['author'=>$account,'schemaAuthor'=>0,'schemaAbout'=>null,'schemaMentions'=>null,'schemaDateModified'=>''],['id'=>$source['id']]);
 $reader=Contao\PageModel::findById($db->fetchOne('SELECT jumpTo FROM tl_news_archive WHERE id=?',[$archive]));$reader->loadDetails();
 $request=Symfony\Component\HttpFoundation\Request::create('https://'.($reader->domain ?: 'example.test').'/');$request->attributes->set('pageModel',$reader);$stack->push($request);$pushed=true;
 $c->get('contao.routing.content_url_generator')->setContext((new Symfony\Component\Routing\RequestContext())->fromRequest($request));
 $entities=new VHUG\SchemaManagerBundle\Schema\EntityGraph($db,$c->get('contao.routing.content_url_generator'),$c->get('contao.cache.tag_manager'),new VHUG\SchemaManagerBundle\Schema\EntityMapper(),$stack);
 $news=new VHUG\SchemaManagerBundle\Schema\NewsGraph($db,$entities,$c->get('contao.routing.content_url_generator'),$c->get('contao.string.html_decoder'),$c->get('contao.cache.tag_manager'));
 $render=static function()use($db,$source,$news,$reader){
  $record=$db->fetchAssociative('SELECT * FROM tl_news WHERE id=?',[$source['id']]);$key='#/schema/news/'.$record['id'];
  $context=new Contao\CoreBundle\Routing\ResponseContext\ResponseContext();$manager=new Contao\CoreBundle\Routing\ResponseContext\JsonLd\JsonLdManager($context);$context->add($manager);$graph=$manager->getGraphForSchema($manager::SCHEMA_ORG);
  $graph->set((new Spatie\SchemaOrg\NewsArticle())->setProperty('@id',$key)->setProperty('author',['@type'=>'Person','name'=>'Backend only'])->setProperty('genre','Preserved'),$key);
  $template=new Contao\FrontendTemplate('news_full');$template->hasText=false;$template->figure=null;$emitted=[];
  $news->apply([['record'=>$record,'template'=>$template,'reader'=>true,'detail'=>true]],$reader->language,$manager,$emitted);
  $event=new Contao\CoreBundle\Event\JsonLdEvent();$event->setResponseContext($context);(new ManagedAuthorListener($db))($event);
  return $graph->get(Spatie\SchemaOrg\NewsArticle::class,$key)->toArray();
 };
 $node=$render();$check(!isset($node['author']) && $node['genre']==='Preserved','Default archive removes implicit account author without losing other properties.');
 $db->update('tl_user',['schemaPerson'=>$person],['id'=>$account]);$node=$render();
 $check(($node['author']['@id']??'')==='https://example.test/#author-'.$token,'An explicitly mapped Person automatically authors existing news, including with disabled backend login.');
 $db->update('tl_schema_entity',['published'=>''],['id'=>$person]);$check(!isset($render()['author']),'Draft Person is not a public author.');
 $db->update('tl_schema_entity',['published'=>'1','entityType'=>'Organization'],['id'=>$person]);$check(!isset($render()['author']),'An invalid non-Person user mapping is not an author.');
 $db->update('tl_schema_entity',['entityType'=>'Person'],['id'=>$person]);$db->update('tl_user',['schemaPerson'=>0],['id'=>$account]);$db->update('tl_news',['schemaAuthor'=>$person],['id'=>$source['id']]);$check(isset($render()['author']['@id']),'The existing deliberate per-news Person override remains supported.');
 $context=new Contao\CoreBundle\Routing\ResponseContext\ResponseContext();$manager=new Contao\CoreBundle\Routing\ResponseContext\JsonLd\JsonLdManager($context);$context->add($manager);$graph=$manager->getGraphForSchema($manager::SCHEMA_ORG);
 $graph->set((new Spatie\SchemaOrg\WebPage())->setProperty('@id','https://example.test/#page')->setProperty('author',['@type'=>'Person','name'=>'Backend only']));
 $event=new Contao\CoreBundle\Event\JsonLdEvent();$event->setResponseContext($context);(new ManagedAuthorListener($db))($event);
 $check(!isset($graph->get(Spatie\SchemaOrg\WebPage::class)->toArray()['author']),'Page name-only authors are removed too.');
 echo 'PASS: '.$checks." managed author policy and real news/page rendering checks.\n";
} finally {if($pushed)$stack->pop();$db->rollBack();echo "Managed-author fixtures rolled back.\n";}
