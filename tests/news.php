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
$pages = [];
foreach ($db->fetchFirstColumn("SELECT id FROM tl_page WHERE type = 'regular' AND published = '1' ORDER BY id") as $id) {
    $page = Contao\PageModel::findById($id); $page->loadDetails();
    if (!$page->protected && in_array($page->language, ['de', 'en'], true)) { $pages[$page->language] ??= $page; }
}
if (count($pages) !== 2) { throw new RuntimeException('Integration fixture needs published DE and EN pages.'); }
$check = static function (bool $ok, string $message): void { if (!$ok) { throw new RuntimeException($message); } };
$entities = new VHUG\SchemaManagerBundle\Schema\EntityGraph(
    $db, $c->get('contao.routing.content_url_generator'), $c->get('contao.cache.tag_manager'),
    new VHUG\SchemaManagerBundle\Schema\EntityMapper(),
    $c->get('request_stack')
);
$news = new VHUG\SchemaManagerBundle\Schema\NewsGraph($db, $entities, $c->get('contao.routing.content_url_generator'), $c->get('contao.string.html_decoder'), $c->get('contao.cache.tag_manager'));
$listener = new VHUG\SchemaManagerBundle\EventListener\JsonLdListener(
    $db, $c->get('request_stack'), $c->get('contao.routing.content_url_generator'),
    $c->get('contao.cache.tag_manager'), $entities, $news
);

