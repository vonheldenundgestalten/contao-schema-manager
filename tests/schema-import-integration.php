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
 $user=Contao\BackendUser::getInstance();$user->id=3;$user->admin='1';$user->username='import-test';
 $security=new class implements Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface {public function isGranted(mixed $a,mixed $s=null):bool{return false;}};
 $inventory=new VHUG\SchemaManagerBundle\Ai\SiteInventory($db,new VHUG\SchemaManagerBundle\Backend\ContentMapSource($db,$c->get('contao.routing.content_url_generator'),$security));
 $data=$inventory->collect(2,$user,true);
 $sources=array_values(array_filter($data['sources'],fn($s)=>str_starts_with($s['id'],'page:')));
 $first=$sources[0];$second=null;
 foreach($sources as $s)if($s['language']!==$first['language']&&$data['pages'][$s['page']]['languageFamily']===$data['pages'][$first['page']]['languageFamily']){$second=$s;break;}
 $check((bool)$second,'Linked language fixture');
 $audit=new class($db,$inventory) extends VHUG\SchemaManagerBundle\Ai\SchemaAudit {public string $html='';public function fetch(string $url):string{return $this->html;}};
 $policy=new VHUG\SchemaManagerBundle\Ai\FieldPolicy(new VHUG\SchemaManagerBundle\EventListener\BusinessDetailsListener($db),new VHUG\SchemaManagerBundle\EventListener\DataContainerListener($db),new VHUG\SchemaManagerBundle\EventListener\EntityDetailsListener($db));
 $importer=new VHUG\SchemaManagerBundle\Ai\SchemaImport($db,$audit,$policy,$inventory);
 $run=['stage'=>'import','status'=>'complete','root'=>2,'origin'=>'https://example.org','inventory'=>$data,'importSources'=>[]];
 $suffix=bin2hex(random_bytes(6));$markup=fn($n)=>'<script type="application/ld+json">'.json_encode($n).'</script>';
 foreach([$first,$second] as $source){
  $node=['@context'=>'https://schema.org','@type'=>'Service','@id'=>'https://example.org/'.$source['language'].'/#'.$suffix,'name'=>'Import fixture '.$suffix,'url'=>$source['url'],'description'=>'Description '.$source['language'],'termsOfService'=>'https://example.org/terms'];
  $audit->html=$markup($node).$markup(['@context'=>'https://schema.org','@type'=>'WebPage','@id'=>$source['url'].'#webpage','name'=>'Core page']);
  $scan=$importer->scan($source);$check(count($scan['candidates'])===1,'Core-only WebPage excluded');$run['importSources'][$source['id']]=$scan;
 }
 $run['importPlan']=$importer->plan($run);$check(count($run['importPlan'])===1,'Linked localized services grouped once');
 $key=array_key_first($run['importPlan']);$g=$run['importPlan'][$key];$check(!$g['conflicts'],'Unambiguous import');$check(count($g['variants'])===2,'Both languages retained');
 $check($importer->apply($run,[$key],$user)===1,'Import reviewed group');$id=$run['importPlan'][$key]['record'];
 $check(!$db->fetchOne('SELECT published FROM tl_schema_entity WHERE id=?',[$id]),'Parent starts unpublished');
 $homes=$db->fetchAllAssociative('SELECT * FROM tl_schema_translation WHERE pid=?',[$id]);$check(count($homes)===2,'Localized drafts created');
 foreach($homes as $home){$check(!$home['published'],'Home starts unpublished');$retained=json_decode($home['schemaImportedData'],true);$check($retained['termsOfService']==='https://example.org/terms','Unsupported fact preserved');$check($retained['@id']==='https://example.org/'.$home['language'].'/#'.$suffix,'Localized ID preserved');$check($home['description']==='Description '.$home['language'],'Description mapped to editor');}
 $check($importer->publish($run,[$key],$user)===1,'Publish selected import');$check((int)$db->fetchOne("SELECT COUNT(*) FROM tl_schema_translation WHERE pid=? AND published='1'",[$id])===2,'Both homes published');
 $graph=new VHUG\SchemaManagerBundle\Schema\EntityGraph($db,$c->get('contao.routing.content_url_generator'),$c->get('contao.cache.tag_manager'),new VHUG\SchemaManagerBundle\Schema\EntityMapper(),$c->get('request_stack'));
 foreach([$first,$second] as $source){$manager=new Contao\CoreBundle\Routing\ResponseContext\JsonLd\JsonLdManager(new Contao\CoreBundle\Routing\ResponseContext\ResponseContext());$emitted=[];$output=$graph->emit($id,$source['language'],$manager,$emitted);$check($output['@id']==='https://example.org/'.$source['language'].'/#'.$suffix&&$output['termsOfService']==='https://example.org/terms','Rendered localized ID and retained facts');}
 $nativeEvent=['@context'=>'https://schema.org','@type'=>'Event','@id'=>$first['url'].'#event','name'=>'Native calendar event','url'=>$first['url']];
 $eventSource=$first;$eventSource['id']='event:999999';$audit->html=$markup($nativeEvent);$check(!$importer->scan($eventSource)['candidates'],'Native calendar fallback identity excluded');
 $pageNode=['@type'=>'WebPage','@id'=>$first['url'].'#original-page','name'=>'Legacy page','url'=>$first['url']];
 $pageRun=['stage'=>'import','status'=>'complete','root'=>2,'origin'=>'https://example.org','inventory'=>$data,'importSources'=>[$first['id']=>['source'=>$first,'candidates'=>[['node'=>$pageNode,'element'=>0]],'elements'=>[]]]];
 $pageRun['importPlan']=$importer->plan($pageRun);$pageKey=array_key_first($pageRun['importPlan']);$before=$db->fetchAssociative('SELECT * FROM tl_page WHERE id=?',[$first['page']]);
 $importer->apply($pageRun,[$pageKey],$user);$check($db->fetchAssociative('SELECT * FROM tl_page WHERE id=?',[$first['page']])===$before,'Page import is staged without public changes');
 $importer->publish($pageRun,[$pageKey],$user);$check($db->fetchOne('SELECT schemaImportedActive FROM tl_page WHERE id=?',[$first['page']])==='1','Explicit publication activates page import');
 $db->update('tl_schema_translation',['description'=>'Editor changed this'],['id'=>$homes[0]['id']]);$again=$importer->plan($run);$check((bool)$again[$key]['conflicts'],'Existing edited fields block overwrite');
 $native=['@type'=>'Service','@id'=>'https://example.org/new','name'=>'Edited','sameAs'=>['https://example.org/new-link']];
 $merged=VHUG\SchemaManagerBundle\Schema\ImportedSchema::merge($native,json_encode(['@id'=>'https://example.org/old','termsOfService'=>'https://example.org/terms','sameAs'=>['old','obsolete']]));
 $check($merged['@id']==='https://example.org/old'&&$merged['sameAs']===['https://example.org/new-link']&&isset($merged['termsOfService']),'Native lists replace, identity and unsupported facts survive');
 echo "PASS: core exclusion, multilingual grouping, editable fields, preserved IDs/data, drafts, publication and overwrite guard.\n";
} finally {$db->rollBack();echo "Import fixtures rolled back.\n";}
