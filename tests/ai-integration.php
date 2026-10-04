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
$keyPath=sys_get_temp_dir().'/schema-ai-test-'.bin2hex(random_bytes(6));mkdir($keyPath,0700);mkdir($keyPath.'/var',0700);
try {
 file_put_contents($keyPath.'/.env.local',"KEEP_ME='preserved'\n");
 $keys=new VHUG\SchemaManagerBundle\Ai\ApiKeyStore($keyPath);$fake='sk-'.str_repeat('x',40);$keys->save($fake);
 $check($keys->get()===$fake && str_contains(file_get_contents($keyPath.'/.env.local'),"KEEP_ME='preserved'"),'Key roundtrip preserves unrelated settings');
 $keys->save('sk-'.str_repeat('y',40));$check(substr_count(file_get_contents($keyPath.'/.env.local'),'SCHEMA_AI_API_KEY=')===1,'Key replacement has one declaration');
 try{$keys->save("sk-invalid\nINJECT=yes");throw new LogicException('Invalid key accepted');}catch(InvalidArgumentException $expected){}
 $mock=new Symfony\Component\HttpClient\MockHttpClient(function($method,$url,$options)use($check){
   $body=json_decode($options['body'],true);
   $check($url==='https://api.openai.com/v1/responses' && $body['model']==='gpt-6.1-sol' && $body['store']===false,'Fixed provider/model and no response storage');
   return new Symfony\Component\HttpClient\Response\MockResponse(json_encode(['status'=>'completed','usage'=>['input_tokens'=>100,'output_tokens'=>50],'output'=>[['content'=>[['type'=>'output_text','text'=>'{"suggestions":[]}']]]]]));
 });
 $result=(new VHUG\SchemaManagerBundle\Ai\OpenAiProvider($mock,$keys))->analyze([]);$check($result['usage']['input_tokens']===100 && $result['suggestions']===[],'Structured output and usage parsed');
}finally{foreach(glob($keyPath.'/var/*') as $f)unlink($f);rmdir($keyPath.'/var');unlink($keyPath.'/.env.local');rmdir($keyPath);}
$db->beginTransaction();
try {
 $user=Contao\BackendUser::getInstance();$user->id=1;$user->username='schema-ai-test';$user->admin='1';
 $biz=new VHUG\SchemaManagerBundle\EventListener\BusinessDetailsListener($db);
 $policy=new VHUG\SchemaManagerBundle\Ai\FieldPolicy($biz,new VHUG\SchemaManagerBundle\EventListener\DataContainerListener($db),new VHUG\SchemaManagerBundle\EventListener\EntityDetailsListener($db));
 $engine=new VHUG\SchemaManagerBundle\Ai\ProposalEngine($db,$policy,$c->get('contao.cache.tag_manager'));
 $page=null;foreach($db->fetchFirstColumn("SELECT id FROM tl_page WHERE type='regular' AND published='1' AND requireItem='' ORDER BY id") as $id){$candidate=Contao\PageModel::findById($id);$candidate->loadDetails();if(!$candidate->protected){$page=$candidate;break;}}
 $check((bool)$page,'Public page fixture');
 $row=$page->row();$row['language']=$page->language;
 $source=['id'=>'page:'.$page->id,'title'=>'AI test','text'=>'Test company provides reliable technical support.','hash'=>'fixture','language'=>$page->language];
 $run=['mode'=>'discover','root'=>(int)$page->rootId,'origin'=>'https://example.org','inventory'=>['records'=>[],'pages'=>[$page->id=>$row]],'mapped'=>[],'proposals'=>[],'decisions'=>[]];
 $proposal=static fn($action,$target,$field,$value)=>['action'=>$action,'target'=>$target,'field'=>$field,'value'=>$value,'source'=>$source['id'],'quote'=>$source['text'],'reason'=>'Test evidence'];
 $name='AI fixture '.bin2hex(random_bytes(6));
 $engine->ingest($run,[$proposal('create','new:test','Service',$name),$proposal('home','new:test','page','page:'.$page->id),$proposal('set','new:test@page:'.$page->id,'description','Reliable technical support.')],[$source['id']=>$source]);
 $check(count($run['proposals'])===3 && array_column($run['proposals'],'status')===['pending','pending','pending'],'Evidence and dependent fields validate');
 $linked=$run;$linked['inventory']['records']['page:'.$page->id]=$row;
 $engine->ingest($linked,[$proposal('add','page:'.$page->id,'schemaEntities','new:test')],[$source['id']=>$source]);
 $check(end($linked['proposals'])['status']==='pending','Discovery can link an existing page to the new entity');
 $engine->ingest($linked,[$proposal('set','page:'.$page->id,'schemaPageType','AboutPage')],[$source['id']=>$source]);
 $check(end($linked['proposals'])['status']==='invalid','Discovery still rejects unrelated edits to existing pages');
 // A fresh page fixture exercises Apply all with DB NULL versus an inventory array.
 $db->insert('tl_page',['pid'=>(int)$page->rootId,'type'=>'regular','title'=>'AI relationship test','alias'=>'ai-relationship-'.bin2hex(random_bytes(6)),'published'=>0,'schemaEntities'=>null]);
 $relationPage=(int)$db->lastInsertId();$relationRow=$db->fetchAssociative('SELECT * FROM tl_page WHERE id=?',[$relationPage]);$relationRow['schemaEntities']=[];
 $run['inventory']['records']['page:'.$relationPage]=$relationRow;
 $engine->ingest($run,[$proposal('add','page:'.$relationPage,'schemaEntities','new:test')],[$source['id']=>$source]);
 $check(count($run['proposals'])===4 && end($run['proposals'])['old']===[],'Empty array relationship snapshot retained');
 $engine->apply($run,[0,1,2,3],$user);
 $check(Contao\StringUtil::deserialize($db->fetchOne('SELECT schemaEntities FROM tl_page WHERE id=?',[$relationPage]),true)===[(int)$run['mapped']['new:test']],'Apply all accepts NULL and empty array as equivalent');
 // A normalized snapshot must also compare with a serialized nonempty list.
 $relationSnapshot=$run;$relationSnapshot['mode']='improve';$relationSnapshot['proposals']=[];
 $entityId=(int)$run['mapped']['new:test'];
 $relationSnapshot['inventory']['records']['entity:'.$entityId]=$db->fetchAssociative('SELECT * FROM tl_schema_entity WHERE id=?',[$entityId]);
 $relationSnapshot['inventory']['records']['page:'.$relationPage]['schemaEntities']=[$entityId];
 $db->insert('tl_schema_entity',['name'=>'Second AI test service','entityType'=>'Service','identityBase'=>'https://example.org','entityId'=>'https://example.org/#test-'.bin2hex(random_bytes(6)),'published'=>'']);
 $otherEntity=(int)$db->lastInsertId();$relationSnapshot['inventory']['records']['entity:'.$otherEntity]=$db->fetchAssociative('SELECT * FROM tl_schema_entity WHERE id=?',[$otherEntity]);
 $engine->ingest($relationSnapshot,[$proposal('add','page:'.$relationPage,'schemaEntities','entity:'.$otherEntity)],[$source['id']=>$source]);
 $engine->apply($relationSnapshot,[0],$user);
 $check(count(Contao\StringUtil::deserialize($db->fetchOne('SELECT schemaEntities FROM tl_page WHERE id=?',[$relationPage]),true))===2,'Serialized relationship matches array snapshot without losing existing links');
 $staleRelations=$relationSnapshot;$staleRelations['proposals'][0]['status']='pending';
 $db->update('tl_page',['schemaEntities'=>serialize([$otherEntity])],['id'=>$relationPage]);
 try{$engine->apply($staleRelations,[0],$user);throw new LogicException('Changed relationship accepted');}catch(RuntimeException $expected){$check(str_contains($expected->getMessage(),'changed since'),'Real relationship edits remain protected');}

 $entity=(int)$run['mapped']['new:test'];$translation=(int)$run['mapped']['new:test@'.$page->id];
 $check($db->fetchOne('SELECT published FROM tl_schema_entity WHERE id=?',[$entity])==='' && $db->fetchOne('SELECT published FROM tl_schema_translation WHERE id=?',[$translation])==='','Drafts stay unpublished');
 $check($db->fetchOne('SELECT description FROM tl_schema_translation WHERE id=?',[$translation])==='Reliable technical support.','Dependent translation field saved');
 $check((int)$db->fetchOne("SELECT COUNT(*) FROM tl_version WHERE fromTable='tl_schema_translation' AND pid=?",[$translation])>0,'Version history created');
 $bad=$proposal('set','new:test','identityBase','https://attacker.example');$engine->ingest($run,[$bad],[$source['id']=>$source]);$check(end($run['proposals'])['status']==='invalid','Identity mutation rejected');
 $bad=$proposal('set','new:test','name','Another');$bad['quote']='not in this source';$engine->ingest($run,[$bad],[$source['id']=>$source]);$check(end($run['proposals'])['status']==='invalid','Unfounded evidence rejected');
 $run2=$run;$run2['mode']='improve';$run2['proposals']=[];$run2['inventory']['records']['entity:'.$entity]=$db->fetchAssociative('SELECT * FROM tl_schema_entity WHERE id=?',[$entity]);
 $engine->ingest($run2,[$proposal('set','entity:'.$entity,'name','Changed title')],[$source['id']=>$source]);
 $db->update('tl_schema_entity',['name'=>'Manual edit'],['id'=>$entity]);
 try{$engine->apply($run2,[0],$user);throw new LogicException('Stale write accepted');}catch(RuntimeException $expected){$check(str_contains($expected->getMessage(),'changed since'),'Stale writes detected');}
 $store=new VHUG\SchemaManagerBundle\Ai\RunStore($db);
 $run['processed']=['discover:page:1'=>'same'];$run['proposals'][0]['status']='rejected';
 $runId=$store->create(1,(int)$page->rootId,$run);
 $check($store->get($runId,1)['processed']===$run['processed'],'Private run roundtrip');
 try{$store->get($runId,2);throw new LogicException('Other user accessed run');}catch(RuntimeException $expected){}
 $previous=$store->previous(1,(int)$page->rootId);$check(isset($previous['decisions'][$run['proposals'][0]['fingerprint']]),'Rejection memory retained');
 $security=new class implements Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface {public function isGranted(mixed $attribute,mixed $subject=null):bool{return false;}};
 $content=new VHUG\SchemaManagerBundle\Backend\ContentMapSource($db,$c->get('contao.routing.content_url_generator'),$security);
 $inventory=(new VHUG\SchemaManagerBundle\Ai\SiteInventory($db,$content))->collect((int)$page->rootId,$user);
 $check(count($inventory['sources'])>0,'Public source inventory collected');
 // Publication checks use raw DB state even if Contao models were loaded earlier.
 $siteInventory=new VHUG\SchemaManagerBundle\Ai\SiteInventory($db,$content);
 $fixturePage=(int)array_key_first($inventory['pages']);
 $containerChildren=0;
 foreach($db->fetchAllAssociative("SELECT child.id,parent.id AS parentId,parent.protected FROM tl_page child JOIN tl_page parent ON parent.id=child.pid WHERE child.published=1 AND child.type='regular' AND parent.type='regular' AND parent.published=0 AND parent.protected=0") as $child){
  if(!isset($inventory['sources']['page:'.$child['id']]))continue;
  ++$containerChildren;
  $db->update('tl_page',['protected'=>1],['id'=>$child['parentId']]);
  $check(!isset($siteInventory->collect((int)$page->rootId,$user)['sources']['page:'.$child['id']]),'Parent protection still excludes published children');
  $db->update('tl_page',['protected'=>$child['protected']],['id'=>$child['parentId']]);
 }
 $check($containerChildren>0,'Published pages below unpublished navigation containers remain sources');

 foreach([['published'=>0],['start'=>(string)(time()+3600)],['stop'=>(string)(time()-3600)],['protected'=>'1']] as $change){
  $original=$db->fetchAssociative('SELECT * FROM tl_page WHERE id=?',[$fixturePage]);$db->update('tl_page',$change,['id'=>$fixturePage]);
  $filtered=$siteInventory->collect((int)$page->rootId,$user);
  $check(!isset($filtered['sources']['page:'.$fixturePage])&&!isset($filtered['pages'][$fixturePage]),'Inactive/scheduled/protected page excluded from sources and homes');
  $db->update('tl_page',array_intersect_key($original,$change),['id'=>$fixturePage]);
 }
 $check(!isset($inventory['records']['entity:'.$entity]),'Draft schema identity excluded from AI context');
 $reserved=array_column($inventory['identities'],null,'id');
 $check(isset($reserved[$entity])&&!isset($reserved[$entity]['description']),'Draft identity reserved without unpublished content');
 $duplicateRun=['mode'=>'discover','inventory'=>$inventory,'proposals'=>[],'mapped'=>[],'decisions'=>[]];
 $engine->ingest($duplicateRun,[$proposal('create','new:duplicate','Service','  MANUAL   EDIT  ')],[$source['id']=>$source]);
 $check($duplicateRun['proposals'][0]['status']==='invalid','Duplicate draft rejected despite case/whitespace differences');
 $context=$engine->context($duplicateRun,[$source]);
 $check(!isset($context['records']['entity:'.$entity])&&count($context['reservedIdentities'])>0,'Draft reservation is separate from active editable context');

 $db->insert('tl_article',['pid'=>$fixturePage,'title'=>'Active article fixture','published'=>1,'inColumn'=>'main']);$articleId=(int)$db->lastInsertId();
 $sentinel='UNPUBLISHED_SENTINEL_'.bin2hex(random_bytes(6));
 $db->insert('tl_content',['pid'=>$articleId,'ptable'=>'tl_article','type'=>'text','text'=>$sentinel,'invisible'=>0]);$elementId=(int)$db->lastInsertId();
 $readText=static fn()=> $siteInventory->collect((int)$page->rootId,$user)['sources']['page:'.$fixturePage]['text'];
 $check(str_contains($readText(),$sentinel),'Active element is included');
 foreach([['invisible'=>1],['start'=>(string)(time()+3600)],['stop'=>(string)(time()-3600)]] as $change){
  $original=$db->fetchAssociative('SELECT * FROM tl_content WHERE id=?',[$elementId]);$db->update('tl_content',$change,['id'=>$elementId]);
  $check(!str_contains($readText(),$sentinel),'Invisible or scheduled element excluded');
  $db->update('tl_content',array_intersect_key($original,$change),['id'=>$elementId]);
 }
 $db->update('tl_article',['published'=>0],['id'=>$articleId]);
 $check(!str_contains($readText(),$sentinel),'Unpublished article excludes its active children');
 $db->delete('tl_content',['id'=>$elementId]);$db->delete('tl_article',['id'=>$articleId]);

 foreach($inventory['sources'] as $key=>$item){if(str_starts_with($key,'news:')){
  $newsId=(int)substr($key,5);$original=$db->fetchAssociative('SELECT * FROM tl_news WHERE id=?',[$newsId]);
  foreach([['published'=>0],['start'=>(string)(time()+3600)],['stop'=>(string)(time()-3600)]] as $change){
   $db->update('tl_news',$change,['id'=>$newsId]);$filtered=$siteInventory->collect((int)$page->rootId,$user);
   $check(!isset($filtered['sources'][$key])&&!isset($filtered['records'][$key]),'Inactive/scheduled news excluded');
   $db->update('tl_news',array_intersect_key($original,$change),['id'=>$newsId]);
  }
  $archive=$db->fetchAssociative('SELECT * FROM tl_news_archive WHERE id=?',[$original['pid']]);
  $db->update('tl_news_archive',['schemaType'=>''],['id'=>$original['pid']]);
  $defaultInventory=$siteInventory->collect((int)$page->rootId,$user);
  $check(isset($defaultInventory['records'][$key])&&$defaultInventory['records'][$key]['headline']===$original['headline']&&$defaultInventory['records'][$key]['_mode']==='NewsArticle','Default archive news is a named editable target');
  $db->update('tl_news_archive',['schemaType'=>'suppress'],['id'=>$original['pid']]);
  $check(!isset($siteInventory->collect((int)$page->rootId,$user)['records'][$key]),'Suppressed news is not an editable schema target');
  $db->update('tl_news_archive',['schemaType'=>$archive['schemaType']],['id'=>$original['pid']]);

  $db->update('tl_news_archive',['protected'=>'1'],['id'=>$original['pid']]);
  $check(!isset($siteInventory->collect((int)$page->rootId,$user)['sources'][$key]),'Protected archive news excluded');
  $db->update('tl_news_archive',['protected'=>$archive['protected']],['id'=>$original['pid']]);break;
 }}

 $multiInventory=(new VHUG\SchemaManagerBundle\Ai\SiteInventory($db,$content))->collect((int)$page->rootId,$user,true);
 $pair=[];
 foreach($multiInventory['pages'] as $a){foreach($multiInventory['pages'] as $b){if($a['language']!==$b['language'] && $a['languageFamily']===$b['languageFamily']){$pair=[$a,$b];break 2;}}}
 $check(count($pair)===2,'Pilot has linked published language pages');
 [$firstPage,$secondPage]=$pair;
 $multiSource=['id'=>'page:'.$firstPage['id'],'page'=>(int)$firstPage['id'],'title'=>'Translation fixture','text'=>mb_substr($multiInventory['sources']['page:'.$firstPage['id']]['text'],0,200),'hash'=>'multi-fixture','language'=>$firstPage['language']];
 $multi=['mode'=>'discover','root'=>(int)$firstPage['_root'],'origin'=>'https://example.org','inventory'=>['records'=>[],'pages'=>[$firstPage['id']=>$firstPage,$secondPage['id']=>$secondPage],'roots'=>[(int)$firstPage['_root'],(int)$secondPage['_root']],'multilingual'=>true,'sources'=>[$multiSource['id']=>$multiSource]],'mapped'=>[],'proposals'=>[],'decisions'=>[],'warnings'=>[]];
 $mp=static fn($action,$target,$field,$value)=>['action'=>$action,'target'=>$target,'field'=>$field,'value'=>$value,'source'=>$multiSource['id'],'quote'=>$multiSource['text'],'reason'=>'Translation fixture'];
 $multiName='Shared multilingual fixture '.bin2hex(random_bytes(6));
 $engine->ingest($multi,[$mp('create','new:multi','Service',$multiName),$mp('home','new:multi','page',(string)$firstPage['id']),$mp('set','new:multi@'.$firstPage['id'],'description','Reliable website maintenance.')],[$multiSource['id']=>$multiSource]);
 $tasks=$engine->localizationTasks($multi);
 $check(count($tasks)===1 && $tasks[0]['missingPages']===[(int)$secondPage['id']],'Missing linked language is scheduled');
 $translationPending=$multi;
 $multi['_localizationTask']=$tasks[0];
 $engine->ingest($multi,[$mp('home','new:multi','page',(string)$secondPage['id']),$mp('set','new:multi@'.$secondPage['id'],'description','Zuverlaessige Wartung der Website.')],[$multiSource['id']=>$multiSource]);
 $check(array_column($multi['proposals'],'status')===array_fill(0,5,'pending'),'Localized output accepts original-language evidence only in the translation task');
 $engine->ingest($multi,[$mp('set','new:multi','name','Unwanted translated identity')],[$multiSource['id']=>$multiSource]);
 $check(end($multi['proposals'])['status']==='invalid','Translation phase cannot change the shared identity');
 unset($multi['_localizationTask']);
 $engine->apply($multi,[0,1,2,3,4],$user);
 $multiId=$multi['mapped']['new:multi'];
 $homes=$db->fetchAllAssociative('SELECT * FROM tl_schema_translation WHERE pid=?',[$multiId]);
 $check(count($homes)===2 && count(array_unique(array_column($homes,'language')))===2,'One entity gets two distinct language homes');
 $check($db->fetchOne('SELECT name FROM tl_schema_entity WHERE id=?',[$multiId])===$multiName && !array_filter($homes,static fn($home)=>!empty($home['published'])),'Shared identity unchanged and localized homes stay unpublished');
 $existing=$multi;$existing['proposals']=[];$existing['inventory']['records']['entity:'.$multiId]=$db->fetchAssociative('SELECT * FROM tl_schema_entity WHERE id=?',[$multiId]);
 foreach($homes as $home)$existing['inventory']['records']['translation:'.$home['id']]=$home;
 $check($engine->localizationTasks($existing)===[],'Existing translations are never scheduled for overwrite');
 $withoutLink=$multi;$withoutLink['proposals']=array_slice($withoutLink['proposals'],0,3);$withoutLink['inventory']['pages'][$secondPage['id']]['languageFamily']=999999;
 $check($engine->localizationTasks($withoutLink)===[] && count($withoutLink['warnings'])>0,'Missing counterpart produces a notice instead of a guessed page');

 $check((bool)json_encode($inventory,JSON_THROW_ON_ERROR),'Inventory is JSON-safe and excludes binary fields');
 foreach($inventory['records'] as $record){$check(!array_key_exists('image',$record),'Unrelated binary fields excluded');}

 $planner=new VHUG\SchemaManagerBundle\Ai\SetupPlanner($db,$siteInventory,$store);
 $setupId=$planner->prepare($user,(int)$page->rootId,true);$setup=$store->get($setupId,1);
 $check($setup['configuration']&&$setup['queue']===[]&&$setup['usage']['input_tokens']===0,'Parent setup is a no-API review');
 $check(count(array_filter(array_keys($setup['inventory']['records']),static fn($key)=>str_starts_with($key,'root:')))===count($setup['inventory']['roots']),'Parent review includes each language root');
 $rootIndex=null;foreach($setup['proposals'] as $index=>$item){if($item['field']==='schemaSiteName'){$rootIndex=$index;break;}}
 $check($rootIndex!==null,'Website name is reviewable');
 $setup['proposals'][$rootIndex]['value']='Reviewed setup fixture';
 $engine->apply($setup,[$rootIndex],$user);
 $check($db->fetchOne('SELECT schemaSiteName FROM tl_page WHERE id=?',[(int)substr($setup['proposals'][$rootIndex]['target'],5)])==='Reviewed setup fixture','Selected parent field applies through versioned review');
 $foundation=$run;$foundation['stage']='foundation';$foundation['proposals']=[];
 $engine->ingest($foundation,[$proposal('create','new:outside-stage','Service','Outside foundation')],[$source['id']=>$source]);
 $check($foundation['proposals'][0]['status']==='invalid','Foundation cannot propose services or child content');
 $publisherIndex=null;foreach($setup['proposals'] as $index=>$item){if($item['field']==='schemaPublisher'){$publisherIndex=$index;break;}}
 $check($publisherIndex!==null,'Parent publisher is reviewable');
 $invalidPublisher=$setup;$invalidPublisher['proposals'][$publisherIndex]['value']=(string)$entity;
 try{$engine->apply($invalidPublisher,[$publisherIndex],$user);throw new LogicException('Service accepted as publisher');}catch(RuntimeException $expected){$check(str_contains($expected->getMessage(),'publisher'),'Parent publisher must be an organization');}
 $staleSetup=$planner->prepare($user,(int)$page->rootId,false);$staleParent=$store->get($staleSetup,1);
 foreach($staleParent['proposals'] as $index=>$item){if($item['field']!=='schemaSiteName')continue;
  $target=(int)substr($item['target'],5);$beforeName=$db->fetchOne('SELECT schemaSiteName FROM tl_page WHERE id=?',[$target]);$db->update('tl_page',['schemaSiteName'=>'Changed after review'],['id'=>$target]);
  try{$engine->apply($staleParent,[$index],$user);throw new LogicException('Stale parent update accepted');}catch(RuntimeException $expected){$check(str_contains($expected->getMessage(),'changed since'),'Concurrent parent edits are protected');}
  $db->update('tl_page',['schemaSiteName'=>$beforeName],['id'=>$target]);break;
 }

 $keyPath=sys_get_temp_dir().'/schema-ai-runner-'.bin2hex(random_bytes(6));mkdir($keyPath,0700);mkdir($keyPath.'/var',0700);
 try {
  $keys=new VHUG\SchemaManagerBundle\Ai\ApiKeyStore($keyPath);$keys->save('sk-'.str_repeat('z',40));
  $provider=new VHUG\SchemaManagerBundle\Ai\OpenAiProvider(new Symfony\Component\HttpClient\MockHttpClient(new Symfony\Component\HttpClient\Response\MockResponse('{"status":"completed","usage":{"input_tokens":10,"output_tokens":5},"output":[{"content":[{"type":"output_text","text":"{\\"suggestions\\":[]}"}]}]}')),$keys);
  $runner=new VHUG\SchemaManagerBundle\Ai\AnalysisRunner($db,$store,new VHUG\SchemaManagerBundle\Ai\SiteInventory($db,$content),$provider,$engine,new VHUG\SchemaManagerBundle\Ai\PublicTextFetcher(),new VHUG\SchemaManagerBundle\Ai\SchemaAudit($db,$siteInventory),new VHUG\SchemaManagerBundle\Ai\SchemaImport($db,new VHUG\SchemaManagerBundle\Ai\SchemaAudit($db,$siteInventory),$policy,$siteInventory));
  $newRun=$runner->start($user,(int)$page->rootId,'improve','https://example.org',false);
  $started=$store->get($newRun,1);$check($started['status']==='ready' && count($started['queue'])>0,'Full analysis run prepares without provider use');
  // Blank URLs ensure this mock test makes no public fetches either.
  foreach($started['inventory']['sources'] as &$src){$src['url']='';}unset($src);$store->save($newRun,1,$started);
  $stale=$started;$stale['inventory']['multilingual']=false;$stale['localizationPrepared']=true;
  $staleKey='page:'.$fixturePage;$stale['queue']=[$staleKey];$stale['inventory']['sources']=[$staleKey=>$inventory['sources'][$staleKey]];
  $staleId=$store->create(1,(int)$page->rootId,$stale);
  $originalPublished=$db->fetchOne('SELECT published FROM tl_page WHERE id=?',[$fixturePage]);$db->update('tl_page',['published'=>0],['id'=>$fixturePage]);
  $skipped=$runner->step($staleId,$user);
  $check($skipped['done'] && $store->get($staleId,1)['usage']['input_tokens']===0,'Source disabled after preparation is skipped without an AI call');
  $db->update('tl_page',['published'=>$originalPublished],['id'=>$fixturePage]);
  $cursor=$runner::cursor($started);
  $working=$started;$working['status']='working';$working['workingAt']=time();$store->save($newRun,1,$working);
  $busy=$runner->step($newRun,$user,$cursor);
  $check($busy['busy'] && !$busy['paused'] && $store->get($newRun,1)['usage']['input_tokens']===0,'Concurrent request waits without provider usage');
  $check($runner->status($newRun,$user)===$busy,'Status reads the same saved progress without changing the run');
  $working['workingAt']=time()-181;$store->save($newRun,1,$working);
  $check($runner->status($newRun,$user)['paused'],'Expired worker requires an explicit retry');
  $store->save($newRun,1,$started);
  $response=$runner->step($newRun,$user);$after=$store->get($newRun,1);
  $check($after['usage']['input_tokens']===10 && count($after['queue'])<count($started['queue']),'Resumable batch advances and records usage');
  $duplicate=$runner->step($newRun,$user,$cursor);
  $check($duplicate['cursor']!==$cursor && $store->get($newRun,1)['usage']['input_tokens']===10,'Late duplicate cannot start the next paid batch');
  $after['status']='complete';$after['queue']=[];$store->save($newRun,1,$after);
  $before=$store->get($newRun,1);
  $translationPending+=['status'=>'ready','queue'=>[],'processed'=>[],'usage'=>['input_tokens'=>0,'output_tokens'=>0],'localizationPrepared'=>true,'localizationQueue'=>$tasks,'localizationTotal'=>1];
  $translationId=$store->create(1,$translationPending['root'],$translationPending);
  $localHttp=new Symfony\Component\HttpClient\MockHttpClient(function($method,$url,$options)use($check,$mp,$secondPage){
    $ctx=json_decode(json_decode($options['body'],true)['input'],true);
    $check($ctx['mode']==='localize' && $ctx['localizationTask']['target']==='new:multi','Runner sends a constrained localization request');
    return new Symfony\Component\HttpClient\Response\MockResponse(json_encode(['status'=>'completed','usage'=>['input_tokens'=>5,'output_tokens'=>5],'output'=>[['content'=>[['type'=>'output_text','text'=>json_encode(['suggestions'=>[$mp('home','new:multi','page',(string)$secondPage['id']),$mp('set','new:multi@'.$secondPage['id'],'description','Translated maintenance description.')],'explanation'=>'Localized the linked page.'])]]]]]));
  });
  $localRunner=new VHUG\SchemaManagerBundle\Ai\AnalysisRunner($db,$store,new VHUG\SchemaManagerBundle\Ai\SiteInventory($db,$content),new VHUG\SchemaManagerBundle\Ai\OpenAiProvider($localHttp,$keys),$engine,new VHUG\SchemaManagerBundle\Ai\PublicTextFetcher(),new VHUG\SchemaManagerBundle\Ai\SchemaAudit($db,$siteInventory),new VHUG\SchemaManagerBundle\Ai\SchemaImport($db,new VHUG\SchemaManagerBundle\Ai\SchemaAudit($db,$siteInventory),$policy,$siteInventory));
  $localResponse=$localRunner->step($translationId,$user);$localized=$store->get($translationId,1);
  $check($localResponse['done'] && $localResponse['remaining']===0 && count($localized['proposals'])===5 && $localized['mapped']===[],'Localization batch completes and leaves all changes for review');
  $refineHttp=new Symfony\Component\HttpClient\MockHttpClient(function($method,$url,$options)use($check){
   $request=json_decode($options['body'],true);$context=json_decode($request['input'],true);
   $check($context['editorFeedback']==='Keep broad services. Explain missing relationships.','Feedback reaches provider context');
   $check(in_array('explanation',$request['text']['format']['schema']['required'],true),'Explanation is required in structured response');
   return new Symfony\Component\HttpClient\Response\MockResponse(json_encode(['status'=>'completed','usage'=>['input_tokens'=>20,'output_tokens'=>10],'output'=>[['content'=>[['type'=>'output_text','text'=>json_encode(['suggestions'=>[],'explanation'=>'Keep broad services; software relationships need additional supported fields.'])]]]]]));
  });
  $refiner=new VHUG\SchemaManagerBundle\Ai\AnalysisRunner($db,$store,new VHUG\SchemaManagerBundle\Ai\SiteInventory($db,$content),new VHUG\SchemaManagerBundle\Ai\OpenAiProvider($refineHttp,$keys),$engine,new VHUG\SchemaManagerBundle\Ai\PublicTextFetcher(),new VHUG\SchemaManagerBundle\Ai\SchemaAudit($db,$siteInventory),new VHUG\SchemaManagerBundle\Ai\SchemaImport($db,new VHUG\SchemaManagerBundle\Ai\SchemaAudit($db,$siteInventory),$policy,$siteInventory));
  $revisedId=$refiner->refine($newRun,$user,'Keep broad services. Explain missing relationships.');$revised=$store->get($revisedId,1);
  $check($store->get($newRun,1)===$before,'Refinement preserves original run');
  $check($revisedId!==$newRun && $revised['status']==='complete' && $revised['refinementOf']===$newRun && $revised['usage']['input_tokens']===20 && str_contains($revised['explanation'],'broad services'),'Feedback creates a separate completed review with usage and explanation');
  $check(end($revised['conversation'])['role']==='user' && $revised['mapped']===[],'Conversation retained without applying schema');
  $locked=$store->get($runId,1);$locked['status']='complete';$store->save($runId,1,$locked);
  try{$refiner->refine($runId,$user,'test');throw new LogicException('Applied suggestions accepted for refinement');}catch(RuntimeException $expected){}

 } finally {foreach(glob($keyPath.'/var/*') as $f)unlink($f);rmdir($keyPath.'/var');unlink($keyPath.'/.env.local');rmdir($keyPath);}
 foreach($inventory['pages'] as $r){$check(empty($r['requireItem']) && !str_contains($r['robots'] ?? '','noindex'),'Reader containers and noindex pages excluded');}
 echo "PASS: key storage, fixed provider, evidence validation, draft dependencies, versions, identity protection and stale edits.\n";
} finally { $db->rollBack();echo "AI fixtures rolled back.\n"; }