$db->beginTransaction();
try {
    $archive=(int)$db->fetchOne("SELECT id FROM tl_news_archive WHERE jumpTo>0 ORDER BY id LIMIT 1");
    $org=(int)$db->fetchOne("SELECT id FROM tl_schema_entity WHERE entityType='Organization' AND published='1' LIMIT 1");
    if (!$archive || !$org) { throw new RuntimeException('Run on the configured pilot with an archive and published organization.'); }
    $db->update('tl_news_archive',['schemaType'=>'BlogPosting','schemaPublisher'=>$org],['id'=>$archive]);
    $token=bin2hex(random_bytes(8));
    $idUri='https://example.org/#article-'.$token;
    $db->insert('tl_news',['pid'=>$archive,'tstamp'=>time(),'headline'=>'Fixture &amp; headline','alias'=>'schema-test-'.$token,'date'=>time(),'time'=>time(),'published'=>'1','author'=>1,'schemaIdentity'=>$idUri]);
    $id=(int)$db->lastInsertId();
    $record=$db->fetchAssociative('SELECT * FROM tl_news WHERE id=?',[$id]);
    $template=new Contao\FrontendTemplate('news_full');
    $template->hasText=false;
    $item=['record'=>$record,'template'=>$template,'detail'=>true];
    $request=Symfony\Component\HttpFoundation\Request::create(getenv('SCHEMA_TEST_ORIGIN') ?: 'https://example.test/');
    $request->attributes->set('pageModel',$pages['en']);
    $c->get('request_stack')->push($request);
    $render=static function(string $mode, bool $reader=true)use($db,$archive,$id,$news,$item):array{
        $item['reader']=$reader; $item['record']=$db->fetchAssociative('SELECT * FROM tl_news WHERE id=?',[$id]);
        $db->update('tl_news_archive',['schemaType'=>$mode],['id'=>$archive]);
        $context=new Contao\CoreBundle\Routing\ResponseContext\ResponseContext();
        $manager=new Contao\CoreBundle\Routing\ResponseContext\JsonLd\JsonLdManager($context);
        $graph=$manager->getGraphForSchema($manager::SCHEMA_ORG);
        $graph->set((new Spatie\SchemaOrg\NewsArticle())->setProperty('@id','#/schema/news/'.$id)->setProperty('genre','Preserved value')->setProperty('about',['@type'=>'Thing','name'=>'Existing template subject']),'#/schema/news/'.$id);
        $graph->set((new Spatie\SchemaOrg\NewsArticle())->setProperty('@id','https://example.org/#unrelated'),'unrelated');
        $emitted=[];$subjects=$news->apply([$item],'en',$manager,$emitted);
        return [$graph->toArray()['@graph'],$subjects];
    };
    [$nodes,$subjects]=$render('BlogPosting');
    $matches=array_values(array_filter($nodes,static fn($n)=>($n['@id']??'')===$idUri));
    $check(count($matches)===1 && $matches[0]['@type']==='BlogPosting','Exactly one replacement article');
    $article=$matches[0];
    $check($article['genre']==='Preserved value','Keep properties added by core or other listeners');
    $check($article['headline']==='Fixture & headline','Decode headline');
    $check(str_contains($article['url'],'schema-test-'.$token),'Use real reader route including alias');
    $check($subjects===[['@id'=>$idUri]],'Link reader main subject');
    $check(isset($article['publisher']['@id'],$article['author']['@id']),'Shared publisher and author');
    $check(!isset($article['dateModified']),'Administrative timestamp not claimed as revision');
    $check(count(array_filter($nodes,static fn($n)=>$n['@type']==='NewsArticle'))===1,'Unrelated news node remains');

    $service=(int)$db->fetchOne("SELECT id FROM tl_schema_entity WHERE entityType='Service' AND published='1' LIMIT 1");
    $serviceIdentity=$db->fetchOne('SELECT entityId FROM tl_schema_entity WHERE id=?',[$service]);
    $db->insert('tl_schema_entity',['name'=>'Unpublished subject','entityType'=>'Service','identityBase'=>'https://example.org','entityId'=>'https://example.org/#hidden-'.$token,'published'=>'']);
    $hidden=(int)$db->lastInsertId();
    $db->update('tl_news',['schemaAbout'=>serialize([$service,$service,$hidden]),'schemaMentions'=>serialize([$org])],['id'=>$id]);
    [$nodes]=$render('BlogPosting');
    $article=array_values(array_filter($nodes,fn($n)=>($n['@id']??'')===$idUri))[0];
    $check($article['about']===[['@type'=>'Thing','name'=>'Existing template subject'],['@id'=>$serviceIdentity]],'Existing subject retained; published subject deduplicated; unpublished subject omitted');
    $check(count($article['mentions'])===1,'Explicit mentions emitted');
    $check(count(array_filter($nodes,fn($n)=>($n['@id']??'')===$serviceIdentity))===1,'Subject entity included in graph');
    [$nodes,$subjects]=$render('suppress');
    $check(count($nodes)===1 && $nodes[0]['@id']==='https://example.org/#unrelated' && !$subjects,'Suppress only exact source node');
    [$nodes,$subjects]=$render('');
    $check(count($nodes)===2 && !$subjects,'Default mode preserves core output');
    $db->update('tl_news_archive',['schemaJobCity'=>'Stuttgart','schemaJobCountry'=>'DE','schemaJobEmployment'=>serialize(['FULL_TIME'])],['id'=>$archive]);
    $db->update('tl_news',['teaser'=>'Public job description','schemaJobValidThrough'=>time()+3600],['id'=>$id]);
    [$nodes,$subjects]=$render('JobPosting');
    $jobs=array_values(array_filter($nodes,static fn($n)=>($n['@type']??'')==='JobPosting'));
    $check(count($jobs)===1 && $jobs[0]['hiringOrganization']['@id'] && $subjects===[['@id'=>$idUri]],'Job replaces article and becomes page subject');
    $check(!isset($jobs[0]['headline'],$jobs[0]['articleBody'],$jobs[0]['genre']) && $jobs[0]['title']==='Fixture & headline','No stale article properties in job');
    $check(str_contains($jobs[0]['url'],'schema-test-'.$token),'Job uses actual news route');
    [$nodes,$subjects]=$render('JobPosting',false);
    $check(!array_filter($nodes,static fn($n)=>($n['@type']??'')==='JobPosting') && !$subjects,'No JobPosting on lists');
    $db->update('tl_news',['schemaJobValidThrough'=>time()-10],['id'=>$id]);
    [$nodes,$subjects]=$render('JobPosting');
    $check(count($nodes)===1 && !$subjects,'Expired job suppresses its article without touching unrelated nodes');
    $response=new Symfony\Component\HttpFoundation\Response(); $response->setPrivate(); $response->setMaxAge(7200);
    $request->attributes->set('_schema_job_expires',time()+60);
    $responseEvent=new Symfony\Component\HttpKernel\Event\ResponseEvent($kernel,$request,Symfony\Component\HttpKernel\HttpKernelInterface::MAIN_REQUEST,$response);
    (new VHUG\SchemaManagerBundle\EventListener\JobExpiryListener())($responseEvent);
    $check($response->getMaxAge()<=60 && $response->headers->hasCacheControlDirective('private'),'Expiry caps response cache without exposing private pages');
    echo "PASS: news replacement and JobPosting routing, defaults, expiry, list suppression and cache checks.\n";
} finally {
    if (isset($request)) { $c->get('request_stack')->pop(); }
    $db->rollBack();
    echo "All news fixture records and archive settings rolled back.\n";
}
