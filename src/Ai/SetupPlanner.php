<?php
declare(strict_types=1);
namespace VHUG\SchemaManagerBundle\Ai;
use Contao\BackendUser;
use Doctrine\DBAL\Connection;
/** Editorial configuration review. No provider call and no inferred archive classification. */
final class SetupPlanner
{
    public function __construct(private readonly Connection $db,private readonly SiteInventory $inventory,private readonly RunStore $store) {}
    public function prepare(BackendUser $user,int $root,bool $multilingual): int
    {
        $inventory=$this->inventory->collect($root,$user,$multilingual);
        $records=[];$proposals=[];$parents=[];
        $organizations=$this->db->fetchAllAssociative("SELECT id,name,published,identityBase FROM tl_schema_entity WHERE entityType IN ('Organization','LocalBusiness') ORDER BY name");
        $choices=[];foreach($organizations as $org)$choices[(int)$org['id']]=$org;
        $candidate=count($choices)===1?(int)array_key_first($choices):0;
        $add=function(string $key,array $row,array $values,string $reason)use(&$records,&$proposals):void{
            $records[$key]=$row;
            foreach($values as $field=>$value)$proposals[]=['action'=>'set','target'=>$key,'field'=>$field,'value'=>(string)$value,'old'=>(string)($row[$field]??''),'reason'=>$reason,'source'=>'configuration','quote'=>'','status'=>'pending','error'=>'','fingerprint'=>hash('sha256',$key.':'.$field.':'.$value),'bulk'=>false];
        };
        foreach($inventory['roots'] as $id){
            $row=$this->db->fetchAssociative('SELECT * FROM tl_page WHERE id=?',[$id]);
            $parents[$id]=true;
            $add('root:'.$id,array_intersect_key($row,array_flip(['id','title','schemaPublisher','schemaSiteName'])),['schemaPublisher'=>$row['schemaPublisher']?:$candidate,'schemaSiteName'=>$row['schemaSiteName']?:$row['title']],'websiteImpact');
        }
        // Reader pages can require an item. Use their ancestor chain, not source-page eligibility.
        $inScope=function(int $page)use($parents):bool{
            $seen=[];while($page&&!isset($seen[$page])){$seen[$page]=true;$row=$this->db->fetchAssociative('SELECT id,pid,type,published,protected,start,stop FROM tl_page WHERE id=?',[$page]);
                if(!$row||!empty($row['protected']))return false;
                if($row['type']==='root')return isset($parents[(int)$row['id']]);
                if(count($seen)===1 && (empty($row['published'])||(!empty($row['start'])&&$row['start']>time())||(!empty($row['stop'])&&$row['stop']<=time())))return false;
                $page=(int)$row['pid'];}return false;
        };
        if($this->db->createSchemaManager()->tablesExist(['tl_news_archive']))foreach($this->db->fetchAllAssociative('SELECT * FROM tl_news_archive ORDER BY title,id') as $row){
            if(!empty($row['protected'])||!$inScope((int)$row['jumpTo']))continue;
            $add('archive:'.$row['id'],array_intersect_key($row,array_flip(['id','title','schemaType','schemaPublisher'])),['schemaType'=>$row['schemaType'],'schemaPublisher'=>$row['schemaPublisher']?:$candidate],'archiveImpact');
        }
        $calendars=[];
        if($this->db->createSchemaManager()->tablesExist(['tl_calendar']))foreach($this->db->fetchAllAssociative('SELECT * FROM tl_calendar ORDER BY title,id') as $row){if(empty($row['protected'])&&$inScope((int)$row['jumpTo'])){
            $fields=array_merge(['schemaMode'],array_keys(\VHUG\SchemaManagerBundle\Schema\CalendarEventFields::defaults()));$values=[];foreach($fields as $field)$values[$field]=$row[$field]??'';$values['schemaOrganizer']=$values['schemaOrganizer']?:$candidate;
            $add('calendar:'.$row['id'],array_intersect_key($row,array_flip(array_merge(['id','title'],$fields))),$values,'calendarImpact');
        }}
        return $this->store->create((int)$user->id,$root,['root'=>$root,'mode'=>'improve','stage'=>'configuration','configuration'=>true,'inventory'=>['records'=>$records,'pages'=>[],'sources'=>[],'roots'=>$inventory['roots']],'organizations'=>$choices,'calendars'=>$calendars,'proposals'=>$proposals,'queue'=>[],'processed'=>[],'mapped'=>[],'decisions'=>[],'warnings'=>[],'usage'=>['input_tokens'=>0,'output_tokens'=>0],'status'=>'complete','createdAt'=>time()]);
    }
}
