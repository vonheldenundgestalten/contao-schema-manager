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

$user = new class extends Contao\BackendUser {
    public function __construct() {}
    public function __get($key) { return $key==='isAdmin' ? false : null; }
    public function hasAccess($field,$array) { return $array==='modules' && in_array($field,['page','news'],true); }
};
$deny = new class implements Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface {
    public array $calls=[];
    public function isGranted(mixed $attribute,mixed $subject=null): bool { $this->calls[]=$attribute;return false; }
};
$source=new VHUG\SchemaManagerBundle\Backend\ContentMapSource($db,$c->get('contao.routing.content_url_generator'),$deny);
$result=$source->load($user);
if ($result!==['pages'=>[],'news'=>[]] || !$deny->calls) { throw new RuntimeException('Denied records must not enter the graph source'); }
$pageId=(int)$db->fetchOne("SELECT id FROM tl_page WHERE type='regular' ORDER BY id LIMIT 1");
$newsId=class_exists(Contao\NewsModel::class)?(int)$db->fetchOne('SELECT id FROM tl_news ORDER BY id LIMIT 1'):0;
$allow=new class($pageId,$newsId) implements Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface {
    public function __construct(private int $page,private int $news){}
    public function isGranted(mixed $attribute,mixed $subject=null): bool {
        return $subject instanceof Contao\CoreBundle\Security\DataContainer\ReadAction &&
            (($attribute===Contao\CoreBundle\Security\ContaoCorePermissions::DC_PREFIX.'tl_page' && (int)$subject->getCurrentId()===$this->page)
            || ($attribute===Contao\CoreBundle\Security\ContaoCorePermissions::DC_PREFIX.'tl_news' && (int)$subject->getCurrentId()===$this->news));
    }
};
$source=new VHUG\SchemaManagerBundle\Backend\ContentMapSource($db,$c->get('contao.routing.content_url_generator'),$allow);
$result=$source->load($user);
if (array_map('intval',array_column($result['pages'],'id'))!==[$pageId] || ($newsId && array_map('intval',array_column($result['news'],'id'))!==[$newsId])) { throw new RuntimeException('Content source bypassed per-record authorization'); }
echo "PASS: per-record page/news read authorization filters inaccessible content; optional News source supported.\n";
