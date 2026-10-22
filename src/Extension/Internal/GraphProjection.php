<?php
declare(strict_types=1);
namespace VHUG\SchemaManagerBundle\Extension\Internal;
use VHUG\SchemaManagerBundle\Extension\V1 as Api;
final class GraphProjection
{
    public function __construct(private readonly Registry $registry,private readonly Store $store,private readonly Api\BindingManagerInterface $bindings, private readonly ?\Doctrine\DBAL\Connection $connection=null) {}
    public function filter(array $rows,int $actor,string $language): array
    {
        return array_values(array_filter($rows,function($row)use($actor,$language){
            if(!$source=$this->store->binding((int)$row['id']))return true;
            $adapter=$this->registry->adapter($source->adapterKey);
            if(!$adapter)return true;
            try { $result=$adapter->resolve($source,new Api\ReadContext('preview',$source->installationScope,$this->sourceLanguage($source,$language),$actor,true,time())); }
            catch(\Throwable){return false;}
            return $result->status!=='forbidden';
        }));
    }
    public function extend(array $map,array $rows,string $language,int $actor=0): array
    {
        $visible=[];foreach($map['nodes'] as &$node){if(str_starts_with($node['data']['id'],'entity-'))$visible[$node['data']['record']??0]=$node['data']['id'];$type=$this->registry->type($node['data']['type']??'');if($type)$node['data']['type']=$type->schemaTypes[0];}unset($node);
        foreach($rows as $row) {
            $id=(int)$row['id'];$relations=$this->store->relations($id,$language);
            $source=$this->store->binding($id);
            if($source && $actor>0 && $adapter=$this->registry->adapter($source->adapterKey)){
                try{$snapshot=$adapter->resolve($source,new Api\ReadContext('preview',$source->installationScope,$this->sourceLanguage($source,$language),$actor,true,time()))->snapshot;foreach($adapter->describe()->ownedRelationships as $key)$relations[$key]=$snapshot?->relationships[$key]??[];}catch(\Throwable){}
            }
            if($source && !$this->registry->adapter($source->adapterKey))foreach($map['nodes'] as &$node)if($node['data']['id']==='entity-'.$id)$node['data']['warnings'][]='sourceUnavailable';unset($node);
            foreach($relations as $key=>$values) {
                $definition=$this->registry->relationship($key);if(!$definition)continue;
                foreach($values as $value) {
                    $target=$value->target instanceof Api\SourceReference?$this->bindings->resolve($value->target):$value->target;
                    if(!$target || !isset($visible[$id],$visible[$target->id]))continue;
                    $editorial=$definition->property===null;
                    $map['edges'][]=['data'=>['source'=>'entity-'.$id,'target'=>'entity-'.$target->id,'label'=>$editorial?$definition->label.' (editorial only)':$definition->property,'editorialOnly'=>$editorial,'relationshipKey'=>$key]];
                }
            }
        }
        return $map;
    }
    private function sourceLanguage(Api\SourceReference $source,string $fallback): string
    {
        // Graph labels follow the editor's UI locale; source resolution follows
        // the configured source root. This creates no frontend translation.
        if ($this->connection && ctype_digit($source->installationScope)) {
            $language=$this->connection->fetchOne("SELECT language FROM tl_page WHERE id=? AND type='root'",[(int)$source->installationScope]);
            if (is_string($language) && $language!=='') return $language;
        }
        return $fallback;
    }

}
