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
    $render=static function(string $mode)use($db,$archive,$id,$news,$item):array{
        $db->update('tl_news_archive',['schemaType'=>$mode],['id'=>$archive]);
        $context=new Contao\CoreBundle\Routing\ResponseContext\ResponseContext();
        $manager=new Contao\CoreBundle\Routing\ResponseContext\JsonLd\JsonLdManager($context);
        $graph=$manager->getGraphForSchema($manager::SCHEMA_ORG);
        $graph->set((new Spatie\SchemaOrg\NewsArticle())->setProperty('@id','#/schema/news/'.$id)->setProperty('genre','Preserved value'),'#/schema/news/'.$id);
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
    [$nodes,$subjects]=$render('suppress');
    $check(count($nodes)===1 && $nodes[0]['@id']==='https://example.org/#unrelated' && !$subjects,'Suppress only exact source node');
    [$nodes,$subjects]=$render('');
    $check(count($nodes)===2 && !$subjects,'Default mode preserves core output');
    echo "PASS: 10 real Contao news replacement, suppression, reference and routing checks.\n";
} finally {
    if (isset($request)) { $c->get('request_stack')->pop(); }
    $db->rollBack();
    echo "All news fixture records and archive settings rolled back.\n";
}
