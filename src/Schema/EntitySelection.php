<?php

declare(strict_types=1);
namespace VHUG\SchemaManagerBundle\Schema;

use Doctrine\DBAL\Connection;
use VHUG\SchemaManagerBundle\Extension\Internal\Registry;
use VHUG\SchemaManagerBundle\Extension\Internal\SiteScope;
use VHUG\SchemaManagerBundle\Extension\V1 as Api;

/** Shared selection policy: published entities whose original source is available. */
final class EntitySelection
{
    public function __construct(private readonly Connection $db, private readonly ?Registry $registry=null, private readonly SiteScope $scopes=new SiteScope()) {}

    public function rows(array $types=[], int $excludeId=0, array $excludeTypes=[], ?int $id=null): array
    {
        $where=["e.published='1'"];$params=[];
        if($types){$where[]='e.entityType IN ('.implode(',',array_fill(0,count($types),'?')).')';$params=array_merge($params,$types);}
        if($excludeTypes){$where[]='e.entityType NOT IN ('.implode(',',array_fill(0,count($excludeTypes),'?')).')';$params=array_merge($params,$excludeTypes);}
        if($excludeId){$where[]='e.id<>?';$params[]=$excludeId;}
        if($id!==null){$where[]='e.id=?';$params[]=$id;}
        $rows=$this->db->fetchAllAssociative('SELECT e.id,e.name,e.entityType,e.addressLocality,e.published,e.identityBase,b.adapterKey,b.installationScope,b.subjectKey FROM tl_schema_entity e LEFT JOIN tl_schema_extension_binding b ON b.entity=e.id WHERE '.implode(' AND ',$where).' ORDER BY e.name,e.id',$params);
        $contexts=null;
        return array_values(array_filter($rows,function(array $row)use(&$contexts):bool{
            if(!$row['adapterKey'])return true;
            $adapter=$this->registry?->adapter($row['adapterKey']);
            if(!$adapter)return false;
            if($contexts===null){
                $contexts=[];
                foreach($this->db->fetchAllAssociative("SELECT id,language FROM tl_page WHERE type='root' ORDER BY id") as $root){
                    if(!$root['language'])continue;
                    $context=$this->scopes->read(new Api\ReadContext('frontend',(string)$root['id'],$root['language'],now:time()));
                    $contexts[$context->site][]=$context;
                }
            }
            foreach($contexts[$row['installationScope']]??[] as $context){
                $source=new Api\SourceReference($row['adapterKey'],$row['installationScope'],$row['subjectKey']);
                try{if($adapter->resolve($source,$context)->snapshot!==null)return true;}catch(\Throwable){continue;}
            }
            return false;
        }));
    }

    public function options(array $types=[], int $excludeId=0, array $excludeTypes=[]): array
    {
        return array_column($this->rows($types,$excludeId,$excludeTypes),'name','id');
    }

    public function contains(int $id): bool
    {
        return $id>0 && $this->rows(id:$id)!==[];
    }
}
