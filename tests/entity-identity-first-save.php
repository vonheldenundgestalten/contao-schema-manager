<?php

declare(strict_types=1);
// Installed Contao regression; all records are rolled back.
require getcwd().'/vendor/autoload.php';
$kernel=Contao\ManagerBundle\HttpKernel\ContaoKernel::fromInput(getcwd(),new Symfony\Component\Console\Input\ArgvInput());
$kernel->boot();$container=$kernel->getContainer();$container->get('contao.framework')->initialize();$db=$container->get('database_connection');
$listener=new VHUG\SchemaManagerBundle\EventListener\DataContainerListener($db);
$dc=new class extends Contao\DC_Table {
    public array $submitted=[];
    public function __construct() {}
    public function getActiveRecord(): ?array { return $this->submitted; }
};
$checks=0;$check=static function(bool $ok,string $why)use(&$checks):void{if(!$ok)throw new RuntimeException($why);++$checks;};
$reject=static function(callable $action,string $why)use($check):void{try{$action();}catch(InvalidArgumentException){$check(true,$why);return;}$check(false,$why);};
Contao\Controller::loadDataContainer('tl_schema_entity');
$check(($GLOBALS['TL_DCA']['tl_schema_entity']['fields']['entityId']['eval']['decodeEntities']??false)===true,'The identity field preserves URI fragment and query characters instead of HTML-encoding them.');
$postValue=static function(string $value)use($container):string {
    $stack=$container->get('request_stack');$request=Symfony\Component\HttpFoundation\Request::create('https://example.org/contao','POST');$request->attributes->set('_scope','backend');$stack->push($request);
    try {Contao\Input::setPost('entityId',$value);$widget=new Contao\TextField(['name'=>'entityId','decodeEntities'=>$GLOBALS['TL_DCA']['tl_schema_entity']['fields']['entityId']['eval']['decodeEntities']]);return (new ReflectionMethod($widget,'getPost'))->invoke($widget,'entityId');}
    finally {$stack->pop();}
};
$check($postValue('https://www.okal.de/#organization')==='https://www.okal.de/#organization','The real Contao widget input path preserves a literal fragment marker.');
$check($postValue('https://www.okal.de/entity?kind=company&lang=de#organization')==='https://www.okal.de/entity?kind=company&lang=de#organization','The real widget preserves query separators and fragments exactly.');
$db->beginTransaction();
try {
    $db->insert('tl_schema_entity',['name'=>'First-save identity regression','entityType'=>'Organization','identityBase'=>'','entityId'=>null,'published'=>'']);$dc->id=(int)$db->lastInsertId();
    $origin=$listener->identityBase('https://www.okal.de/',$dc);$dc->submitted=['identityBase'=>$origin];
    $check($origin==='https://www.okal.de','The submitted origin is normalized before identity validation.');
    $check($db->fetchOne('SELECT identityBase FROM tl_schema_entity WHERE id=?',[$dc->id])==='','Contao has not persisted the new origin during field validation.');
    $check($listener->immutableId($postValue('https://www.okal.de/#organization'),$dc)==='https://www.okal.de/#organization','The exact user-reported URL is accepted together with an unsaved origin.');
    $reject(fn()=>$listener->immutableId('https://okal.de/#organization',$dc),'A mismatched www/non-www origin is still rejected.');
    $reject(fn()=>$listener->immutableId('http://www.okal.de/#organization',$dc),'Plain HTTP remains rejected.');
    $check($listener->immutableId('',$dc)===null,'An intentionally blank ID remains eligible for automatic generation.');
    $db->insert('tl_schema_entity',['name'=>'Duplicate identity regression','entityType'=>'Organization','identityBase'=>$origin,'entityId'=>$origin.'/#organization','published'=>'']);$duplicate=(int)$db->lastInsertId();
    $reject(fn()=>$listener->immutableId($origin.'/#organization',$dc),'Existing identity uniqueness is enforced on first save.');$db->delete('tl_schema_entity',['id'=>$duplicate]);
    $db->update('tl_schema_entity',['identityBase'=>$origin,'entityId'=>$origin.'/#organization'],['id'=>$dc->id]);$listener->saveIdentity($dc);
    $check($db->fetchOne('SELECT entityId FROM tl_schema_entity WHERE id=?',[$dc->id])===$origin.'/#organization','Submission retains the explicit identity rather than generating a replacement.');
    $check($listener->immutableId($origin.'/#custom-organization',$dc)===$origin.'/#custom-organization','An existing public identity can deliberately be changed to a unique ID.');
    $db->insert('tl_schema_entity',['name'=>'Existing-ID collision regression','entityType'=>'Organization','identityBase'=>$origin,'entityId'=>$origin.'/#taken','published'=>'']);
    $reject(fn()=>$listener->immutableId($origin.'/#taken',$dc),'Editing an existing ID cannot take another entity’s ID.');
    $reject(fn()=>$listener->immutableId('',$dc),'Clearing an existing identity is rejected instead of silently replacing it.');
    $GLOBALS['TL_DCA']['tl_schema_entity']['fields']['entityId']['eval']['readonly']=true;$listener->identityEditor($dc);
    $check($GLOBALS['TL_DCA']['tl_schema_entity']['fields']['entityId']['eval']['readonly']===false,'The ordinary editor keeps the ID field editable after first save.');
    $dc->submitted=['identityBase'=>'https://different.example'];
    $reject(fn()=>$listener->identityBase('https://different.example',$dc),'The identity origin remains immutable after first save.');
    $db->update('tl_schema_entity',['entityId'=>null],['id'=>$dc->id]);$listener->saveIdentity($dc);$generated=$db->fetchOne('SELECT entityId FROM tl_schema_entity WHERE id=?',[$dc->id]);
    $check(str_starts_with($generated,$origin.'/#entity-'),'Blank IDs still generate an identity on submission.');
    $listener->saveIdentity($dc);$check($db->fetchOne('SELECT entityId FROM tl_schema_entity WHERE id=?',[$dc->id])===$generated,'Repeated saves preserve an automatically generated ID.');
    echo 'PASS: '.$checks." first-save identity, editable IDs, uniqueness and origin protection checks.\n";
} finally {$db->rollBack();echo "Identity regression fixtures rolled back.\n";}
