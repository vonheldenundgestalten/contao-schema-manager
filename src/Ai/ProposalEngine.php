<?php
declare(strict_types=1);
namespace VHUG\SchemaManagerBundle\Ai;
use Contao\StringUtil;
use Contao\Versions;
use Contao\BackendUser;
use Doctrine\DBAL\Connection;
use Contao\CoreBundle\Cache\CacheTagManager;
final class ProposalEngine
{
    public function __construct(private readonly Connection $db,private readonly FieldPolicy $policy,private readonly CacheTagManager $tags) {}
    public function context(array $run,array $sources): array
    {
        $records=[];
        foreach ($run['inventory']['records'] as $key=>$row) {
            [$table,,$type]=$this->resolve($key,$run);
            $keep=array_merge(FieldPolicy::fields($table,$type),array_keys(FieldPolicy::links($table,$type)),['id','pid','entityType','entityId','name','headline','title','page','language','published','_organizer','_authorTarget','_author']);
            if (array_key_exists('published',$row) && empty($row['published'])) {
                continue;
            }
            $records[$key]=array_intersect_key($row,array_flip($keep));
            foreach(['registrationIdentifiers','registrationNames'] as $registrationField)if(isset($records[$key][$registrationField]))$records[$key][$registrationField]=StringUtil::deserialize($records[$key][$registrationField],true);
            foreach (array_keys(FieldPolicy::links($table,$type)) as $field) { if (isset($records[$key][$field]) && !in_array($field,['organization','schemaOrganizer','schemaPerson'],true)) { $records[$key][$field]=StringUtil::deserialize($records[$key][$field],true); } }
        }
        // Omit inactive relation targets from the prompt, but retain original DB
        // values in the inventory for optimistic concurrency checks when applying.
        foreach($records as $key=>&$record){
            [$table,,$type]=$this->resolve($key,$run);
            foreach(array_keys(FieldPolicy::links($table,$type)) as $field){
                if(!isset($record[$field]))continue;
                if(in_array($field,['organization','schemaOrganizer','schemaPerson'],true)){if($field!=='schemaPerson'&&!isset($records['entity:'.$record[$field]]))$record[$field]=0;}
                else{$record[$field]=array_values(array_filter(StringUtil::deserialize($record[$field],true),static fn($id)=>isset($records['entity:'.$id])));}
            }
        }unset($record);
        $fields=[];
        foreach (FieldPolicy::TYPES as $type) { $fields[$type]=['entity'=>FieldPolicy::fields('tl_schema_entity',$type),'translation'=>FieldPolicy::fields('tl_schema_translation',$type),'links'=>FieldPolicy::links('tl_schema_entity',$type)]; }
        return ['stage'=>$run['stage'] ?? 'content','reservedIdentities'=>$run['inventory']['identities'] ?? [],'editorLanguage'=>$run['editorLanguage'] ?? 'en','editorFeedback'=>$run['editorFeedback'] ?? '', 'conversation'=>$run['conversation'] ?? [],'previousSuggestions'=>$run['previousSuggestions'] ?? [],'mode'=>$run['mode'],'sources'=>array_values($sources),'records'=>$records,'eligibleHomes'=>array_values(array_map(static fn($p)=>array_intersect_key($p,array_flip(['id','title','language','url','languageFamily'])),$run['inventory']['pages'])),'allowed'=>$fields,
            'pending'=>array_map(static fn($p)=>array_intersect_key($p,array_flip(['action','target','field','value'])),array_values(array_filter($run['proposals'],static fn($p)=>$p['status']==='pending'))),
            'preserveExistingValues'=>true,'authorLinks'=>['schemaPerson'=>['Person']],'pageLinks'=>['schemaEntities'],'eventFields'=>FieldPolicy::fields('tl_calendar_events','Event'),'eventLinks'=>FieldPolicy::links('tl_calendar_events','Event'),'newsLinks'=>['schemaAbout','schemaMentions'],'pageFields'=>['schemaPageType']];
    }
    /** Missing linked language homes only; existing editorial translations are preserved. */
    public function localizationTasks(array &$run): array
    {
        if(empty($run['inventory']['multilingual']))return [];
        $pages=$run['inventory']['pages'];$anchors=[];$existing=[];$descriptions=[];
        foreach($run['inventory']['records'] as $key=>$row){
            if(!str_starts_with($key,'translation:'))continue;
            $target='entity:'.$row['pid'];
            if(($run['stage']??'')==='foundation'&&!in_array($run['inventory']['records'][$target]['entityType']??'',['Organization','LocalBusiness'],true))continue;
            $existing[$target][$row['language']]=true;
            if(isset($pages[$row['page']]))$anchors[$target][]=(int)$row['page'];
        }
        foreach($run['proposals'] as $p){
            if(in_array($p['status'],['invalid','rejected'],true))continue;
            if($p['action']==='home')$anchors[$p['target']][]=(int)$p['value'];
            if($p['action']==='set' && $p['field']==='description')$descriptions[$p['target']]=true;
        }
        $tasks=[];
        foreach($anchors as $target=>$homePages){
            $families=[];foreach($homePages as $id)if(isset($pages[$id]))$families[]=$pages[$id]['languageFamily'] ?? $id;
            $related=[];$missing=[];$byLanguage=[];
            foreach($pages as $id=>$page){
                if(!in_array($page['languageFamily'] ?? $id,$families,true))continue;
                $related[]=(int)$id;$byLanguage[$page['language']][]=(int)$id;
            }
            foreach($byLanguage as $language=>$ids){
                if(isset($existing[$target][$language]))continue;
                if(count($ids)!==1){$run['warnings'][]='Ambiguous linked pages for '.$target.' ('.$language.'); choose its page manually.';continue;}
                $page=$ids[0];if(!isset($descriptions[$target.'@'.$page]))$missing[]=$page;
            }
            if($missing)$tasks[]=['target'=>$target,'pages'=>$related,'missingPages'=>$missing];
            $languages=array_unique(array_column($pages,'language'));
            foreach($languages as $language){if(!isset($byLanguage[$language])&&!isset($existing[$target][$language]))$run['warnings'][]='No linked '.$language.' page for '.$target.'. Link the translated page in Contao to add its localized content.';}
        }
        return $tasks;
    }
    public function resolve(string $key,array $run): array
    {
        if (preg_match('/^(entity:[1-9][0-9]*|new:[a-z0-9-]{1,64})@([1-9][0-9]*)$/D',$key,$m)) {
            [,$parentId,$type]=$this->resolve($m[1],$run);
            $id=(int)($run['mapped'][$key] ?? 0);
            foreach ($run['inventory']['records'] as $k=>$row) { if (str_starts_with($k,'translation:') && ((int)$row['pid']===$parentId || 'entity:'.$row['pid']===$m[1]) && (int)$row['page']===(int)$m[2]) { return ['tl_schema_translation',(int)$row['id'],$type,$row]; } }
            return ['tl_schema_translation',$id,$type,[]];
        }
        if (str_starts_with($key,'new:')) {
            foreach ($run['proposals'] as $p) { if ($p['action']==='create' && $p['target']===$key && !in_array($p['status'],['invalid','rejected'],true)) { return ['tl_schema_entity',(int)($run['mapped'][$key] ?? 0),$p['field'],[]]; } }
            throw new \InvalidArgumentException('Select a valid candidate creation first.');
        }
        if(!empty($run['configuration']) && preg_match('/^(root|archive|calendar):([1-9][0-9]*)$/D',$key,$m) && isset($run['inventory']['records'][$key])){
            return [$m[1]==='root'?'tl_page':($m[1]==='calendar'?'tl_calendar':'tl_news_archive'),(int)$m[2],$m[1]==='root'?'WebSite':'Archive',$run['inventory']['records'][$key]];
        }
        if (!preg_match('/^(entity|translation|news|event|author|page):([1-9][0-9]*)$/D',$key,$m) || !isset($run['inventory']['records'][$key])) { throw new \InvalidArgumentException('Unknown target record.'); }
        $row=$run['inventory']['records'][$key];$type=$row['entityType'] ?? ($m[1]==='event'?'Event':'');
        if ($m[1]==='translation') { $type=$run['inventory']['records']['entity:'.$row['pid']]['entityType'] ?? ''; }
        return [match($m[1]){'entity'=>'tl_schema_entity','translation'=>'tl_schema_translation','news'=>'tl_news','event'=>'tl_calendar_events','author'=>'tl_user','page'=>'tl_page'},(int)$m[2],$type,$row];
    }
    /** Author coverage must not depend on whether the language model happens to propose it. */
    public function proposeAuthors(array &$run): void
    {
        if(($run['stage']??'content')!=='content')return;
        $normalize=static fn($name)=>mb_strtolower(trim(preg_replace('/\s+/u',' ',(string)$name)));
        foreach($run['inventory']['records'] as $target=>$author){
            if(!str_starts_with($target,'author:')||!empty($author['schemaPerson']))continue;
            $name=trim($author['name']??'');$source=null;
            foreach($run['inventory']['sources'] as $key=>$candidate){
                if(str_starts_with($key,'news:')&&($run['inventory']['records'][$key]['_authorTarget']??'')===$target){$source=$candidate;break;}
            }
            if(!$source)continue;
            // A display name alone cannot prove that an account represents a human.
            if(!preg_match('/^\p{L}[\p{L}\p{M} .\x{2019}\x{0027}-]+\s+\p{L}[\p{L}\p{M} .\x{2019}\x{0027}-]*$/u',$name)||preg_match('/\b(team|admin|editor|redaktion|support|marketing|office|gmbh|company)\b/iu',$name)){
                $run['warnings'][]='Review author "'.$name.'": confirm a named person in Contao before creating a public Person.';continue;
            }
            $matches=array_filter($run['inventory']['identities']??[],static fn($r)=>($r['entityType']??'')==='Person'&&$normalize($r['name']??'')===$normalize($name));
            $related=null;$create=null;
            if(count($matches)>1){$run['warnings'][]='Multiple Persons match author "'.$name.'". Choose the public author entity in user settings.';continue;}
            if($matches){
                $match=reset($matches);$related='entity:'.$match['id'];
                if(!isset($run['inventory']['records'][$related])){$run['warnings'][]='Person "'.$name.'" already exists as a draft. Review/publish it and link it in user settings; no duplicate was proposed.';continue;}
            }else{
                foreach($run['proposals'] as $p){if($p['action']==='create'&&$p['field']==='Person'&&$normalize($p['value'])===$normalize($name)&&!in_array($p['status'],['invalid','rejected'],true)){$related=$p['target'];break;}}
                $related??='new:author-'.substr($target,7);
                $create=['action'=>'create','target'=>$related,'field'=>'Person','value'=>$name];
            }
            $quote='Contao editorial author: '.$name.'.';
            $evidence=['source'=>$source['id'],'quote'=>$quote,'reason'=>'This named Contao author writes published articles. Review that this is a person, then create/link a public Person so all their articles share one identity. No biography, job title or home page is inferred. New entries remain drafts; publish after review.'];
            $suggestions=[];
            if($create&&!array_filter($run['proposals'],static fn($p)=>$p['action']==='create'&&$p['target']===$related))$suggestions[]=$create+$evidence;
            if(!array_filter($run['proposals'],static fn($p)=>$p['action']==='add'&&$p['target']===$target&&$p['field']==='schemaPerson'&&!in_array($p['status'],['invalid','rejected'],true)))$suggestions[]=['action'=>'add','target'=>$target,'field'=>'schemaPerson','value'=>$related]+$evidence;
            $this->ingest($run,$suggestions,[$source['id']=>$source],true);
        }
        $run['warnings']=array_values(array_unique($run['warnings']));
    }
    public function ingest(array &$run,array $suggestions,array $sources,bool $authorCoverage=false): void
    {
        // Define candidates before their fields, irrespective of provider ordering.
        usort($suggestions,static fn($a,$b)=>(['create'=>0,'home'=>1][$a['action']] ?? 2)<=>(['create'=>0,'home'=>1][$b['action']] ?? 2));
        foreach ($suggestions as $item) {
            $p=array_intersect_key($item,array_flip(['action','target','field','value','source','quote','reason']));
            if (count($p)!==7 || count(array_filter($p,'is_string'))!==7) { continue; }
            // Accept the same page reference notation used in the supplied source IDs.
            if ($p['action']==='home' && preg_match('/^page:([1-9][0-9]*)$/D',$p['value'],$match)) { $p['value']=$match[1]; }
            $p['target']=preg_replace('/@page:([1-9][0-9]*)$/D','@$1',$p['target']);
            $p+=['status'=>'pending','old'=>'','error'=>''];
            $p['fingerprint']=hash('sha256',json_encode([$p['action'],$p['target'],$p['field'],$p['value'],$sources[$p['source']]['hash'] ?? ''],JSON_THROW_ON_ERROR));
            if (isset($run['decisions'][$p['fingerprint']]) || count(array_filter($run['proposals'],static fn($old)=>$old['fingerprint']===$p['fingerprint']))) { continue; }
            try {
                $source=$sources[$p['source']] ?? null;
                if (!$source || mb_strlen(trim($p['quote']))<8 || !str_contains($source['text'],trim($p['quote']))) { throw new \InvalidArgumentException('The quotation was not found in the supplied source.'); }
                if (mb_strlen($p['reason'])>2000 || mb_strlen($p['value'])>6000 || mb_strlen($p['quote'])>2000) { throw new \InvalidArgumentException('Suggestion exceeds the field limits.'); }
                if(($run['stage'] ?? '')==='foundation'){
                    if($p['action']==='create'){$foundationType=$p['field'];}
                    elseif($p['action']==='add'&&str_starts_with($p['target'],'page:')&&$p['field']==='schemaEntities'){[,,$foundationType]=$this->resolve($p['value'],$run);}
                    else{[,,$foundationType]=$this->resolve($p['target'],$run);}
                    if(!in_array($foundationType,['Organization','LocalBusiness'],true))throw new \InvalidArgumentException('Foundation analysis only handles organizations, their localized homes and page links.');
                }
                $authorMapping=$p['action']==='add'&&str_starts_with($p['target'],'author:')&&$p['field']==='schemaPerson';
                if (empty($run['_localizationTask']) && $run['mode']==='discover' && !$authorMapping && !str_starts_with($p['target'],'new:') && !($p['action']==='add' && preg_match('/^(news|event|author|page):[1-9][0-9]*$/D',$p['target']) && str_starts_with($p['value'],'new:'))) { throw new \InvalidArgumentException('New-subject analysis cannot edit existing records.'); }
                if (empty($run['_localizationTask']) && $run['mode']==='improve' && str_starts_with($p['target'],'new:') && !($authorCoverage && $p['action']==='create' && $p['field']==='Person')) { throw new \InvalidArgumentException('Improvement analysis cannot create new entities.'); }
                if(!empty($run['_localizationTask'])){
                    $task=$run['_localizationTask'];
                    $home=$p['action']==='home'&&$p['target']===$task['target']&&in_array((int)$p['value'],$task['missingPages'],true);
                    $field=$p['action']==='set'&&preg_match('/^'.preg_quote($task['target'],'/').'@([0-9]+)$/D',$p['target'],$match)&&in_array((int)$match[1],$task['missingPages'],true);
                    if(!$home&&!$field)throw new \InvalidArgumentException('Translation phase can only add the requested localized homes and fields.');
                }
                if ($p['action']==='create') {
                    if (!preg_match('/^new:[a-z0-9-]{1,64}$/D',$p['target']) || !in_array($p['field'],FieldPolicy::TYPES,true)) { throw new \InvalidArgumentException('Invalid new entity.'); }
                    $p['value']=$this->policy->validate('tl_schema_entity',$p['field'],'name',$p['value']);
                    if(self::matchingIdentity($run['inventory']['identities'] ?? [],$p['field'],$p['value']))throw new \InvalidArgumentException('This entity already exists, possibly as an unpublished draft. Review the existing entry instead.');
                    foreach ($run['inventory']['records'] as $k=>$r) { if (str_starts_with($k,'entity:') && ($r['entityType'] ?? '')===$p['field'] && mb_strtolower(trim($r['name']))===mb_strtolower($p['value'])) { throw new \InvalidArgumentException('An entity with this name and type already exists; review it instead.'); } }
                    foreach ($run['proposals'] as $prior) { if ($prior['action']==='create' && $prior['target']===$p['target'] && $prior['status']!=='invalid') { throw new \InvalidArgumentException('Candidate already proposed.'); } }
                } else {
                    [$table,,$type,$row]=$this->resolve($p['target'],$run);
                    if ($p['action']==='home') {
                        if ($table!=='tl_schema_entity' || $p['field']!=='page' || !ctype_digit($p['value']) || !isset($run['inventory']['pages'][(int)$p['value']])) { throw new \InvalidArgumentException('Choose an eligible page as home.'); }
                        foreach($run['proposals'] as $prior){if($prior['action']==='home'&&$prior['target']===$p['target']&&!in_array($prior['status'],['invalid','rejected'],true)&&($run['inventory']['pages'][(int)$prior['value']]['language']??'')===$run['inventory']['pages'][(int)$p['value']]['language'])throw new \InvalidArgumentException('A home is already proposed for this language.');}
                        foreach ($run['inventory']['records'] as $key=>$home) { if (str_starts_with($key,'translation:') && 'entity:'.$home['pid']===$p['target'] && $home['language']===$run['inventory']['pages'][(int)$p['value']]['language']) { throw new \InvalidArgumentException('A home already exists for this language. Edit it normally.'); } }
                    } elseif ($p['action']==='set') {
                        $p['value']=$this->policy->validate($table,$type,$p['field'],$p['value']);
                        if ($table==='tl_schema_translation') {
                            $page=(int)($row['page'] ?? substr(strrchr($p['target'],'@'),1));
                            if (empty($run['_localizationTask']) && ($run['inventory']['pages'][$page]['language'] ?? '')!==$source['language']) { throw new \InvalidArgumentException('Source and translation language differ.'); }
                        }
                        $p['old']=(string)($row[$p['field']] ?? '');
                        if ($p['old']===$p['value']) { continue; }
                    } elseif ($p['action']==='add') {
                        if($table==='tl_user'&&(!empty($row['schemaPerson'])||($source['id']??'')===''||($run['inventory']['records'][$source['id']]['_authorTarget']??'')!==$p['target']))throw new \InvalidArgumentException('Only an unmapped author of the cited news can be connected.');
                        $types=FieldPolicy::links($table,$type)[$p['field']] ?? [];
                        [$targetTable,,$targetType]=$this->resolve($p['value'],$run);
                        if ($targetTable!=='tl_schema_entity' || !in_array($targetType,$types,true) || $p['target']===$p['value']) { throw new \InvalidArgumentException('Invalid relationship.'); }
                        $p['old']=$row[$p['field']] ?? null;
                        $related=(int)substr($p['value'],7);
                        if($p['field']==='schemaOrganizer'&&str_starts_with($p['value'],'entity:')&&(int)($row['_organizer']??0)===$related)continue;
                        if (str_starts_with($p['value'],'entity:') && (in_array($p['field'],['organization','schemaOrganizer','schemaPerson'],true)?(int)$p['old']===$related:in_array($related,array_map('intval',StringUtil::deserialize($p['old'],true)),true))) { continue; }
                    } else { throw new \InvalidArgumentException('Unsupported action.'); }
                }
            } catch (\InvalidArgumentException $e) { $p['status']='invalid';$p['error']=$e->getMessage(); }
            $p['bulk']=$p['status']==='pending' && (empty($p['old']) || ($p['action']==='add' && !in_array($p['field'],['organization','schemaPerson'],true))) && !in_array($p['field'],['legalName','name'],true);
            $run['proposals'][]=$p;
        }
    }
    private static function matchingIdentity(array $identities,string $type,string $name): bool
    {
        $normalize=static fn(string $value):string=>mb_strtolower(trim(preg_replace('/\s+/u',' ',$value)));
        $name=$normalize($name);
        foreach($identities as $identity){
            if(($identity['entityType'] ?? '')!==$type)continue;
            foreach(['name','legalName'] as $field){if(!empty($identity[$field])&&$normalize($identity[$field])===$name)return true;}
        }
        return false;
    }
    /** Caller holds the run row lock and a DB transaction. All selected changes are atomic. */
    public function apply(array &$run,array $selected,BackendUser $user): int
    {
        // A language record may have been created by an earlier apply or manually
        // since the scan. Resolve exact entity + page identities, never names.
        foreach($run['proposals'] as $proposal){
            $key=$proposal['target'];
            if($proposal['status']!=='pending'||!preg_match('/^(entity:[1-9][0-9]*|new:[a-z0-9-]+)@([1-9][0-9]*)$/D',$key,$parts)||!empty($run['mapped'][$key]))continue;
            try{[,$parentId,$parentType]=$this->resolve($parts[1],$run);}catch(\InvalidArgumentException){continue;}
            if(!$parentId)continue;
            $homes=$this->db->fetchFirstColumn('SELECT t.id FROM tl_schema_translation t JOIN tl_schema_entity e ON e.id=t.pid WHERE t.pid=? AND t.page=? AND e.entityType=?',[$parentId,(int)$parts[2],$parentType]);
            if(count($homes)===1)$run['mapped'][$key]=(int)$homes[0];
        }
        $plan=ProposalQueue::plan($run,$selected);$indices=$plan['order'];$run['applyBlocked']=$plan['blocked'];$versions=[];$count=0;
        foreach ($indices as $index) {
            $p=$run['proposals'][$index] ?? null;
            if (!$p || $p['status']!=='pending') { throw new \RuntimeException('A selection has already changed. Reload the review.'); }
            try {
            if($p['action']==='remove'){
                $record=(new MissingRelations($this->db))->apply($p,$run,$user);
                $run['proposals'][$index]['status']='applied';$run['proposals'][$index]['appliedAt']=time();$run['proposals'][$index]['record']=$record;++$count;continue;
            }
            $field=$p['field'];$value=$p['value'];
            if ($p['action']==='create') {
                if (self::matchingIdentity($this->db->fetchAllAssociative('SELECT id,entityType,name,legalName FROM tl_schema_entity'),$field,$value)) { throw new \RuntimeException('A matching entity now exists. Rescan before creating a duplicate.'); }
                $this->db->insert('tl_schema_entity',['tstamp'=>time(),'name'=>$value,'entityType'=>$field,'identityBase'=>$run['origin'],'entityId'=>$run['origin'].'/#entity-'.bin2hex(random_bytes(16)),'published'=>'']);
                $id=(int)$this->db->lastInsertId();$table='tl_schema_entity';$run['mapped'][$p['target']]=$id;
            } else {
                [$table,$id,$type]=$this->resolve($p['target'],$run);
                if($table==='tl_user'){
                    if(!$user->isAdmin)throw new \RuntimeException('Only administrators may map backend authors.');
                    $sourceId=(int)substr($p['source'],5);
                    if(!str_starts_with($p['source'],'news:')||(int)$this->db->fetchOne('SELECT author FROM tl_news WHERE id=?',[$sourceId])!==$id)throw new \RuntimeException('The news author changed. Rescan before mapping.');
                }
                if (!$id) { throw new \RuntimeException('Select the dependent entity/home creation, or apply it first.'); }
                $current=$this->db->fetchAssociative('SELECT * FROM '.$table.' WHERE id=? FOR UPDATE',[$id]);
                if (!$current) { throw new \RuntimeException('A target record was removed. Rescan.'); }
                if ($p['action']==='home') {
                    $page=\Contao\PageModel::findById((int)$value);$page?->loadDetails();
                    if (!$page || !$page->published || $page->protected || $page->requireItem || !in_array((int)$page->rootId,$run['inventory']['roots'] ?? [$run['root']],true)) { throw new \RuntimeException('The proposed home is no longer eligible.'); }
                    if ($this->db->fetchOne('SELECT id FROM tl_schema_translation WHERE pid=? AND language=?',[$id,$page->language])) { throw new \RuntimeException('A home for this language already exists.'); }
                    $this->db->insert('tl_schema_translation',['tstamp'=>time(),'pid'=>$id,'page'=>(int)$value,'language'=>$page->language,'published'=>'']);
                    $id=(int)$this->db->lastInsertId();$table='tl_schema_translation';$run['mapped'][$p['target'].'@'.$value]=$id;
                } else {
                    $versionKey=$table.':'.$id;
                    // Compare against the run snapshot before the first mutation of each field.
                    $checkKey=$versionKey.':'.$field;
                    if (!isset($run['_checked'][$checkKey]) && !$this->sameFieldValue($table,$type,$field,$current[$field] ?? null,$p['old'] ?? null)) {
                        throw new \RuntimeException(sprintf('The field "%s" on %s #%d changed since analysis. Rescan instead of overwriting it.',$field,$table,$id));
                    }
                    $run['_checked'][$checkKey]=true;
                    if (!isset($versions[$versionKey])) { $v=new Versions($table,$id);$v->setUserId((int)$user->id);$v->setUsername($user->username);$v->setEditUrl('do='.(in_array($table,['tl_news','tl_news_archive'],true)?'news':(in_array($table,['tl_calendar','tl_calendar_events'],true)?'calendar':($table==='tl_user'?'user':($table==='tl_page'?'page':'schema_manager')))).'&table='.$table.'&act=edit&id='.$id);$v->initialize();$versions[$versionKey]=$v; }
                    if ($p['action']==='set') {
                        $value=$this->policy->validate($table,$type,$field,$value);
                        if(in_array($field,['schemaPublisher','schemaOrganizer'],true) && (int)$value && !$this->db->fetchOne("SELECT id FROM tl_schema_entity WHERE id=? AND entityType IN ('Organization','LocalBusiness')",[(int)$value]))throw new \RuntimeException('The publisher no longer exists. Review parent setup again.');
                    }
                    else {
                        [$targetTable,$related,$relatedType]=$this->resolve($value,$run);
                        if (!$related || !$this->db->fetchOne('SELECT id FROM tl_schema_entity WHERE id=? AND entityType=?',[$related,$relatedType]) || !in_array($relatedType,FieldPolicy::links($table,$type)[$field] ?? [],true)) { throw new \RuntimeException('Select the related entity creation first.'); }
                        if ($table==='tl_schema_entity' && $id===$related) { throw new \RuntimeException('Self relationships are not allowed.'); }
                        if (in_array($field,['organization','schemaOrganizer','schemaPerson'],true)) {
                            $parent=$field==='organization'?$related:0;$seen=[$id=>true];
                            while($parent){if(isset($seen[$parent]))throw new \RuntimeException('Circular organization relationship.');$seen[$parent]=true;$parent=(int)$this->db->fetchOne('SELECT organization FROM tl_schema_entity WHERE id=?',[$parent]);}
                            $value=$related;
                        } else { $value=serialize(array_values(array_unique(array_merge(array_map('intval',StringUtil::deserialize($current[$field] ?? null,true)),[$related])))); }
                    }
                    if(in_array($field,['registrationIdentifiers','registrationNames'],true)){
                        $value=serialize(json_decode($value,true,64,JSON_THROW_ON_ERROR));
                        if($field==='registrationNames'){ $valid=array_map('strval',array_column(StringUtil::deserialize($this->db->fetchOne('SELECT registrationIdentifiers FROM tl_schema_entity WHERE id=?',[$current['pid']]),true),'value'));foreach(StringUtil::deserialize($value,true) as $label)if(!in_array((string)$label['key'],$valid,true))throw new \RuntimeException('Registration number is not defined on the shared entity.'); }
                    }
                    $this->db->update($table,[$field=>$value,'tstamp'=>time()],['id'=>$id]);
                }
            }
            if($table==='tl_calendar_events')(new \VHUG\SchemaManagerBundle\EventListener\CalendarSettingsListener($this->db,$this->tags))->ensureIdentity($id);
            $versionKey=$table.':'.$id;
            if (!isset($versions[$versionKey])) { $v=new Versions($table,$id);$v->setUserId((int)$user->id);$v->setUsername($user->username);$v->setEditUrl('do='.(in_array($table,['tl_news','tl_news_archive'],true)?'news':(in_array($table,['tl_calendar','tl_calendar_events'],true)?'calendar':($table==='tl_user'?'user':($table==='tl_page'?'page':'schema_manager')))).'&table='.$table.'&act=edit&id='.$id);$versions[$versionKey]=$v; }
            $run['proposals'][$index]['status']='applied';$run['proposals'][$index]['appliedAt']=time();$run['proposals'][$index]['record']=$versionKey;$count++;
            } catch (\Throwable $e) { throw new \RuntimeException(ProposalQueue::label($p,$run).': '.$e->getMessage(),0,$e); }
        }
        if(!empty($run['configuration'])){
            $settings=new \VHUG\SchemaManagerBundle\EventListener\SourceSettingsListener($this->db);
            foreach($indices as $index){$key=$run['proposals'][$index]['target'];$id=(int)substr(strstr($key,':'),1);if(str_starts_with($key,'root:'))$settings->ensureWebsiteIdentity($id);elseif(str_starts_with($key,'archive:'))$settings->ensureArchiveIdentities($id);else{foreach($this->db->fetchFirstColumn('SELECT id FROM tl_calendar_events WHERE pid=?',[$id]) as $eventId)(new \VHUG\SchemaManagerBundle\EventListener\CalendarSettingsListener($this->db,$this->tags))->ensureIdentity((int)$eventId);}}
        }
        foreach ($versions as $v) { $v->create(); }
        unset($run['_checked']);
        return $count;
    }
    /** Compare relationships by membership, not DB serialization or empty-value encoding. */
    private function sameFieldValue(string $table,string $type,string $field,mixed $current,mixed $snapshot): bool
    {
        if (array_key_exists($field,FieldPolicy::links($table,$type))) {
            if (in_array($field,['organization','schemaOrganizer','schemaPerson'],true)) { return (int)$current===(int)$snapshot; }
            $normalize=static function(mixed $value): array {
                $ids=array_values(array_unique(array_map('intval',StringUtil::deserialize($value,true))));
                sort($ids,SORT_NUMERIC);
                return $ids;
            };
            return $normalize($current)===$normalize($snapshot);
        }
        return (string)($current ?? '')===(string)($snapshot ?? '');
    }
    public function invalidate(): void
    {
        foreach ([\VHUG\SchemaManagerBundle\Model\EntityModel::class,\VHUG\SchemaManagerBundle\Model\TranslationModel::class,\Contao\PageModel::class] as $class) { $this->tags->invalidateTagsForModelClass($class); }
        (new \VHUG\SchemaManagerBundle\EventListener\CalendarSettingsListener($this->db,$this->tags))->invalidate();
        $this->tags->invalidateTagsForModelClass(\Contao\UserModel::class);
        if (class_exists(\Contao\NewsModel::class)) { $this->tags->invalidateTagsForModelClass(\Contao\NewsModel::class);$this->tags->invalidateTagsForModelClass(\Contao\NewsArchiveModel::class); }
    }
}
