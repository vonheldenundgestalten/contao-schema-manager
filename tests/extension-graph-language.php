<?php

declare(strict_types=1);
require getcwd().'/vendor/autoload.php';
$kernel=Contao\ManagerBundle\HttpKernel\ContaoKernel::fromInput(getcwd(),new Symfony\Component\Console\Input\ArgvInput());$kernel->boot();$c=$kernel->getContainer();$c->get('contao.framework')->initialize();$db=$c->get('database_connection');
use VHUG\SchemaManagerBundle\Extension\V1 as Api;
use VHUG\SchemaManagerBundle\Extension\Internal as Internal;
$root=$db->fetchAssociative("SELECT id,language FROM tl_page WHERE type='root' AND language!='' ORDER BY id LIMIT 1");$actor=(int)$db->fetchOne("SELECT id FROM tl_user WHERE admin='1' AND disable!='1' ORDER BY id LIMIT 1");
if(!$root||!$actor)throw new RuntimeException('Configured source root and administrator required.');
$checks=0;$check=static function(bool $ok,string $message)use(&$checks){if(!$ok)throw new RuntimeException($message);++$checks;};
$db->beginTransaction();
try {
 $token=bin2hex(random_bytes(8));$db->insert('tl_schema_entity',['name'=>'Synthetic graph target','entityType'=>'Organization','entityId'=>'https://example.test/#graph-target-'.$token,'identityBase'=>'https://example.test','published'=>'1']);$target=(int)$db->lastInsertId();
 $adapter=new class((string)$root['language'],$actor,$target) implements Api\SourceAdapterInterface,Api\RelationshipProviderInterface {
  public function __construct(public string $language,public int $actor,public int $target){}
  public function key():string{return 'test.graph_locale';}
  public function describe():Api\SourceDefinition{return new Api\SourceDefinition(['Person'],['name'],[],['test.graph_affiliation']);}
  public function relationships():iterable{yield new Api\RelationshipDefinition('test.graph_affiliation','Affiliation',['Person'],['Organization'],property:'affiliation');}
  public function findCandidates(Api\CandidateQuery $query):Api\CandidatePage{return new Api\CandidatePage([]);}
  public function resolve(Api\SourceReference $source,Api\ReadContext $context):Api\SourceResolution{
   if($context->purpose!=='frontend'&&$context->actorId!==$this->actor)return new Api\SourceResolution('forbidden');
   if($context->language!==$this->language)return new Api\SourceResolution('translation_missing');
   return new Api\SourceResolution('available',new Api\SourceSnapshot($source,'fixture-1','Person',['name'=>'Synthetic graph source'],[],relationships:['test.graph_affiliation'=>[new Api\RelationshipValue(new Api\EntityReference($this->target))]]));
  }
 };
 $registry=new Internal\Registry([],[$adapter],[$adapter]);$invalidator=new class implements Api\SourceInvalidatorInterface{public function changed(Api\SourceReference $source):void{}public function adapterChanged(string $adapterKey):void{}};
 $bindings=new Internal\BindingManager($db,$registry,$invalidator);$store=new Internal\Store($db);$projection=new Internal\GraphProjection($registry,$store,$bindings,$db);
 $source=new Api\SourceReference($adapter->key(),(string)$root['id'],'synthetic-'.$token);$context=new Api\ReadContext('preview',(string)$root['id'],$root['language'],$actor,true);
 $entity=$bindings->bind(new Api\BindRequest($source,'fixture-1','Person'),new Api\WriteContext($context,'https://example.test'));
 $rows=$db->fetchAllAssociative('SELECT * FROM tl_schema_entity WHERE id IN (?,?)',[$entity->id,$target]);$otherLanguage=$root['language']==='en'?'de':'en';
 $filtered=$projection->filter($rows,$actor,$otherLanguage);$check(count($filtered)===2,'UI language does not remove source-backed identities.');
 $base=(new VHUG\SchemaManagerBundle\Schema\RelationshipMap())->build($filtered,[]);$map=$projection->extend($base,$filtered,$otherLanguage,$actor);
 $check(count($map['edges'])===1&&$map['edges'][0]['data']['target']==='entity-'.$target,'Source-owned relationships use the actual source root language.');
 $check($adapter->resolve($source,new Api\ReadContext('frontend',(string)$root['id'],$otherLanguage))->status==='translation_missing','The graph fix does not fabricate a frontend translation.');
 $check(count($projection->filter($rows,$actor+999999,$otherLanguage))===1,'Source authorization remains enforced across UI locales.');
 echo 'PASS: '.$checks." source graph language and authorization checks.\n";
} finally {$db->rollBack();echo "Graph language fixtures rolled back.\n";}
