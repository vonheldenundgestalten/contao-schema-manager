<?php
declare(strict_types=1);
namespace VHUG\SchemaManagerBundle\Ai;
use Doctrine\DBAL\Connection;
use Contao\BackendUser;
/** Deterministic migration before AI discovery. Original IDs and unsupported data are retained. */
final class SchemaImport
{
    private const BUSINESS=['Organization','LocalBusiness','Person','Service','Product','Event'];
    public function __construct(private readonly Connection $db,private readonly SchemaAudit $audit,private readonly FieldPolicy $policy,private readonly SiteInventory $inventory){}
    public function scan(array $source): array
    {
        $out=['source'=>$source,'candidates'=>[],'elements'=>[],'warnings'=>[]];
        try{$parsed=SchemaMarkup::parse($this->audit->fetch($source['url']));$out['warnings']=$parsed['errors'];}
        catch(\Throwable){$out['warnings'][]='Could not read public JSON-LD on '.$source['url'].'. Keep the original markup enabled and retry.';return $out;}
        $known=$this->db->fetchFirstColumn("SELECT entityId FROM tl_schema_entity WHERE entityId<>''");
        $known=array_merge($known,$this->db->fetchFirstColumn("SELECT schemaWebsiteId FROM tl_page WHERE schemaWebsiteId<>''"));
        // Reader output belongs to its native record, not a new standalone entity.
        foreach(['tl_news','tl_calendar_events'] as $nativeTable){
            $schema=$this->db->createSchemaManager();if(!$schema->tablesExist([$nativeTable]))continue;
            if($schema->introspectTable($nativeTable)->hasColumn('schemaIdentity'))$known=array_merge($known,$this->db->fetchFirstColumn("SELECT schemaIdentity FROM ".$nativeTable." WHERE schemaIdentity<>''"));
        }
        if(str_starts_with($source['id'],'event:'))$known[]=$source['url'].'#event';

        foreach($this->db->fetchFirstColumn('SELECT schemaImportedData FROM tl_schema_translation WHERE schemaImportedData IS NOT NULL') as $json){if($id=\VHUG\SchemaManagerBundle\Schema\ImportedSchema::identity($json))$known[]=$id;}
        $local=[];$fingerprints=[];$table=str_starts_with($source['id'],'news:')?'tl_news':(str_starts_with($source['id'],'event:')?'tl_calendar_events':'tl_article');
        $parents=$table==='tl_article'?$this->db->fetchFirstColumn('SELECT id FROM tl_article WHERE pid=?',[$source['page']]):[(int)substr(strstr($source['id'],':'),1)];
        foreach($this->db->fetchAllAssociative("SELECT id,pid,ptable,html FROM tl_content WHERE type='html' AND invisible=0 AND html LIKE '%application/ld+json%'") as $row){
            if($row['ptable']!==$table||!in_array((int)$row['pid'],array_map('intval',$parents),true))continue;
            $fingerprints[(int)$row['id']]=hash('sha256',$row['html']);
            foreach(SchemaMarkup::parse($row['html'])['blocks'] as $block){$local[hash('sha256',json_encode($block))]=(int)$row['id'];}
        }
        foreach($parsed['blocks'] as $block){
            if(($block['@context']??'')==='https://schema.contao.org')continue;
            $element=$local[hash('sha256',json_encode($block))]??0;
            $nodes=SchemaMarkup::parse('<script type="application/ld+json">'.json_encode($block).'</script>')['nodes'];
            // Core page/article/image nodes are not import candidates. Unknown business markup is reviewed, not assumed to be ours.
            $legacy=$element>0;
            foreach($nodes as $n)if(in_array($n['@type']??'',self::BUSINESS,true)&&!in_array($n['@id']??'',$known,true)&&preg_match('~^https?://~',(string)($n['@id']??'')))$legacy=true;
            if(!$legacy)continue;
            foreach($nodes as $node){
                $type=$node['@type']??'';
                if(!in_array($type,array_merge(self::BUSINESS,['WebSite','WebPage','AboutPage','ContactPage','CollectionPage','ProfilePage','ItemPage']),true))continue;
                if(!$element&&in_array($node['@id']??'',$known,true))continue;
                if(str_starts_with($node['@id']??'','#/schema/'))continue;
                $id=$node['@id']??'';
                if($id!==''&&(!is_string($id)||!preg_match('~^https?://[^\s]+$~uD',$id))){$out['warnings'][]='Review relative or invalid identity: '.SchemaMarkup::label($node);continue;}
                if(!is_string($node['name']??'')||(!($node['name']??'')&&!$id)){$out['warnings'][]='Unnamed entity needs manual review.';continue;}
                $out['candidates'][]=['node'=>$node,'element'=>$element];
            }
            if($element)$out['elements'][$element]=$fingerprints[$element];
        }
        return $out;
    }
    public function plan(array $run): array
    {
        $groups=[];$pages=$run['inventory']['pages'];$byUrl=[];
        foreach($pages as $id=>$page)$byUrl[rtrim($page['url'],'/')]=(int)$id;
        foreach($run['importSources']??[] as $result){
            $source=$result['source'];
            foreach($result['candidates'] as $candidate){
                $node=$candidate['node'];$type=$node['@type'];$page=$byUrl[rtrim((string)($node['url']??''),'/')]??0;
                if(!$page && in_array($type,['WebPage','AboutPage','ContactPage','CollectionPage','ProfilePage','ItemPage'],true))$page=(int)$source['page'];
                $language=$page?($pages[$page]['language']??$source['language']):$source['language'];
                // Linked localized home pages explicitly establish identity across languages.
                $key=$page?'home:'.$type.':'.($pages[$page]['languageFamily']??$page):'id:'.($node['@id']??hash('sha256',$type.':'.($node['name']??'')));
                // Same public ID always wins over page-family grouping.
                foreach($groups as $existingKey=>$g)if(!empty($node['@id'])&&in_array($node['@id'],$g['ids'],true)){$key=$existingKey;break;}
                $groups[$key]??=['type'=>$type,'name'=>$node['name']??$type,'ids'=>[],'variants'=>[],'sources'=>[],'elements'=>[],'conflicts'=>[],'existing'=>0,'status'=>'pending'];
                $g=&$groups[$key];if(!empty($node['@id']))$g['ids'][]=$node['@id'];$g['ids']=array_values(array_unique($g['ids']));
                $g['sources'][$source['id']]=$source['url'];if($candidate['element'])$g['elements'][]=$candidate['element'];
                $variant=$g['variants'][$language]??['page'=>$page,'node'=>[],'homeDefinition'=>false];
                $atHome=$page&&(int)$source['page']===$page;
                if($page&&$variant['page']&&$page!==$variant['page'])$g['conflicts'][]='More than one home for '.$language.'.';
                foreach($node as $property=>$value){
                    if(isset($variant['node'][$property])&&$variant['node'][$property]!==$value){
                        // Prefer the definition on its own home over abbreviated references elsewhere.
                        if($atHome&&!$variant['homeDefinition'])$variant['node'][$property]=$value;
                        elseif(($atHome&&$variant['homeDefinition'])||(!$variant['homeDefinition']&&!in_array($property,['description','name','url','@context'],true)))$g['conflicts'][]='Conflicting '.$property.' in '.$language.'.';
                    }else $variant['node'][$property]=$value;
                }
                $variant['homeDefinition']=$variant['homeDefinition']||$atHome;
                $variant['page']=$variant['page']?:$page;$g['variants'][$language]=$variant;unset($g);
            }
        }
        foreach($groups as &$g){
            $g['elements']=array_values(array_unique($g['elements']));$g['conflicts']=array_values(array_unique($g['conflicts']));
            if(in_array($g['type'],self::BUSINESS,true)){
                $matches=[];foreach($this->db->fetchAllAssociative('SELECT id,entityId,name,entityType FROM tl_schema_entity') as $record){
                    if(in_array($record['entityId'],$g['ids'],true)||($record['entityType']===$g['type']&&mb_strtolower($record['name'])===mb_strtolower($g['name'])))$matches[]=$record;
                }
                if(count($matches)>1)$g['conflicts'][]='Multiple existing entities match. Resolve the duplicate before importing.';
                elseif($matches){$g['existing']=(int)$matches[0]['id'];if($g['ids']&&!in_array($matches[0]['entityId'],$g['ids'],true))$g['conflicts'][]='Existing entity has another ID. Keep it unchanged and resolve its identity before importing.';}
            }
            $sharedValues=[];
            foreach($g['variants'] as $language=>&$v){
                [$v['fields'],$v['localized'],$v['retained']]=$this->fields($g['type'],$v['node']);
                foreach($v['fields'] as $field=>$value){if(isset($sharedValues[$field])&&$sharedValues[$field]!==$value)$g['conflicts'][]='Shared '.$field.' differs between languages.';$sharedValues[$field]=$value;}
                if(in_array($g['type'],self::BUSINESS,true)&&!$v['page'])$g['conflicts'][]='No eligible '.$language.' home page matched the original URL; choose a home before importing.';
                if($g['existing']){
                    $record=$this->db->fetchAssociative('SELECT * FROM tl_schema_entity WHERE id=?',[$g['existing']]);
                    $home=$this->db->fetchAssociative('SELECT * FROM tl_schema_translation WHERE pid=? AND language=?',[$g['existing'],$language]);
                    foreach($v['fields'] as $f=>$value)if(($record[$f]??'')!==''&&(string)$record[$f]!==$value)$g['conflicts'][]='Existing '.$f.' differs; it will not be overwritten.';
                    if($home){if(!empty($home['schemaImportedData'])&&json_decode($home['schemaImportedData'],true)!==$v['retained'])$g['conflicts'][]='Existing retained data differs; it will not be overwritten.';if((int)$home['page']!==$v['page'])$g['conflicts'][]='Existing '.$language.' home differs.';foreach($v['localized'] as $f=>$value)if(($home[$f]??'')!==''&&(string)$home[$f]!==$value)$g['conflicts'][]='Existing '.$language.' '.$f.' differs.';}
                }
            }unset($v);
            $g['conflicts']=array_values(array_unique($g['conflicts']));
        }unset($g);
        return $groups;
    }
    /** Map supported fields; preserve everything else as reviewed structured data, not an AI inference. */
    public function fields(string $type,array $node): array
    {
        $shared=[];$localized=[];$retained=$node;unset($retained['@context']);
        foreach(['tl_schema_entity','tl_schema_translation'] as $table){
            foreach(FieldPolicy::fields($table,$type) as $field){
                if($table==='tl_schema_entity'&&$field==='name'&&in_array($type,['Service','Product','Event'],true))continue;
                $value=$node[$field]??($node['address'][$field]??null);
                if($field==='sameAs'&&is_array($value))$value=implode("\n",array_filter($value,'is_string'));
                if(!is_string($value)&&!is_int($value)&&!is_float($value))continue;
                try{$value=$this->policy->validate($table,$type,$field,(string)$value);}catch(\Throwable){continue;}
                if($table==='tl_schema_entity')$shared[$field]=$value;else $localized[$field]=$value;
                unset($retained[$field]);if(isset($retained['address'][$field]))unset($retained['address'][$field]);
            }
        }
        if(isset($retained['address'])&&array_keys($retained['address'])===['@type'])unset($retained['address']);
        return [$shared,$localized,$retained];
    }
    public function apply(array &$run,array $selected,BackendUser $user): int
    {
        if(!$user->isAdmin||($run['stage']??'')!=='import'||$run['status']!=='complete')throw new \RuntimeException('Complete the import scan first.');
        // Re-evaluate existing records immediately before mutation: no blind overwrites.
        $current=$this->inventory->collect((int)$run['root'],$user,!empty($run['inventory']['multilingual']));
        $fresh=$this->plan($run);$ids=[];$count=0;
        $selected=array_values(array_filter(array_unique($selected),fn($key)=>($run['importPlan'][$key]['status']??'')==='pending'));
        foreach($selected as $key){foreach($fresh[$key]['variants']??[] as $v){if(!isset($current['pages'][$v['page']]))throw new \RuntimeException('An import home is no longer eligible. Rescan.');}
            foreach($fresh[$key]['sources']??[] as $source=>$url){if(!isset($current['sources'][$source]))throw new \RuntimeException('An import source is no longer active. Rescan.');
                foreach($run['importSources'][$source]['elements']??[] as $element=>$hash){$html=$this->db->fetchOne('SELECT html FROM tl_content WHERE id=? AND invisible=0',[$element]);if(!is_string($html)||!hash_equals($hash,hash('sha256',$html)))throw new \RuntimeException('Legacy markup changed since the scan. Rescan before importing.');}
            }
        }
        foreach(array_unique($selected) as $key){
            $g=$fresh[$key]??null;if(!$g||$g['conflicts']||($run['importPlan'][$key]['status']??'')!=='pending')throw new \RuntimeException('Import selection changed or has unresolved conflicts.');
            if(!in_array($g['type'],self::BUSINESS,true))continue;
            $id=$g['existing'];$first=reset($g['variants']);$identity=$g['ids'][0]??rtrim($run['origin'],'/').'/#entity-'.bin2hex(random_bytes(16));
            if(!$id){$parts=parse_url($identity);$this->db->insert('tl_schema_entity',['name'=>$g['name'],'entityType'=>$g['type'],'entityId'=>$identity,'identityBase'=>$parts['scheme'].'://'.$parts['host'].(isset($parts['port'])?':'.$parts['port']:''),'published'=>'','tstamp'=>time()]);$id=(int)$this->db->lastInsertId();}
            $this->version('tl_schema_entity',$id,$user,function()use($id,$g){foreach($g['variants'] as $v){foreach($v['fields'] as $field=>$value){$old=$this->db->fetchOne('SELECT '.$field.' FROM tl_schema_entity WHERE id=?',[$id]);if($old===null||$old==='')$this->db->update('tl_schema_entity',[$field=>$value],['id'=>$id]);}}});
            $homes=[];
            foreach($g['variants'] as $language=>$v){
                $home=$this->db->fetchAssociative('SELECT * FROM tl_schema_translation WHERE pid=? AND language=?',[$id,$language]);
                if(!$home){$this->db->insert('tl_schema_translation',['pid'=>$id,'page'=>$v['page'],'language'=>$language,'isMainEntity'=>'1','published'=>'','tstamp'=>time()]);$home=['id'=>(int)$this->db->lastInsertId()];}
                $homes[]=(int)$home['id'];
                $this->version('tl_schema_translation',(int)$home['id'],$user,function()use($home,$v){$changes=['schemaImportedData'=>json_encode($v['retained'],JSON_THROW_ON_ERROR|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE)];foreach($v['localized'] as $f=>$value)if(empty($home[$f]))$changes[$f]=$value;$this->db->update('tl_schema_translation',$changes,['id'=>$home['id']]);});
            }
            foreach($g['ids'] as $identity)$ids[$identity]=$id;
            $run['importPlan'][$key]['status']='imported';$run['importPlan'][$key]['record']=$id;$run['importPlan'][$key]['homes']=$homes;++$count;
        }
        foreach(array_unique($selected) as $key){
            $g=$fresh[$key];
            if(in_array($g['type'],self::BUSINESS,true)){
                $id=$run['importPlan'][$key]['record'];$v=reset($g['variants']);$property=match($g['type']){'Service'=>'provider','Person'=>'worksFor','Event'=>'organizer',default=>'parentOrganization'};
                $ref=$v['node'][$property]['@id']??'';$parent=$ids[$ref]??(int)$this->db->fetchOne('SELECT id FROM tl_schema_entity WHERE entityId=?',[$ref]);
                if($parent&&$parent!==$id&&in_array($this->db->fetchOne('SELECT entityType FROM tl_schema_entity WHERE id=?',[$parent]),['Organization','LocalBusiness'],true)&&!$this->db->fetchOne('SELECT organization FROM tl_schema_entity WHERE id=?',[$id]))$this->version('tl_schema_entity',$id,$user,fn()=>$this->db->update('tl_schema_entity',['organization'=>$parent],['id'=>$id]));
                continue;
            }
            foreach($g['variants'] as $v){
                $page=\Contao\PageModel::findById($v['page']);if(!$page)throw new \RuntimeException('Imported page disappeared.');$page->loadDetails();$target=$g['type']==='WebSite'?(int)$page->rootId:(int)$page->id;
                $row=$this->db->fetchAssociative('SELECT * FROM tl_page WHERE id=?',[$target]);$changes=['schemaImportedData'=>json_encode($v['node'],JSON_THROW_ON_ERROR|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE)];
                if($g['type']==='WebSite'){
                    $identity=$v['node']['@id']??'';if($row['schemaWebsiteId']&&$row['schemaWebsiteId']!==$identity)throw new \RuntimeException('Existing website identity differs; resolve it before importing.');
                    $changes+=['schemaWebsiteId'=>$identity,'schemaWebsiteHome'=>$v['page'],'schemaSiteName'=>$row['schemaSiteName']?:($v['node']['name']??'')];
                    $publisher=$ids[$v['node']['publisher']['@id']??'']??0;if($publisher&&!$row['schemaPublisher'])$changes['schemaPublisher']=$publisher;
                }else $changes['schemaPageType']=$row['schemaPageType']?:$g['type'];
                if(!empty($row['schemaImportedData'])&&$row['schemaImportedData']!==$changes['schemaImportedData'])throw new \RuntimeException('Existing imported page data differs; nothing overwritten.');
                // Page configuration is staged in the review; importing drafts has no public effect.
                $run['importPlan'][$key]['pageChanges'][$target]=['before'=>array_intersect_key($row,$changes),'changes'=>$changes];
            }
            $run['importPlan'][$key]['status']='imported';$run['importPlan'][$key]['pages']=array_values(array_unique(array_map(function($v)use($g){$p=\Contao\PageModel::findById($v['page']);$p->loadDetails();return $g['type']==='WebSite'?(int)$p->rootId:(int)$p->id;},$g['variants'])));++$count;
        }
        if(!$count)throw new \RuntimeException('Select the legacy entities to import.');return $count;
    }
    public function verify(array &$run): void
    {
        if(($run['stage']??'')!=='import'||$run['status']!=='complete')throw new \RuntimeException('Complete the import first.');
        $keys=[];foreach($run['importPlan'] as $g)if($g['status']==='published')foreach($g['sources'] as $key=>$url)$keys[$key]=true;
        if(!$keys)throw new \RuntimeException('Publish reviewed imports before verifying their output.');
        $run['auditResults']=[];
        foreach(array_keys($keys) as $key)$run['auditResults'][$key]=$this->audit->inspect($run['inventory']['sources'][$key]);
    }
    public function publish(array &$run,array $selected,BackendUser $user): int
    {
        if(!$user->isAdmin||($run['stage']??'')!=='import'||$run['status']!=='complete')throw new \RuntimeException('Complete the import first.');$count=0;
        $selected=array_values(array_filter(array_unique($selected),fn($key)=>($run['importPlan'][$key]['status']??'')==='imported'));
        foreach(array_unique($selected) as $key){
            $g=$run['importPlan'][$key]??null;if(!$g||$g['status']!=='imported')throw new \RuntimeException('Select imported drafts.');
            if(!empty($g['record'])){
                $id=(int)$g['record'];
                if(!$this->db->fetchOne('SELECT id FROM tl_schema_entity WHERE id=?',[$id]))throw new \RuntimeException('Imported entity disappeared. Rescan.');
                foreach($g['homes']??[] as $home){if(!$this->db->fetchOne('SELECT id FROM tl_schema_translation WHERE id=? AND pid=?',[$home,$id]))throw new \RuntimeException('Imported home changed. Rescan.');}
                $this->version('tl_schema_entity',$id,$user,fn()=>$this->db->update('tl_schema_entity',['published'=>'1'],['id'=>$id]));
                foreach($g['homes']??[] as $home)$this->version('tl_schema_translation',(int)$home,$user,fn()=>$this->db->update('tl_schema_translation',['published'=>'1'],['id'=>$home]));
            }
            foreach($g['pageChanges']??[] as $page=>$staged){
                $row=$this->db->fetchAssociative('SELECT * FROM tl_page WHERE id=?',[$page]);
                if(!$row||array_intersect_key($row,$staged['before'])!==$staged['before'])throw new \RuntimeException('Page settings changed since import. Rescan instead of overwriting them.');
                $this->version('tl_page',(int)$page,$user,fn()=>$this->db->update('tl_page',$staged['changes']+['schemaImportedActive'=>'1'],['id'=>$page]));
            }
            $run['importPlan'][$key]['status']='published';++$count;
        }
        if(!$count)throw new \RuntimeException('Select imported drafts to publish.');return $count;
    }
    private function version(string $table,int $id,BackendUser $user,callable $change): void
    {
        $v=new \Contao\Versions($table,$id);$v->setUserId((int)$user->id);$v->setUsername($user->username);$v->setEditUrl('do='.($table==='tl_page'?'page':'schema_manager').'&table='.$table.'&act=edit&id='.$id);$v->initialize();$change();$v->create();
    }
}
