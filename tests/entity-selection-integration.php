<?php

declare(strict_types=1);

// Run from an installed Contao application root; fixture changes are rolled back.
require getcwd().'/vendor/autoload.php';
$kernel=Contao\ManagerBundle\HttpKernel\ContaoKernel::fromInput(getcwd(),new Symfony\Component\Console\Input\ArgvInput());
$kernel->boot();$c=$kernel->getContainer();$c->get('contao.framework')->initialize();$db=$c->get('database_connection');
use VHUG\SchemaManagerBundle\EventListener as Listeners;
use VHUG\SchemaManagerBundle\Schema\EntitySelection;
$selection=$c->get(EntitySelection::class);$checks=0;
$check=static function(bool $ok,string $message)use(&$checks):void{if(!$ok)throw new RuntimeException($message);++$checks;};
$dc=new class extends Contao\DataContainer{public function __construct(){$this->intId=0;}public function getPalette(){return '';}protected function save($value){}};
$sources=new Listeners\SourceSettingsListener($db,$selection);
$business=new Listeners\BusinessDetailsListener($db,$selection);
$places=new Listeners\EventLocationListener($db,null,$selection);
$pickers=[
 'all entities'=>[$sources->entities(...),['Organization','LocalBusiness','Person','Place','Service','SoftwareApplication','Product','Event']],
 'people'=>[$sources->people(...),['Person']],
 'organizations'=>[(new Listeners\DataContainerListener($db,$selection))->organizations(...),['Organization','LocalBusiness']],
 'business organizations'=>[$business->organizations(...),['Organization','LocalBusiness']],
 'offices'=>[$business->offices(...),['LocalBusiness']],
 'knowledge topics'=>[static fn()=>$business->knowledgeTopicOptions($dc),['Organization','LocalBusiness','Person','Place','Service','SoftwareApplication','Product','Event']],
 'software'=>[static fn()=>$business->softwareOptions($dc),['SoftwareApplication']],
 'services'=>[(new Listeners\EntityDetailsListener($db,$selection))->services(...),['Service']],
 'performers'=>[(new Listeners\CalendarSettingsListener($db,$c->get('contao.cache.tag_manager'),$selection))->performers(...),['Person','Organization','LocalBusiness']],
 'venues'=>[$places->places(...),['Place','LocalBusiness']],
];
$db->beginTransaction();
try {
 $active=[];$disabled=[];
 foreach(['Organization','LocalBusiness','Person','Place','Service','SoftwareApplication','Product','Event'] as $type){
  foreach(['1',''] as $published){
   $db->insert('tl_schema_entity',['name'=>'Selection test '.$type,'entityType'=>$type,'entityId'=>'https://example.org/#selection-'.bin2hex(random_bytes(12)),'identityBase'=>'https://example.org','published'=>$published]);
   if($published)$active[$type]=(int)$db->lastInsertId();else $disabled[$type]=(int)$db->lastInsertId();
  }
 }
 $db->update('tl_schema_entity',['knowledgeTopics'=>serialize([$disabled['Person']])],['id'=>$active['Service']]);
 foreach($pickers as $name=>[$options,$types]){
  $values=$options();
  foreach($types as $type)$check(isset($values[$active[$type]]),$name.' offers enabled '.$type);
  foreach($disabled as $id)$check(!isset($values[$id]),$name.' excludes disabled entities');
 }
 $check($db->fetchOne('SELECT knowledgeTopics FROM tl_schema_entity WHERE id=?',[$active['Service']])===serialize([$disabled['Person']]),'Generating options never rewrites existing saved references.');
 try{$places->validatePlace($disabled['Place']);$check(false,'Disabled venues must be rejected.');}catch(InvalidArgumentException){$check(true,'Disabled venue selections are rejected.');}
 $check($places->validatePlace($active['Place'])===$active['Place'],'Enabled venues remain valid.');
 foreach($active as $id)$db->update('tl_schema_entity',['published'=>''],['id'=>$id]);
 foreach($pickers as $name=>[$options,$types]){$values=$options();foreach($active as $id)$check(!isset($values[$id]),$name.' immediately removes newly disabled entities');}
 // A source-bound index can be published while its source is unavailable.
 $db->update('tl_schema_entity',['published'=>'1'],['id'=>$active['Place']]);
 $source=new VHUG\SchemaManagerBundle\Extension\V1\SourceReference('selection.unavailable','1','disabled-source');
 $db->insert('tl_schema_extension_binding',['entity'=>$active['Place'],'adapterKey'=>$source->adapterKey,'installationScope'=>$source->installationScope,'subjectKey'=>$source->subjectKey,'sourceHash'=>$source->key()]);
 $check(!$selection->contains($active['Place']),'A published reference with an unavailable source is not selectable.');
 echo 'PASS: '.$checks." shared entity picker publication, validation and reference-preservation checks.\n";
}finally{$db->rollBack();}
