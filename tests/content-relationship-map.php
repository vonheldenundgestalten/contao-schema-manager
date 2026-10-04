<?php
declare(strict_types=1);
require __DIR__.'/../src/Schema/RelationshipMap.php';
require __DIR__.'/../src/Schema/ContentRelationshipMap.php';
use VHUG\SchemaManagerBundle\Schema\RelationshipMap;
use VHUG\SchemaManagerBundle\Schema\ContentRelationshipMap;
$check=static function(bool $ok,string $why):void {if(!$ok)throw new RuntimeException($why);};
$entities=[];foreach([1=>'Organization',2=>'Person',3=>'Service'] as $id=>$type){$entities[]=['id'=>$id,'entityType'=>$type,'name'=>'Entity '.$id,'published'=>'1'];}
$pages=[['id'=>10,'_root'=>10,'type'=>'root','title'=>'Website','schemaWebsiteId'=>'https://example.org/#website','schemaPublisher'=>1,'published'=>1,'language'=>'de'],
 ['id'=>20,'_root'=>20,'type'=>'root','title'=>'English site','schemaWebsiteRoot'=>10,'published'=>'1','language'=>'en'],
 ['id'=>11,'_root'=>10,'type'=>'regular','title'=>'Home','published'=>1,'language'=>'de','url'=>'https://example.org/de/'],
 ['id'=>12,'_root'=>10,'type'=>'regular','title'=>'Reader','published'=>1,'language'=>'de','url'=>'','schemaEntities'=>[3]],
 ['id'=>21,'_root'=>20,'type'=>'regular','title'=>'Reader','published'=>'1','language'=>'en','url'=>'']];
$base=['_mode'=>'BlogPosting','_publisher'=>1,'_author'=>2,'_reader'=>12,'headline'=>'Post','published'=>'1','author'=>1,'mapAuthorName'=>'Core author','schemaIdentity'=>'https://example.org/#post','source'=>'default'];
$posts=[['id'=>31,'url'=>'https://example.org/de/post-a','schemaAbout'=>[3]]+$base,
 ['id'=>32,'_reader'=>21,'url'=>'https://example.org/en/post-a','languageMain'=>31]+$base,
 ['id'=>33,'_author'=>0,'url'=>'https://example.org/de/post-b']+$base,
 ['id'=>34,'_mode'=>'suppress']+$base,
 ['id'=>35,'_reader'=>999,'url'=>'https://example.org/restricted']+$base];
$translations=[['pid'=>3,'language'=>'de','page'=>11,'published'=>'1','isMainEntity'=>'1','name'=>'Leistung'],['pid'=>3,'language'=>'en','page'=>21,'published'=>'1','isMainEntity'=>'1','name'=>'Service']];
$map=(new ContentRelationshipMap())->extend((new RelationshipMap())->build($entities,$translations),$pages,$posts,$translations);
$nodes=array_column(array_column($map['nodes'],'data'),null,'id');$edges=array_column($map['edges'],'data');
$has=static fn($a,$b,$p)=>count(array_filter($edges,fn($e)=>$e['source']===$a&&$e['target']===$b&&$e['label']===$p))===1;
$check($nodes['site-10']['published'] && $nodes['page-11']['published'], 'Core integer publication flags');
$check(count(array_filter($nodes,fn($n)=>$n['type']==='WebSite'))===1,'Shared translated root must yield one WebSite');
$check($has('site-10','entity-1','publisher')&&$has('page-21','site-10','isPartOf'),'Website and translated page relationships');
$check($has('page-11','entity-3','mainEntity')&&$has('entity-3','page-11','mainEntityOfPage'),'Home page relationships');
$check($has('news-31','entity-2','author')&&$has('news-31','entity-1','publisher')&&$has('news-31','entity-3','about'),'Post connects author, publisher and service');
$check($has('news-32','news-31','translationOfWork'),'News translation relation');
$check($nodes['reader-31']['identity']!==$nodes['reader-33']['identity']&&$has('reader-31','news-31','mainEntity'),'Separate reader WebPages for distinct post URLs');
$check($has('news-33','core-author-1','author')&&!empty($nodes['core-author-1']['warnings']),'Unmapped core author is visible and distinct');
$check(!$nodes['news-31']['needsService']&&$nodes['news-32']['needsService'],'Author/publisher cannot hide missing service subject');
$check(!isset($nodes['reader-35'],$nodes['page-999']),'Do not expose inaccessible reader pages');
$check(!isset($nodes['reader-34'])&&in_array('suppressed',$nodes['news-34']['warnings'],true),'Suppressed archive state');
$check(count(array_unique(array_column($edges,'id')))===count($edges),'Unique relationship identifiers');
$empty=(new ContentRelationshipMap())->extend((new RelationshipMap())->build([],[]),[],[],[]);
$check($empty===['nodes'=>[],'edges'=>[]],'Empty and core-only content source');
$requiredPages=$pages;
foreach ($requiredPages as &$p) { if (in_array($p['id'],[12,21],true)) { $p['requireItem']=1; } } unset($p);
$required=(new ContentRelationshipMap())->extend((new RelationshipMap())->build($entities,$translations),$requiredPages,$posts,$translations);
$requiredNodes=array_column(array_column($required['nodes'],'data'),null,'id');
$requiredEdges=array_column($required['edges'],'data');
$check(!isset($requiredNodes['page-12']) && !isset($requiredNodes['page-21']), 'Require-item containers excluded');
$check(isset($requiredNodes['news-31'],$requiredNodes['reader-31'],$requiredNodes['reader-32'],$requiredNodes['page-11']), 'Posts, actual detail pages and ordinary pages retained');
$check($requiredNodes['news-32']['language']==='en', 'Reader language retained');
foreach ($requiredEdges as $edge) { $check(isset($requiredNodes[$edge['source']],$requiredNodes[$edge['target']]), 'No dangling container relationships'); }
$check(count(array_filter($requiredEdges,fn($e)=>$e['source']==='reader-31'&&$e['target']==='entity-3'&&$e['label']==='about'))===1, 'Detail page inherits reader subjects');
$noindexPages=$pages;
foreach ($noindexPages as &$p) { if ($p['id']===12) {$p['robots']='noindex,follow';} if ($p['id']===11) {$p['robots']='index,nofollow';} } unset($p);
$filtered=(new ContentRelationshipMap())->extend((new RelationshipMap())->build($entities,$translations),$noindexPages,$posts,$translations);
$filteredNodes=array_column(array_column($filtered['nodes'],'data'),null,'id');
$check(!isset($filteredNodes['page-12'],$filteredNodes['news-31'],$filteredNodes['reader-31'],$filteredNodes['news-33']),'Noindex reader and its posts excluded');
$check(isset($filteredNodes['page-11'],$filteredNodes['news-32'],$filteredNodes['entity-3']),'Nofollow and shared entities remain; no blanket language exclusion');
$check($nodes['news-32']['language']==='en' && $nodes['page-11']['language']==='de' && $nodes['entity-3']['language']==='','Content has language, business entities remain shared');
$check($nodes['site-10']['languages']===['de','en'],'Shared website spans both languages');
echo "PASS: website/page/blog graph, translated roots, author fallback, topics, per-post reader identity and permission-filtered references.\n";
