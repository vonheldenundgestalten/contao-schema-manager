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

$db->beginTransaction();
try {
 $user=Contao\BackendUser::getInstance();$user->id=3;$user->admin='1';$user->username='author-regression';
 $security=new class implements Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface {public function isGranted(mixed $attribute,mixed $subject=null):bool{return false;}};
 $inventory=new VHUG\SchemaManagerBundle\Ai\SiteInventory($db,new VHUG\SchemaManagerBundle\Backend\ContentMapSource($db,$c->get('contao.routing.content_url_generator'),$security));
 $data=$inventory->collect(2,$user,true);
 $newsKey=null;foreach($data['records'] as $key=>$row){if(str_starts_with($key,'news:')&&!empty($row['_authorTarget'])){$newsKey=$key;break;}}
 $check($newsKey!==null,'Published news author available');$authorKey=$data['records'][$newsKey]['_authorTarget'];$authorId=$data['records'][$authorKey]['id'];
 $db->update('tl_user',['schemaPerson'=>0,'disable'=>'1'],['id'=>$authorId]);
 $data=$inventory->collect(2,$user,true);$author=$data['records'][$authorKey];
 $check(array_keys($author)===['id','name','schemaPerson'],'Only minimal author fields exposed, disabled login does not exclude published attribution');
 $check(str_contains($data['sources'][$newsKey]['text'],'Contao editorial author: '.$author['name']),'Explicit author assignment enters evidence');
 $policy=new VHUG\SchemaManagerBundle\Ai\FieldPolicy(new VHUG\SchemaManagerBundle\EventListener\BusinessDetailsListener($db),new VHUG\SchemaManagerBundle\EventListener\DataContainerListener($db),new VHUG\SchemaManagerBundle\EventListener\EntityDetailsListener($db));
 $engine=new VHUG\SchemaManagerBundle\Ai\ProposalEngine($db,$policy,$c->get('contao.cache.tag_manager'));
 $person=(int)$db->fetchOne("SELECT id FROM tl_schema_entity WHERE entityType='Person' AND published='1' LIMIT 1");
 $run=['mode'=>'discover','stage'=>'content','inventory'=>$data,'proposals'=>[],'mapped'=>[]];
 $source=$data['sources'][$newsKey];$p=['action'=>'add','target'=>$authorKey,'field'=>'schemaPerson','value'=>'entity:'.$person,'source'=>$newsKey,'quote'=>'Contao editorial author: '.$author['name'],'reason'=>'Reuse person for the editorial author.'];
 $engine->ingest($run,[$p],[$newsKey=>$source]);$check($run['proposals'][0]['status']==='pending','Existing Person mapping allowed in discovery');
 $engine->apply($run,[0],$user);$check((int)$db->fetchOne('SELECT schemaPerson FROM tl_user WHERE id=?',[$authorId])===$person,'Scalar Person mapping applied');
 $data=$inventory->collect(2,$user,true);$run['inventory']=$data;$run['proposals']=[];$engine->ingest($run,[$p],[$newsKey=>$source]);$check($run['proposals'][0]['status']==='invalid','Existing mappings cannot be replaced');
 foreach($data['records'] as $key=>$row){if(str_starts_with($key,'news:')&&($row['_authorTarget']??'')===$authorKey)$db->update('tl_news',['published'=>''],['id'=>$row['id']]);}
 $check(!isset($inventory->collect(2,$user,true)['records'][$authorKey]),'Authors with no eligible published news excluded');
 echo "PASS: author evidence, minimal fields, inactive login, reuse, scalar apply, overwrite guard, unpublished exclusions.\n";
}finally{$db->rollBack();echo "Author fixtures rolled back.\n";}
