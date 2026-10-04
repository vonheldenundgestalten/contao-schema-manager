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
try{
 $user=Contao\BackendUser::getInstance();$user->id=3;$user->admin='1';$user->username='migration-test';
 $security=new class implements Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface {public function isGranted(mixed $a,mixed $s=null):bool{return false;}};
 $inventory=new VHUG\SchemaManagerBundle\Ai\SiteInventory($db,new VHUG\SchemaManagerBundle\Backend\ContentMapSource($db,$c->get('contao.routing.content_url_generator'),$security));
 $data=$inventory->collect(2,$user,true);$source=null;foreach($data['sources'] as $s)if(str_starts_with($s['id'],'page:')){$source=$s;break;}$check((bool)$source,'Public page fixture');
 $article=(int)$db->fetchOne('SELECT id FROM tl_article WHERE pid=? LIMIT 1',[$source['page']]);$check($article>0,'Article fixture');
 $identity='https://example.org/#audit-'.bin2hex(random_bytes(6));$name='Migration fixture';
 $db->insert('tl_schema_entity',['name'=>$name,'entityType'=>'Service','published'=>'1','identityBase'=>'https://example.org','entityId'=>$identity]);$entity=(int)$db->lastInsertId();
 $old=['@context'=>'https://schema.org','@type'=>'Service','name'=>$name];$new=$old+['@id'=>$identity];
 $markup=static fn($node)=>'<script type="application/ld+json">'.json_encode($node).'</script>';
 $db->insert('tl_content',['pid'=>$article,'ptable'=>'tl_article','type'=>'html','html'=>$markup($old),'invisible'=>0]);$element=(int)$db->lastInsertId();
 $audit=new class($db,$inventory) extends VHUG\SchemaManagerBundle\Ai\SchemaAudit {public string $html='';public function fetch(string $url):string{return $this->html;}};
 $audit->html=$markup($old).$markup($new);$result=$audit->inspect($source);
 $check(count($result['elements'])===1&&$result['elements'][0]['ready'],'Exact local schema-only source with complete replacement ready');
 $run=['audit'=>true,'status'=>'complete','root'=>2,'inventory'=>$data,'auditResults'=>[$source['id']=>$result]];
 $stale=$run;$db->update('tl_content',['html'=>$markup($old).' '],['id'=>$element]);
 try{$audit->retire($stale,[$source['id'].'|'.$element],$user);throw new LogicException('Stale content accepted');}catch(RuntimeException $expected){}
 $db->update('tl_content',['html'=>$markup($old)],['id'=>$element]);
 $check($audit->retire($run,[$source['id'].'|'.$element],$user)===1,'Explicit retirement succeeds');
 $check((int)$db->fetchOne('SELECT invisible FROM tl_content WHERE id=?',[$element])===1,'Content disabled, not deleted');
 $check($db->fetchOne('SELECT name FROM tl_schema_entity WHERE id=?',[$entity])===$name,'Existing entity unchanged');
 $db->update('tl_content',['invisible'=>0,'html'=>'<p>Visible content</p>'.$markup($old)],['id'=>$element]);$result=$audit->inspect($source);$check(!$result['elements'][0]['ready'],'Mixed visible content blocked');
 $old['description']='Important legacy detail';$db->update('tl_content',['html'=>$markup($old)],['id'=>$element]);$audit->html=$markup($old).$markup($new);$result=$audit->inspect($source);$check(!$result['elements'][0]['ready'],'Missing replacement property blocked');
 $old=['@context'=>'https://schema.org','@type'=>'Service','name'=>$name,'@id'=>'https://example.org/#old'];$db->update('tl_content',['html'=>$markup($old)],['id'=>$element]);$audit->html=$markup($old).$markup($new);$result=$audit->inspect($source);$check(!$result['elements'][0]['ready'],'Changed identity requires manual reference review');
 $db->insert('tl_schema_translation',['pid'=>$entity,'language'=>'en','page'=>$source['page'],'published'=>'']);
 $check((bool)array_filter($audit->warnings(),fn($r)=>$r['name']===$name),'Published parent with draft home visibly diagnosed');
 echo "PASS: source attribution, explicit retirement, versioned disable, entity preservation, visible HTML/property/identity guards and draft-home warnings.\n";
}finally{$db->rollBack();echo "Migration fixtures rolled back.\n";}
