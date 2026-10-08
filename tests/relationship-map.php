<?php
declare(strict_types=1);
require __DIR__.'/../src/Schema/RelationshipMap.php';
use VHUG\SchemaManagerBundle\Schema\RelationshipMap;
function entity(int $id, string $type, array $extra=[]): array { return $extra + ['id'=>$id,'entityType'=>$type,'name'=>'Record '.$id,'published'=>'1','organization'=>0]; }
$rows=[entity(1,'Organization',['locations'=>[2,2]]),entity(2,'LocalBusiness',['organization'=>1,'streetAddress'=>'Hauptstraße 3','postalCode'=>'70173','addressLocality'=>'Stuttgart']),
 entity(3,'Person',['organization'=>1,'knowledgeTopics'=>[4,4], 'workLocation'=>[2],'memberOf'=>[99]]),entity(4,'Service',['organization'=>1,'subservices'=>[5]]),entity(5,'Service',['subservices'=>[4]]),
 entity(6,'Product',['organization'=>1]),entity(7,'Event',['published'=>'']),entity(8,'Organization',['name'=>'</script><script>alert(1)</script>'])];
$result=(new RelationshipMap())->build($rows,[['pid'=>7,'page'=>10,'language'=>'de','published'=>'1','name'=>'An event']]);
$nodes=array_column(array_column($result['nodes'],'data'),null,'id');$edges=array_column($result['edges'],'data');
$check=static function(bool $ok,string $why):void {if(!$ok)throw new RuntimeException($why);};
$check(count($nodes)===9,'Keep every entity plus a missing reference');
$check($nodes['entity-99']['missing'] && !$nodes['entity-7']['published'],'Missing and draft states');
$check($nodes['entity-2']['detail']==='Hauptstraße 3, 70173 Stuttgart','Distinguish offices');
$check(count(array_filter($edges,fn($e)=>$e['source']==='entity-1'&&$e['target']==='entity-2'&&$e['label']==='location'))===1,'Deduplicate explicit and inferred locations');
$check(count(array_unique(array_column($edges,'id')))===count($edges),'Unique edge IDs');
foreach(['knowsAbout','worksFor','provider','offers.seller','workLocation','memberOf','parentOrganization','location','hasOfferCatalog.itemOffered'] as $property){$check(in_array($property,array_column($edges,'label'),true),'Missing '.$property);}
$check(count(array_filter($edges,fn($e)=>in_array('entity-7',[$e['source'],$e['target']],true)))===0,'A homepage must not hide an isolated entity');
$check(count($nodes['entity-7']['homes'])===1,'Localized home is available in details');
$check(count(array_filter($edges,fn($e)=>$e['source']==='entity-4'&&$e['target']==='entity-5'))===1,'Cycles remain visible without traversal recursion');
$softwareMap=(new RelationshipMap())->build([entity(10,'SoftwareApplication',['requiredSoftware'=>[11,11]]),entity(11,'SoftwareApplication',['published'=>''])],[]);
$softwareEdges=array_column($softwareMap['edges'],'data');
$check(count($softwareEdges)===1 && $softwareEdges[0]['source']==='entity-10' && $softwareEdges[0]['target']==='entity-11' && $softwareEdges[0]['label']==='softwareRequirements','Required software links point from extension to dependency, deduplicate, and retain drafts for editorial review');
$check((new RelationshipMap())->build([],[])===['nodes'=>[],'edges'=>[]],'Empty installation');
$payload=json_encode($result,JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_THROW_ON_ERROR);
$check(!str_contains($payload,'</script>'),'Safe script data embedding');
echo "PASS: relationships, direction, deduplication, disconnected/draft/missing nodes, locations, cycles and empty map.\n";
