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
 $user=Contao\BackendUser::getInstance();$user->id=3;$user->username='missing-link-test';$user->admin='1';
 $add=static function(array $data)use($db):int{$db->insert('tl_schema_entity',$data+['name'=>'Missing-link fixture','entityType'=>'Service']);return (int)$db->lastInsertId();};
 $owner=$add(['published'=>'1']);$draft=$add(['published'=>'']);$gone=$add([]);$db->delete('tl_schema_entity',['id'=>$gone]);
 $db->update('tl_schema_entity',['subservices'=>serialize([$draft,$gone]),'organization'=>$gone],['id'=>$owner]);
 $record=$db->fetchAssociative('SELECT * FROM tl_schema_entity WHERE id=?',[$owner]);
 $run=['mode'=>'improve','stage'=>'content','inventory'=>['records'=>['entity:'.$owner=>$record,'translation:1'=>['pid'=>$owner,'page'=>100]],'sources'=>['page:100'=>['id'=>'page:100','title'=>'Fixture','url'=>'https://example.org/']]],'proposals'=>[],'decisions'=>[]];
 $cleanup=new VHUG\SchemaManagerBundle\Ai\MissingRelations($db);$cleanup->propose($run);
 $check(count($run['proposals'])===2,'Deleted list and scalar targets proposed');$check(!array_filter($run['proposals'],fn($p)=>$p['value']==='entity:'.$draft),'Unpublished target preserved');
 $cleanup->propose($run);$check(count($run['proposals'])===2,'Cleanup proposals deduplicated');
 $discover=$run;$discover['mode']='discover';$discover['proposals']=[];$cleanup->propose($discover);$check(!$discover['proposals'],'New-entity runs never remove links');
 $list=array_values(array_filter($run['proposals'],fn($p)=>$p['field']==='subservices'))[0];$scalar=array_values(array_filter($run['proposals'],fn($p)=>$p['field']==='organization'))[0];
 $extra=$add(['published'=>'1']);$db->update('tl_schema_entity',['subservices'=>serialize([$draft,$gone,$extra])],['id'=>$owner]);
 $policy=new VHUG\SchemaManagerBundle\Ai\FieldPolicy(new VHUG\SchemaManagerBundle\EventListener\BusinessDetailsListener($db),new VHUG\SchemaManagerBundle\EventListener\DataContainerListener($db),new VHUG\SchemaManagerBundle\EventListener\EntityDetailsListener($db));
 $engine=new VHUG\SchemaManagerBundle\Ai\ProposalEngine($db,$policy,$c->get('contao.cache.tag_manager'));
 $listIndex=array_search($list,$run['proposals'],true);$check($engine->apply($run,[$listIndex],$user)===1&&$run['proposals'][$listIndex]['status']==='applied','Normal review applies cleanup proposal');$check(Contao\StringUtil::deserialize($db->fetchOne('SELECT subservices FROM tl_schema_entity WHERE id=?',[$owner]),true)===[$draft,$extra],'Only missing reference removed; draft and concurrent added links preserved');
 $db->insert('tl_schema_entity',['id'=>$gone,'name'=>'Restored target','entityType'=>'Organization','published'=>'']);
 try{$cleanup->apply($scalar,$run,$user);throw new LogicException('Restored target removed');}catch(RuntimeException $expected){}
 $check((int)$db->fetchOne('SELECT organization FROM tl_schema_entity WHERE id=?',[$owner])===$gone,'Restored draft relation retained');
 $db->delete('tl_schema_entity',['id'=>$gone]);$cleanup->apply($scalar,$run,$user);$check(!(int)$db->fetchOne('SELECT organization FROM tl_schema_entity WHERE id=?',[$owner]),'Missing scalar relation cleared');
 $check((int)$db->fetchOne("SELECT COUNT(*) FROM tl_version WHERE fromTable='tl_schema_entity' AND pid=?",[$owner])>0,'Removal versioned for undo');
 $check((bool)$db->fetchOne('SELECT id FROM tl_schema_entity WHERE id=?',[$owner]),'Owner entity retained');
 echo "PASS: improvement-only cleanup, deleted versus draft targets, list/scalar removals, concurrent changes, restored targets and versions.\n";
}finally{$db->rollBack();echo "Cleanup fixtures rolled back.\n";}
