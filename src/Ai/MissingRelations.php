<?php
declare(strict_types=1);
namespace VHUG\SchemaManagerBundle\Ai;
use Contao\BackendUser;
use Contao\StringUtil;
use Doctrine\DBAL\Connection;

/** Database-backed cleanup; absent from a scan does not mean deleted. */
final class MissingRelations
{
    public function __construct(private readonly Connection $db) {}
    private function fields(string $table,string $type): array
    {
        $fields=FieldPolicy::links($table,$type);
        if($table==='tl_schema_entity'&&in_array($type,['Organization','LocalBusiness','Service'],true))$fields['subservices']=['Service'];
        if($table==='tl_news')$fields+=['schemaAuthor'=>['Person'],'schemaJobEmployer'=>['Organization','LocalBusiness']];
        return $fields;
    }
    private function scalar(string $field): bool { return in_array($field,['organization','schemaPerson','schemaAuthor','schemaJobEmployer'],true); }
    private function table(string $target): array
    {
        if(!preg_match('/^(entity|page|news|author):([1-9][0-9]*)$/D',$target,$m))throw new \RuntimeException('Invalid relationship owner.');
        return [match($m[1]){'entity'=>'tl_schema_entity','page'=>'tl_page','news'=>'tl_news','author'=>'tl_user'},(int)$m[2]];
    }
    public function propose(array &$run): void
    {
        if(($run['mode']??'')!=='improve'||($run['stage']??'content')!=='content')return;
        $known=array_fill_keys(array_map('intval',$this->db->fetchFirstColumn('SELECT id FROM tl_schema_entity')),true);
        foreach($run['inventory']['records'] as $target=>$record){
            if(!preg_match('/^(entity|page|news|author):/',$target))continue;
            $source=isset($run['inventory']['sources'][$target])?$target:null;
            if(str_starts_with($target,'entity:'))foreach($run['inventory']['records'] as $key=>$home){if(str_starts_with($key,'translation:')&&(int)$home['pid']===(int)$record['id']&&isset($run['inventory']['sources']['page:'.$home['page']])){$source='page:'.$home['page'];break;}}
            if(str_starts_with($target,'author:'))foreach($run['inventory']['records'] as $key=>$news){if(($news['_authorTarget']??'')===$target&&isset($run['inventory']['sources'][$key])){$source=$key;break;}}
            if(!$source)continue;
            [$table,$id]=$this->table($target);$row=$this->db->fetchAssociative('SELECT * FROM '.$table.' WHERE id=?',[$id]);if(!$row)continue;
            foreach($this->fields($table,$row['entityType']??'') as $field=>$types){
                if(!array_key_exists($field,$row))continue;
                $ids=$this->scalar($field)?[(int)$row[$field]]:array_map('intval',StringUtil::deserialize($row[$field],true));
                foreach(array_unique($ids) as $missing){
                    if($missing<1||isset($known[$missing]))continue;
                    $fingerprint=hash('sha256','missing:'.$target.':'.$field.':'.$missing);
                    if(isset($run['decisions'][$fingerprint])||array_filter($run['proposals'],static fn($p)=>$p['fingerprint']===$fingerprint))continue;
                    $run['proposals'][]=['action'=>'remove','target'=>$target,'field'=>$field,'value'=>'entity:'.$missing,'source'=>$source,'quote'=>'Stored '.$field.' references Schema Manager record #'.$missing.', which no longer exists.','reason'=>'Remove this broken relationship. Other links and existing entities are kept. Draft and unpublished targets are never considered missing.','databaseEvidence'=>true,'old'=>$row[$field],'fingerprint'=>$fingerprint,'status'=>'pending','error'=>'','bulk'=>true];
                }
            }
        }
    }
    public function apply(array $p,array $run,BackendUser $user): string
    {
        if(!$user->isAdmin||($run['mode']??'')!=='improve'||($run['stage']??'content')!=='content'||empty($p['databaseEvidence'])||!isset($run['inventory']['records'][$p['target']]))throw new \RuntimeException('Only reviewed improvement cleanup is allowed.');
        [$table,$id]=$this->table($p['target']);$row=$this->db->fetchAssociative('SELECT * FROM '.$table.' WHERE id=? FOR UPDATE',[$id]);
        if(!$row||!isset($this->fields($table,$row['entityType']??'')[$p['field']]))throw new \RuntimeException('Relationship owner changed. Run improvement analysis again.');
        if(array_key_exists('published',$row)&&empty($row['published']))throw new \RuntimeException('The relationship owner is no longer published.');
        if(!preg_match('/^entity:([1-9][0-9]*)$/D',$p['value'],$match))throw new \RuntimeException('Invalid missing entity.');$missing=(int)$match[1];
        if($this->db->fetchOne('SELECT id FROM tl_schema_entity WHERE id=?',[$missing]))throw new \RuntimeException('This entity now exists. Its relationship was kept.');
        $field=$p['field'];$ids=$this->scalar($field)?[(int)$row[$field]]:array_map('intval',StringUtil::deserialize($row[$field],true));
        if(!in_array($missing,$ids,true))throw new \RuntimeException('This relationship has already changed. Reload or rescan.');
        $value=$this->scalar($field)?0:serialize(array_values(array_filter($ids,static fn($related)=>$related!==$missing)));
        $version=new \Contao\Versions($table,$id);$version->setUserId((int)$user->id);$version->setUsername($user->username);$version->setEditUrl('do='.match($table){'tl_page'=>'page','tl_news'=>'news','tl_user'=>'user',default=>'schema_manager'}.'&table='.$table.'&act=edit&id='.$id);$version->initialize();
        $this->db->update($table,[$field=>$value,'tstamp'=>time()],['id'=>$id]);$version->create();
        return $table.':'.$id;
    }
}
