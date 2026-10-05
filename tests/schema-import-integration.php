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
 [$shared,$localized,$retained]=$importer->fields('Organization',['@type'=>'Organization','name'=>'Register fixture','areaServed'=>['@type'=>'AdministrativeArea','name'=>'Worldwide'],'identifier'=>['@type'=>'PropertyValue','name'=>'Commercial Register Estonia','value'=>'17334484'],'award'=>['Award 2026']]);
 $check($shared['areaServedWorldwide']==='1'&&$shared['award']==='Award 2026'&&!isset($shared['taxID'],$localized['award'],$retained['identifier'],$retained['areaServed']),'Legacy company facts map into their proper shared fields');
 $check(Contao\StringUtil::deserialize($shared['registrationIdentifiers'],true)===[['key'=>'Commercial Register Estonia','value'=>'17334484']],'Registration becomes editable register pair');
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
 $db->insert('tl_schema_entity',$shared+['entityType'=>'Organization','identityBase'=>'https://example.org','entityId'=>'https://example.org/#register-'.$suffix,'published'=>'1']);$company=(int)$db->lastInsertId();
 $db->insert('tl_schema_translation',['pid'=>$company,'page'=>$first['page'],'language'=>$first['language'],'published'=>'1']);
 $manager=new Contao\CoreBundle\Routing\ResponseContext\JsonLd\JsonLdManager(new Contao\CoreBundle\Routing\ResponseContext\ResponseContext());$emitted=[];$companyOutput=$graph->emit($company,$first['language'],$manager,$emitted);
 $check($companyOutput['areaServed']===['@type'=>'AdministrativeArea','name'=>'Worldwide']&&$companyOutput['identifier']===['@type'=>'PropertyValue','name'=>'Commercial Register Estonia','value'=>'17334484']&&!isset($companyOutput['taxID']),'Real entity graph reproduces the original company blocks without a tax ID');
 foreach([$first,$second] as $source){$manager=new Contao\CoreBundle\Routing\ResponseContext\JsonLd\JsonLdManager(new Contao\CoreBundle\Routing\ResponseContext\ResponseContext());$emitted=[];$output=$graph->emit($id,$source['language'],$manager,$emitted);$check($output['@id']==='https://example.org/'.$source['language'].'/#'.$suffix&&$output['termsOfService']==='https://example.org/terms','Rendered localized ID and retained facts');}
 $nativeEvent=['@context'=>'https://schema.org','@type'=>'Event','@id'=>$first['url'].'#event','name'=>'Native calendar event','url'=>$first['url']];
 $eventSource=$first;$eventSource['id']='event:999999';$audit->html=$markup($nativeEvent);$check(!$importer->scan($eventSource)['candidates'],'Native calendar fallback identity excluded');
 $localizedRun=['stage'=>'import','status'=>'complete','root'=>2,'origin'=>'https://example.org','inventory'=>$data,'importSources'=>[]];
 foreach([$first,$second] as $source){$language=$source['language'];$node=['@type'=>'Organization','@id'=>'https://example.org/#localized-'.$suffix,'name'=>'Localized company '.$suffix,'url'=>$source['url'],'sameAs'=>['https://example.org/'.$language.'/profile','https://example.org/universal'],'identifier'=>['@type'=>'PropertyValue','name'=>'Register '.$language,'value'=>'001734']];$localizedRun['importSources'][$source['id']]=['source'=>$source,'candidates'=>[['node'=>$node,'element'=>0]],'elements'=>[]];}
 $localizedRun['importPlan']=$importer->plan($localizedRun);$localizedKey=array_key_first($localizedRun['importPlan']);$check(!$localizedRun['importPlan'][$localizedKey]['conflicts'],'Translated register labels and profile links are not shared conflicts');
 $localizedRun['importPlan'][$localizedKey]['conflicts']=['Old shared-field error'];$importer->refreshPending($localizedRun);$check(!$localizedRun['importPlan'][$localizedKey]['conflicts'],'Old pending review refreshed');
 $importer->apply($localizedRun,[$localizedKey],$user);$importer->publish($localizedRun,[$localizedKey],$user);$localizedId=$localizedRun['importPlan'][$localizedKey]['record'];
 foreach([$first,$second] as $source){$manager=new Contao\CoreBundle\Routing\ResponseContext\JsonLd\JsonLdManager(new Contao\CoreBundle\Routing\ResponseContext\ResponseContext());$emitted=[];$out=$graph->emit($localizedId,$source['language'],$manager,$emitted);$check($out['identifier']['name']==='Register '.$source['language']&&$out['identifier']['value']==='001734'&&$out['sameAs']===['https://example.org/'.$source['language'].'/profile','https://example.org/universal'],'Localized labels and links rendered with stable registration number');}
 foreach(['WebPage','WebSite'] as $type){$webRun=$localizedRun;$webRun['importSources']=[];foreach([$first,$second] as $source){$node=['@type'=>$type,'@id'=>$type==='WebSite'?'https://example.org/#translated-site':$source['url'].'#'.$type,'name'=>'Title '.$source['language'],'description'=>'Description '.$source['language'],'url'=>$source['url']];$webRun['importSources'][$source['id']]=['source'=>$source,'candidates'=>[['node'=>$node,'element'=>0]],'elements'=>[]];}$webPlan=$importer->plan($webRun);$check(count($webPlan)===1&&!reset($webPlan)['conflicts'],'Localized '.$type.' names and descriptions do not create shared-field conflicts');}
 $pageNode=['@type'=>'WebPage','@id'=>$first['url'].'#original-page','name'=>'Legacy page','url'=>$first['url']];
 $pageRun=['stage'=>'import','status'=>'complete','root'=>2,'origin'=>'https://example.org','inventory'=>$data,'importSources'=>[$first['id']=>['source'=>$first,'candidates'=>[['node'=>$pageNode,'element'=>0]],'elements'=>[]]]];
 $pageRun['importPlan']=$importer->plan($pageRun);$pageKey=array_key_first($pageRun['importPlan']);$before=$db->fetchAssociative('SELECT * FROM tl_page WHERE id=?',[$first['page']]);
 $importer->apply($pageRun,[$pageKey],$user);$check($db->fetchAssociative('SELECT * FROM tl_page WHERE id=?',[$first['page']])===$before,'Page import is staged without public changes');
 $importer->publish($pageRun,[$pageKey],$user);$check($db->fetchOne('SELECT schemaImportedActive FROM tl_page WHERE id=?',[$first['page']])==='1','Explicit publication activates page import');
 $db->update('tl_schema_translation',['description'=>'Editor changed this'],['id'=>$homes[0]['id']]);$again=$importer->plan($run);$check((bool)$again[$key]['conflicts'],'Existing edited fields block overwrite');
 $siteHome=Contao\PageModel::findById($first['page']);$siteHome->loadDetails();$siteRoot=(int)$siteHome->rootId;
 $configured='https://example.org/#generated-'.$suffix;$original='https://example.org/#original-'.$suffix;
 $db->update('tl_page',['schemaWebsiteRoot'=>0,'schemaWebsiteId'=>$configured,'schemaImportedData'=>null,'schemaImportedActive'=>''],['id'=>$siteRoot]);
 $siteRun=['stage'=>'import','status'=>'complete','root'=>2,'origin'=>'https://example.org','inventory'=>$data,'importSources'=>[$first['id']=>['source'=>$first,'candidates'=>[['node'=>['@type'=>'WebSite','@id'=>$original,'name'=>'Original site','url'=>$first['url']],'element'=>0]],'elements'=>[]]]];
 $siteRun['importPlan']=$importer->plan($siteRun);$siteKey=array_key_first($siteRun['importPlan']);
 $check($siteRun['importPlan'][$siteKey]['websiteIdentityChanges'][$siteRoot]===['current'=>$configured,'original'=>$original],'Review shows configured and original website IDs');
 $importer->apply($siteRun,[$siteKey],$user);
 $check($db->fetchOne('SELECT schemaWebsiteId FROM tl_page WHERE id=?',[$siteRoot])===$configured,'Draft does not change website identity');
 $db->update('tl_page',['schemaWebsiteId'=>'https://example.org/#editor-change'],['id'=>$siteRoot]);
 try{$importer->publish($siteRun,[$siteKey],$user);throw new LogicException('Stale website identity overwritten');}catch(RuntimeException $expected){}
 $db->update('tl_page',['schemaWebsiteId'=>$configured],['id'=>$siteRoot]);
 $importer->publish($siteRun,[$siteKey],$user);
 $check($db->fetchOne('SELECT schemaWebsiteId FROM tl_page WHERE id=?',[$siteRoot])===$original,'Publishing restores original website identity');
 $settings=new VHUG\SchemaManagerBundle\EventListener\SourceSettingsListener($db);
 $dc=new class($siteRoot) extends Contao\DataContainer { public function __construct(int $id){$this->intId=$id;} public function getPalette(){return '';} protected function save($value){} };
 $check($settings->keepWebsiteIdentity('https://example.org/#website',$dc)==='https://example.org/#website','Original website ID can be entered in the backend');
 foreach(['javascript:alert(1)','https://user:secret@example.org/#website',''] as $bad){try{$settings->keepWebsiteIdentity($bad,$dc);throw new LogicException('Invalid website ID accepted');}catch(InvalidArgumentException){}}
 $secondHome=Contao\PageModel::findById($second['page']);$secondHome->loadDetails();$owner=(int)$secondHome->rootId;
 $db->update('tl_page',['schemaWebsiteRoot'=>0,'schemaWebsiteId'=>$configured,'schemaImportedData'=>null,'schemaImportedActive'=>''],['id'=>$owner]);
 $db->update('tl_page',['schemaWebsiteRoot'=>$owner],['id'=>$siteRoot]);
 $sharedRun=$siteRun;$sharedRun['importPlan']=$importer->plan($sharedRun);$sharedKey=array_key_first($sharedRun['importPlan']);
 $check(isset($sharedRun['importPlan'][$sharedKey]['websiteIdentityChanges'][$owner]),'Review resolves the actual shared website root');
 $importer->apply($sharedRun,[$sharedKey],$user);
 $check(isset($sharedRun['importPlan'][$sharedKey]['pageChanges'][$owner])&&!isset($sharedRun['importPlan'][$sharedKey]['pageChanges'][$siteRoot]),'Import stages the owning root rather than the language root');
 $importer->publish($sharedRun,[$sharedKey],$user);
 $check($db->fetchOne('SELECT schemaWebsiteId FROM tl_page WHERE id=?',[$owner])===$original,'Original identity reaches the root used for output');
 $native=['@type'=>'Service','@id'=>'https://example.org/new','name'=>'Edited','sameAs'=>['https://example.org/new-link']];
 $merged=VHUG\SchemaManagerBundle\Schema\ImportedSchema::merge($native,json_encode(['@id'=>'https://example.org/old','termsOfService'=>'https://example.org/terms','sameAs'=>['old','obsolete']]));
 $check($merged['@id']==='https://example.org/old'&&$merged['sameAs']===['https://example.org/new-link']&&isset($merged['termsOfService']),'Native lists replace, identity and unsupported facts survive');
 // A fresh scan with no import candidates must still compare existing published entities.
 $legacy=['@context'=>'https://schema.org','@type'=>'Organization','@id'=>'https://example.org/#handover-'.$suffix,'name'=>'Handover fixture','description'=>'Keep this fact'];
 $db->insert('tl_schema_entity',['entityType'=>'Organization','name'=>$legacy['name'],'entityId'=>$legacy['@id'],'published'=>'1']);
 $article=(int)$db->fetchOne('SELECT id FROM tl_article WHERE pid=?',[$first['page']]);
 $db->insert('tl_content',['pid'=>$article,'ptable'=>'tl_article','type'=>'html','html'=>$markup($legacy),'invisible'=>0]);$contentId=(int)$db->lastInsertId();
 $replacement=$legacy;$replacement['url']=$first['url'];
 $audit->html=$markup($legacy).$markup($replacement);
 $emptyRun=['stage'=>'import','status'=>'complete','importPlan'=>[],'inventory'=>$data];
 $importer->verify($emptyRun,$first['id']);
 $items=array_values(array_filter($emptyRun['auditResults'][$first['id']]['elements'],fn($item)=>$item['id']===$contentId));
 $check(count($items)===1&&$items[0]['ready']&&$items[0]['comparisons'][0]['replacement']['url']===$first['url'],'Empty fresh import supports independent original/replacement comparison');
 unset($replacement['description']);$audit->html=$markup($legacy).$markup($replacement);
 $importer->verify($emptyRun,$first['id']);
 $items=array_values(array_filter($emptyRun['auditResults'][$first['id']]['elements'],fn($item)=>$item['id']===$contentId));
 $check(!$items[0]['ready']&&$items[0]['comparisons'][0]['differences'],'Missing original facts block retirement');
 $audit->html=$markup($legacy);$importer->verify($emptyRun,$first['id']);
 $items=array_values(array_filter($emptyRun['auditResults'][$first['id']]['elements'],fn($item)=>$item['id']===$contentId));
 $check(!$items[0]['ready']&&$items[0]['comparisons'][0]['replacement']===null,'Original cannot serve as its own replacement');
 echo "PASS: core exclusion, multilingual grouping, editable fields, preserved IDs/data, drafts, publication and overwrite guard.\n";
} finally {$db->rollBack();echo "Import fixtures rolled back.\n";}
