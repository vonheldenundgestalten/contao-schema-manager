<?php
declare(strict_types=1);
require getcwd().'/vendor/autoload.php';
require_once __DIR__.'/../examples/extension/src/ExampleProvider.php';
require_once __DIR__.'/../examples/extension/src/ExampleSourceAdapter.php';
$kernel=Contao\ManagerBundle\HttpKernel\ContaoKernel::fromInput(getcwd(),new Symfony\Component\Console\Input\ArgvInput());
foreach(['_SERVER','_ENV'] as $scope)if(isset($GLOBALS[$scope]['DATABASE_URL']))$GLOBALS[$scope]['DATABASE_URL']=str_replace('@localhost','@127.0.0.1',$GLOBALS[$scope]['DATABASE_URL']);
if(isset($_SERVER['DATABASE_URL']))putenv('DATABASE_URL='.$_SERVER['DATABASE_URL']);
$kernel->boot();$c=$kernel->getContainer();$c->get('contao.framework')->initialize();$db=$c->get('database_connection');
use VHUG\SchemaManagerBundle\Extension\V1 as Api;
use VHUG\SchemaManagerBundle\Extension\Internal as Internal;
$checks=0;$check=static function(bool $ok,string $why)use(&$checks){if(!$ok)throw new RuntimeException($why);$checks++;};
$reject=static function(callable $action,string $why)use($check){try{$action();}catch(InvalidArgumentException $e){$check(true,$why);return;}$check(false,$why);};
$actor=(int)$db->fetchOne("SELECT id FROM tl_user WHERE admin='1' ORDER BY id");
$site=(string)$db->fetchOne("SELECT id FROM tl_page WHERE type='root' ORDER BY id");
$path=tempnam(sys_get_temp_dir(),'schema-api-');$state=['site'=>$site,'key'=>'equipment-stable','published'=>true,'name'=>'Synthetic equipment','descriptions'=>['de'=>'Deutsch','en'=>'English'],'url'=>'https://example.org/equipment','expiry'=>time()+600];
$writeState=static function()use($path,&$state){file_put_contents($path,json_encode($state,JSON_THROW_ON_ERROR));};$writeState();
$provider=new SchemaManagerExample\ExampleProvider();$adapter=new SchemaManagerExample\ExampleSourceAdapter($path);
$registry=new Internal\Registry([$provider],[$provider],[$adapter],[get_class($provider)=>$provider]);
$invalidator=new Internal\SourceInvalidator($c->get('contao.cache.tag_manager'));
$bindings=new Internal\BindingManager($db,$registry,$invalidator);$store=new Internal\Store($db);$validator=new Internal\FieldValidator();
$runtime=new Internal\Runtime($registry,$store,$validator,$bindings,new VHUG\SchemaManagerBundle\Schema\EntityMapper());
$editor=new Internal\EntityEditor($db,$registry,$store,$validator,$runtime,$bindings,$invalidator);
$preview=new Api\ReadContext('preview',$site,'de',$actor,true,time());$write=new Api\WriteContext($preview,'https://example.org');$front=new Api\ReadContext('frontend',$site,'de',now:time());
if(method_exists($db,'setNestTransactionsWithSavepoints'))$db->setNestTransactionsWithSavepoints(true);
$db->beginTransaction();
try {
 $candidates=$editor->discover($adapter->key(),new Api\CandidateQuery(new Api\ReadContext('discovery',$site,'de',$actor,true)));
 $candidate=$candidates->candidates[0];$write=new Api\WriteContext($preview,'https://example.org',$candidate->revision);$ref=$bindings->bind(new Api\BindRequest($candidate->source,$candidate->revision,$candidate->typeKey),$write);
 $check($bindings->bind(new Api\BindRequest($candidate->source,$candidate->revision,$candidate->typeKey),$write)->id===$ref->id,'Binding is idempotent.');
 $identity=$store->record($ref->id)['entityId'];
 $crossRead=new Api\ReadContext('preview','987654','en',$actor,true);
 $check($runtime->render($ref->id,$crossRead)['node']===null,'Another root is denied by default.');
 $approved=new Internal\SiteScope(['987654'=>$site]);
 $crossRuntime=new Internal\Runtime($registry,$store,$validator,$bindings,new VHUG\SchemaManagerBundle\Schema\EntityMapper(),scopes:$approved);
 $cross=$crossRuntime->render($ref->id,$crossRead);
 $check($cross['node']['@id']===$identity && $cross['node']['description']==='English','Explicit approved root mapping shares identity and localizes facts.');
 $check($approved->read($crossRead)->root==='987654','Actual routing root remains distinct from approved source scope.');
 $crossWrite=new Api\WriteContext($crossRead,'https://example.org',$candidate->revision);
 $reject(fn()=>$bindings->bind(new Api\BindRequest($candidate->source,$candidate->revision,$candidate->typeKey),$crossWrite),'Unapproved root cannot bind/write another source scope.');
 $approvedBindings=new Internal\BindingManager($db,$registry,$invalidator,$approved);
 $check($approvedBindings->bind(new Api\BindRequest($candidate->source,$candidate->revision,$candidate->typeKey),$crossWrite)->id===$ref->id,'Explicit root mapping reuses the same binding without allocating identity.');
 $check($runtime->render($ref->id,$front)['node']===null,'Binding creates draft, never publishes.');
 $check($runtime->render($ref->id,$preview)['node']['name']==='Synthetic equipment','Authorized preview resolves source facts.');
 $reject(fn()=>$editor->saveFields($ref,['notes'=>'Override empty source'],false,0,$write),'Empty source-owned fields remain protected.');
 $rev=$editor->saveFields($ref,['capacity'=>['value'=>'135.31','unit'=>'MTK','basis'=>'DIN 277 net area']],false,0,$write);
 $reject(fn()=>$editor->saveFields($ref,['capacity'=>['value'=>'1','unit'=>'MTK','basis'=>'Living area']],false,0,$write),'Stale supplement revision rejected.');
 $db->update('tl_schema_entity',['published'=>'1'],['id'=>$ref->id]);
 $result=$runtime->render($ref->id,$front);
 $check($result['node']['additionalProperty']['name']==='DIN 277 net area' && $result['expiry']===$state['expiry'],'Source and supplements combine with visibility expiry.');
 $db->insert('tl_schema_entity',['name'=>'Synthetic manufacturer','entityType'=>'Organization','identityBase'=>'https://example.org','entityId'=>'https://example.org/#api-manufacturer-'.bin2hex(random_bytes(4)),'published'=>'1']);$manufacturer=(int)$db->lastInsertId();
 $rev=$editor->saveRelationship($ref,'example.manufacturer',[new Api\RelationshipValue(new Api\EntityReference($manufacturer))],$rev,$write);
 $check($runtime->render($ref->id,$front)['node']['manufacturer']['@id']===$store->record($manufacturer)['entityId'],'Public relationship targets existing identity.');
 $db->update('tl_schema_entity',['published'=>''],['id'=>$manufacturer]);
 $check(!isset($runtime->render($ref->id,$front)['node']['manufacturer']),'Draft target omitted publicly.');
 $reject(fn()=>$editor->saveRelationship($ref,'example.related',[new Api\RelationshipValue($ref)],$rev,$write),'Prohibited cycles rejected.');
 $state['name']='Renamed source';$state['url']='https://example.org/new-alias';$writeState();
 $node=$runtime->render($ref->id,$front)['node'];
 $check($node['@id']===$identity && $node['name']==='Renamed source' && $node['url']===$state['url'],'Source changes and alias updates preserve identity.');
 $reject(fn()=>$bindings->bind(new Api\BindRequest($candidate->source,$candidate->revision,$candidate->typeKey),$write),'Stale source revision rejected.');
 $state['start']=time()+120;$writeState();$scheduled=$runtime->render($ref->id,$front);
 $check($scheduled['node']===null && $scheduled['expiry']===$state['start'],'Unpublished scheduled source supplies cache deadline without facts.');
 unset($state['start']);$writeState();
 $state['published']=false;$writeState();
 $check($runtime->render($ref->id,$front)['node']===null,'Withdrawal removes public source output.');
 $state['published']=true;$state['forbidden']=true;$writeState();
 $check($crossRuntime->render($ref->id,$crossRead)['node']===null,'Approved root mapping never bypasses adapter record permissions.');
 $check($runtime->render($ref->id,$front)['node']===null,'Forbidden source fails closed.');
 $state['forbidden']=false;$writeState();
 $check($runtime->render($ref->id,new Api\ReadContext('frontend',$site,'fr'))['node']===null,'Missing translation has no silent fallback.');
 $without=new Internal\Registry([$provider],[$provider],[],[get_class($provider)=>$provider]);
 $missingRuntime=new Internal\Runtime($without,$store,$validator,$bindings,new VHUG\SchemaManagerBundle\Schema\EntityMapper());
 $check($missingRuntime->render($ref->id,$front)['node']===null,'Missing adapter retains binding but removes output.');
 $check($store->binding($ref->id)->key()===$candidate->source->key(),'Binding survives adapter removal.');
 $check($runtime->render($ref->id,$front)['node']['@id']===$identity,'Reinstalled adapter restores original identity.');
 $row=$store->record($ref->id);$projection=new Internal\GraphProjection($registry,$store,$bindings);
 $map=$projection->extend(['nodes'=>[['data'=>['id'=>'entity-'.$ref->id,'record'=>$ref->id,'type'=>'example.equipment']]],'edges'=>[]],[$row],'de');
 $check($map['nodes'][0]['data']['type']==='ProductModel','Custom graph type comes from definition.');

 $aiTypes=new class($provider) implements Api\TypeProviderInterface {
  public function __construct(private $provider){}
  public function types(): iterable {foreach($this->provider->types() as $t)yield new Api\TypeDefinition($t->key,$t->label,$t->schemaTypes,$t->mapper,array_map(static fn($f)=>new Api\FieldDefinition($f->key,$f->type,$f->localized,$f->multiple,$f->required,$f->constraints,$f->default,$f->widget,true),$t->fields),$t->relationships,properties:$t->properties,ai:true);}
 };
 $aiRegistry=new Internal\Registry([$aiTypes],[$provider],[$adapter],[get_class($provider)=>$provider]);
 $aiRuntime=new Internal\Runtime($aiRegistry,$store,$validator,$bindings,new VHUG\SchemaManagerBundle\Schema\EntityMapper());
 $bridge=new Internal\AiBridge($aiRegistry,$store,$aiRuntime,$editor,$db);
 $metadata=$bridge->context([(int)$site],'de',$actor);
 $check(isset($metadata['entity:'.$ref->id]['fields']['capacity']) && !isset($metadata['entity:'.$ref->id]['fields']['notes']),'AI allowlist excludes source-owned empty fields.');
 $inventory=['extensions'=>$metadata,'roots'=>[(int)$site],'records'=>['entity:'.$ref->id=>['id'=>$ref->id]]];
 $proposal=['target'=>'entity:'.$ref->id,'action'=>'extension_set','field'=>'capacity','value'=>json_encode(['value'=>'151.00','unit'=>'MTK','basis'=>'Typed AI fixture']),'quote'=>'Synthetic evidence'];
 $bridge->apply($proposal,$inventory,$actor,'https://example.org');
 $check($runtime->render($ref->id,$front)['node']['additionalProperty']['value']==='151.00','AI uses ordinary validated supplementary storage.');
 $reject(fn()=>$bridge->apply($proposal,$inventory,$actor,'https://example.org'),'Stale AI entity revision rejected.');
 $proposal['field']='notes';$proposal['value']='"Override"';
 $reject(fn()=>$bridge->validate($proposal,$inventory),'AI cannot override source-owned field.');
 $check((new Internal\AiBridge($registry,$store,$runtime,$editor,$db))->context([(int)$site],'de',$actor)===[],'Custom AI remains disabled by default.');

 $db->insert('tl_schema_entity',['name'=>'Manual equipment','entityType'=>'example.equipment','identityBase'=>'https://example.org','entityId'=>'https://example.org/#manual-api-'.bin2hex(random_bytes(4)),'schemaExtensionVersion'=>1,'published'=>'1']);$manual=new Api\EntityReference((int)$db->lastInsertId());
 $check($runtime->render($manual->id,$front)['node']['name']==='Manual equipment','Manual definitions reuse the existing shared entity name.');
 $editor->saveFields($manual,['name'=>'Renamed manual equipment'],false,0,new Api\WriteContext($preview,'https://example.org'));
 $check($store->record($manual->id)['name']==='Renamed manual equipment' && !isset(Internal\Store::decode($store->record($manual->id)['schemaExtensionData'])['name']),'Shared name edits do not create a duplicate supplementary authority.');
 $db->insert('tl_schema_translation',['pid'=>$manual->id,'language'=>'de','schemaImportedData'=>json_encode(['@id'=>'https://example.org/#legacy-manual'])]);
 $priorKey=$state['key'];$state['key']='another-stable-source';$writeState();
 $importSource=new Api\SourceReference($adapter->key(),$site,$state['key']);$importSnapshot=$adapter->resolve($importSource,$preview)->snapshot;
 $reject(fn()=>$bindings->bind(new Api\BindRequest($importSource,$importSnapshot->revision,'example.equipment',$manual),new Api\WriteContext($preview,'https://example.org')),'Binding cannot silently discard existing imported identities/properties.');
 $state['key']=$priorKey;$writeState();
 $publicRelations=new class implements Api\RelationshipProviderInterface {
  public function relationships():iterable{yield new Api\RelationshipDefinition('example.public_related','Related',['example.equipment'],['example.equipment'],property:'isRelatedTo',multiple:false);}
 };
 $orderRegistry=new Internal\Registry([$provider],[$provider,$publicRelations],[$adapter],[get_class($provider)=>$provider]);
 $orderRuntime=new Internal\Runtime($orderRegistry,$store,$validator,$bindings,new VHUG\SchemaManagerBundle\Schema\EntityMapper());
 $page=null;foreach(Contao\PageModel::findBy(['type=?','published=?'],['regular','1'])??[] as $candidatePage){$candidatePage->loadDetails();if(!$candidatePage->protected&&!$candidatePage->requireItem){$page=$candidatePage;break;}}
 if(!$page)throw new RuntimeException('Public page needed for graph-order regression.');
 $orderIds=[];
 foreach(['first','second'] as $name){
  $db->insert('tl_schema_entity',['name'=>'Order '.$name,'entityType'=>'example.equipment','identityBase'=>'https://example.org','entityId'=>'https://example.org/#order-'.$name.'-'.bin2hex(random_bytes(4)),'schemaExtensionVersion'=>1,'schemaExtensionData'=>Internal\Store::encode(['capacity'=>['value'=>'2.00','unit'=>'C62','basis'=>'Graph order fixture']]),'published'=>'1']);$orderId=(int)$db->lastInsertId();$orderIds[]=$orderId;
  $db->insert('tl_schema_translation',['pid'=>$orderId,'page'=>(int)$page->id,'language'=>$page->language,'published'=>'1']);
 }
 $db->insert('tl_schema_extension_relation',['entity'=>$orderIds[0],'relationKey'=>'example.public_related','target'=>$orderIds[1]]);
 $stack=$c->get('request_stack');$request=Symfony\Component\HttpFoundation\Request::create('https://example.test/');$request->attributes->set('pageModel',$page);$stack->push($request);
 try{
  $entities=new VHUG\SchemaManagerBundle\Schema\EntityGraph($db,$c->get('contao.routing.content_url_generator'),$c->get('contao.cache.tag_manager'),new VHUG\SchemaManagerBundle\Schema\EntityMapper(),$stack,$orderRuntime);
  $manager=new Contao\CoreBundle\Routing\ResponseContext\JsonLd\JsonLdManager(new Contao\CoreBundle\Routing\ResponseContext\ResponseContext());$emitted=[];
  $entities->emit($orderIds[0],$page->language,$manager,$emitted);$second=$entities->emit($orderIds[1],$page->language,$manager,$emitted);
  $check(isset($second['additionalProperty']),'Direct home emission upgrades a previously compact supporting entity.');
  $serialized=$manager->getGraphForSchema($manager::SCHEMA_ORG)->toArray()['@graph'];
  $secondNode=array_values(array_filter($serialized,static fn($node)=>($node['@id']??'')===$second['@id']))[0];
  $check(isset($secondNode['additionalProperty']),'Graph serialization retains upgraded full home node.');
  $db->insert('tl_schema_entity',['name'=>'Native home organization','legalName'=>'Native home organization Ltd','entityType'=>'Organization','identityBase'=>'https://example.org','entityId'=>'https://example.org/#native-order-'.bin2hex(random_bytes(4)),'published'=>'1']);$native=(int)$db->lastInsertId();
  $db->insert('tl_schema_translation',['pid'=>$native,'page'=>(int)$page->id,'language'=>$page->language,'published'=>'1']);
  $db->insert('tl_schema_extension_relation',['entity'=>$orderIds[0],'relationKey'=>'example.manufacturer','target'=>$native]);
  $manager=new Contao\CoreBundle\Routing\ResponseContext\JsonLd\JsonLdManager(new Contao\CoreBundle\Routing\ResponseContext\ResponseContext());$emitted=[];
  $entities->emit($orderIds[0],$page->language,$manager,$emitted);$nativeNode=$entities->emit($native,$page->language,$manager,$emitted);
  $check(($nativeNode['legalName']??'')==='Native home organization Ltd','Native home entities upgrade contributed supporting references.');
  $serialized=$manager->getGraphForSchema($manager::SCHEMA_ORG)->toArray()['@graph'];
  $check(count(array_filter($serialized,static fn($node)=>($node['@id']??'')===$nativeNode['@id']))===1,'Native upgrade never leaves a duplicate generic node.');

 }finally{$stack->pop();}
 $db->delete('tl_schema_extension_relation',['entity'=>$orderIds[0]]);
 $db->insert('tl_schema_extension_relation',['entity'=>$orderIds[0],'relationKey'=>'example.related','target'=>$orderIds[1]]);
 $editorial=$orderRuntime->render($orderIds[0],$front);
 $check(count($editorial['nodes'])===1,'Editorial-only relationships never emit dependency nodes publicly.');
 $v2Types=new class($provider) implements Api\TypeProviderInterface {
  public function __construct(private $provider){}
  public function types(): iterable {foreach($this->provider->types() as $t)yield new Api\TypeDefinition($t->key,$t->label,$t->schemaTypes,$t->mapper,$t->fields,$t->relationships,schemaVersion:2,properties:$t->properties);}
 };
 $v2=new Internal\Registry([$v2Types],[$provider],[$adapter],[get_class($provider)=>$provider]);
 $migration=new class implements Api\PayloadMigrationInterface {
  public function typeKey():string{return 'example.equipment';}
  public function fromVersion():int{return 1;}
  public function toVersion():int{return 2;}
  public function global(array $v):array{$v['capacity']['basis']='Explicit migration';return $v;}
  public function localized(array $v,string $language):array{return $v;}
 };
 $migrator=new Internal\PayloadMigrator($db,$v2,$store,$validator,$invalidator,[$migration]);
 $dry=$migrator->migrate($ref->id);
 $check(!$dry['applied'] && (int)$store->record($ref->id)['schemaExtensionVersion']===1,'Migration dry run does not write.');
 $applied=$migrator->migrate($ref->id,true);
 $check($applied['applied'] && $store->record($ref->id)['entityId']===$identity && (int)$store->record($ref->id)['schemaExtensionVersion']===2,'Explicit migration preserves identity and advances version.');
 $v2Runtime=new Internal\Runtime($v2,$store,$validator,$bindings,new VHUG\SchemaManagerBundle\Schema\EntityMapper());
 $check($v2Runtime->render($ref->id,$front)['node']['additionalProperty']['name']==='Explicit migration','Migrated payload renders through new definition.');
 echo 'PASS: '.$checks." installed extension binding, source authority, rendering, lifecycle and graph checks\n";
} finally { $db->rollBack();unlink($path);echo "Extension integration fixtures rolled back.\n"; }
