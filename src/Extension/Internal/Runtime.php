<?php
declare(strict_types=1);
namespace VHUG\SchemaManagerBundle\Extension\Internal;
use VHUG\SchemaManagerBundle\Extension\V1 as Api;
use VHUG\SchemaManagerBundle\Schema\EntityMapper;
final class Runtime
{
    public function __construct(private readonly Registry $registry,private readonly Store $store,private readonly FieldValidator $fields,private readonly Api\BindingManagerInterface $bindings,private readonly EntityMapper $nativeMapper,private readonly bool $allowPending=false,private readonly SiteScope $scopes=new SiteScope()) {}
    public function scope(int $id,string $fallback): string { return $this->store->binding($id)?->installationScope??$fallback; }
    public function handles(array $record): bool { return str_contains($record['entityType'],'.') || $this->store->binding((int)$record['id'])!==null; }
    public function render(int $id,Api\ReadContext $context,?string $manualUrl=null,bool $full=true,bool $pending=false,?string $currentUrl=null): array
    {
        $context=$this->scopes->read($context);
        $pending=$pending || $this->allowPending;
        $cacheTags=[SourceInvalidator::TAG];$nodes=[];$diagnostics=[];$snapshots=[];$visiting=[];$expiry=null;
        $load=function(int $entityId,int $depth=0) use (&$load,&$nodes,&$diagnostics,&$snapshots,&$visiting,&$expiry,&$cacheTags,$context,$manualUrl,$full,$pending,$id,$currentUrl): ?array {
            if (isset($visiting[$entityId])) return $visiting[$entityId];
            if (count($visiting)>=100 || $depth>1) return null;
            $row=$this->store->record($entityId);
            if (!$row || empty($row['entityId']) || (empty($row['published']) && $context->purpose!=='preview')) return null;
            $translation=$this->store->translation($entityId,$context->language);
            if ($translation && empty($translation['published']) && $context->purpose!=='preview') $translation=null;
            $type=$this->registry->type($row['entityType']);
            if (str_contains($row['entityType'],'.') && !$type) { $diagnostics[]='definition_unavailable'; return null; }
            if ($type && (int)$row['schemaExtensionVersion']!==$type->schemaVersion) { $diagnostics[]='migration_required'; return null; }
            $global=$type?Store::decode($row['schemaExtensionData']??null):$row;
            if($type)foreach($type->fields as $field)if($field->key==='name' && !$field->localized)$global['name']=$row['name'];
            $localized=$type?Store::decode($translation['schemaExtensionData']??null):($translation??[]);
            $relations=$this->store->relations($entityId,$context->language);
            $url=$entityId===$id?$manualUrl:null;$provenance=[];
            if ($source=$this->store->binding($entityId)) {
                $adapter=$this->registry->adapter($source->adapterKey);
                if (!$adapter || $source->installationScope!==$context->site) { $diagnostics[]='source_unavailable'; return null; }
                $resolution=$snapshots[$source->key()]??=$adapter->resolve($source,$context);
                if($resolution->nextVisibilityChange!==null)$expiry=min($expiry??PHP_INT_MAX,$resolution->nextVisibilityChange);
                foreach($resolution->cacheTags as $tag){if(!is_string($tag) || strlen($tag)>255 || preg_match('/[\x00-\x20]/',$tag))throw new \InvalidArgumentException('Invalid source cache tag.');$cacheTags[]=$tag;}
                $snapshot=$resolution->snapshot;
                if (!$snapshot || $snapshot->source->key()!==$source->key() || $snapshot->typeKey!==$row['entityType']) { $diagnostics[]='source_'.$resolution->status; return null; }
                if(!$type)NativeFacts::validate($snapshot);
                $owned=$adapter->describe();
                if (array_diff(array_keys($snapshot->globalFields),$owned->ownedGlobalFields) || array_diff(array_keys($snapshot->localizedFields),$owned->ownedLocalizedFields) || array_diff(array_keys($snapshot->relationships),$owned->ownedRelationships)) throw new \InvalidArgumentException('Adapter returned undeclared fields.');
                foreach ($owned->ownedGlobalFields as $key) { unset($global[$key]); $global[$key]=$snapshot->globalFields[$key]??null;$provenance[$key]='source'; }
                foreach ($owned->ownedLocalizedFields as $key) { unset($localized[$key]); $localized[$key]=$snapshot->localizedFields[$key]??null;$provenance[$key]='source'; }
                foreach ($owned->ownedRelationships as $key) $relations[$key]=$snapshot->relationships[$key]??[];
                foreach($snapshot->cacheTags as $tag){if(!is_string($tag) || strlen($tag)>255 || preg_match('/[\x00-\x20]/',$tag))throw new \InvalidArgumentException('Invalid source cache tag.');$cacheTags[]=$tag;}
                $url=$snapshot->url;
                if ($snapshot->nextVisibilityChange!==null) $expiry=min($expiry??PHP_INT_MAX,$snapshot->nextVisibilityChange);
            }
            $complete=$full || ($url!==null && $currentUrl!==null && rtrim($url,'/')===rtrim($currentUrl,'/'));
            if ($type) {
                $values=$this->fields->validate($type,$global,$localized);
                $allowedReferences=[];
                foreach ($type->fields as $field) if ($field->type==='reference' && isset($values[$field->key])) {
                    $references=$field->multiple?$values[$field->key]:[$values[$field->key]];$safeReferences=[];
                    foreach($references as $reference){
                        $target=$this->store->record($reference->id);
                        if(!$target || !in_array($target['entityType'],$field->constraints['targetTypes']??[],true))continue;
                        $targetNode=$load($reference->id,$depth+1);
                        if($targetNode){$safeReferences[]=['@id'=>$targetNode['@id']];$allowedReferences[$targetNode['@id']]=(array)$targetNode['@type'];}
                    }
                    if($safeReferences)$values[$field->key]=$field->multiple?$safeReferences:$safeReferences[0];else unset($values[$field->key]);
                }
                $resolved=new Api\ResolvedEntity(new Api\EntityReference($entityId),$row['entityId'],$type,$values,[],$url,$provenance);
                foreach ($type->validators as $validator) foreach ($this->registry->validator($validator)->validate($resolved,new Api\ValidationContext($context)) as $violation) throw new \InvalidArgumentException($violation->code);
                $contribution=$this->registry->mapper($type->mapper)->map($resolved,new Api\MappingContext($context));
                if (array_diff(array_keys($contribution->properties),$type->properties)) throw new \InvalidArgumentException('Mapper returned undeclared properties.');
                $this->safe($contribution->properties,0,$allowedReferences);
                Vocabulary::node($type->schemaTypes,$contribution->properties,$allowedReferences);
                $node=['@id'=>$row['entityId'],'@type'=>count($type->schemaTypes)===1?$type->schemaTypes[0]:$type->schemaTypes]+$contribution->properties;
                if ($url!==null) $node['url']=$url;
            } else { $node=$this->nativeMapper->map($global,$localized,$url);$node['@id']=\VHUG\SchemaManagerBundle\Schema\ImportedSchema::identity($localized['schemaImportedData']??null)??$row['entityId'];$node['@type']=$row['entityType']; }
            $compact=array_intersect_key($node,array_flip(['@id','@type','name','url']));
            $visiting[$entityId]=$node;
            if ($depth===0 && $complete) foreach ($relations as $key=>$targets) {
                $definition=$this->registry->relationship($key);
                if (!$definition || !in_array($row['entityType'],$definition->sourceTypes,true) || ($definition->pending && !$pending)) { $diagnostics[]='relationship_unavailable'; continue; }
                if ($definition->property===null) continue;
                if (!$definition->multiple && count($targets)>1) throw new \InvalidArgumentException('Relationship cardinality exceeded.');
                $refs=[];
                foreach ($targets as $value) {
                    if (!$value instanceof Api\RelationshipValue) throw new \InvalidArgumentException('Invalid relationship value.');
                    if ($value->language!==null && $value->language!==$context->language) continue;
                    $ref=$value->target instanceof Api\SourceReference?$this->bindings->resolve($value->target):$value->target;
                    $target=$ref?$this->store->record($ref->id):null;
                    if (!$target || !in_array($target['entityType'],$definition->targetTypes,true)) { $diagnostics[]='target_unresolved'; continue; }
                    $targetNode=$load($ref->id,$depth+1);
                    if ($targetNode) {
                        $projected=array_intersect_key($targetNode,array_flip($definition->projection));
                        $refs[$targetNode['@id']]=$projected;
                        $nodes[$targetNode['@id']]=array_replace($nodes[$targetNode['@id']]??[],$projected);
                    }
                }
                if ($definition->property===null || !$refs) continue;
                ksort($refs);$refs=array_values($refs);$value=$definition->multiple?$refs:$refs[0];
                if ($definition->slot!==null) {
                    if (!isset($node[$definition->slot]) || !is_array($node[$definition->slot]) || array_is_list($node[$definition->slot])) throw new \InvalidArgumentException('Missing singular mapper slot.');
                    $node[$definition->slot][$definition->property]=$value;
                } else $node[$definition->property]=$value;
            }
            $nodes[$row['entityId']]=($depth===0 && $complete)?$node:$compact;
            return $depth===0?$nodes[$row['entityId']]:$node;
        };
        try { $main=$load($id); } catch (\Throwable) { return ['node'=>null,'nodes'=>[],'diagnostics'=>['extension_resolution_failed'],'expiry'=>null,'cacheTags'=>[SourceInvalidator::TAG]]; }
        ksort($nodes);
        return ['node'=>$main,'nodes'=>array_values($nodes),'diagnostics'=>array_values(array_unique($diagnostics)),'expiry'=>$expiry,'cacheTags'=>array_values(array_unique($cacheTags))];
    }
    public function augment(array $node,int $id,Api\ReadContext $context): array
    {
        $applicable=false;foreach($this->registry->relationships() as $definition)if($definition->property!==null && in_array($node['@type'],$definition->sourceTypes,true))$applicable=true;
        if(!$applicable)return ['node'=>$node,'nodes'=>[],'cacheTags'=>[],'expiry'=>null];
        $result=$this->render($id,$context,$node['url']??null,true);
        if(!$result['node'])return ['node'=>$node,'nodes'=>[],'cacheTags'=>[],'expiry'=>null];
        foreach($this->registry->relationships() as $definition) {
            if($definition->property===null || !in_array($node['@type'],$definition->sourceTypes,true))continue;
            if($definition->slot===null && isset($result['node'][$definition->property]) && !isset($node[$definition->property]))$node[$definition->property]=$result['node'][$definition->property];
        }
        return ['node'=>$node,'cacheTags'=>$result['cacheTags'],'expiry'=>$result['expiry'],'nodes'=>array_values(array_filter($result['nodes'],static fn($target)=>$target['@id']!==$node['@id']))];
    }
    private function safe(mixed $value,int $depth=0,array $allowedReferences=[]): void
    {
        if ($depth>16 || is_object($value) || is_resource($value)) throw new \InvalidArgumentException('Unsafe mapper value.');
        if (!is_array($value)) return;
        foreach ($value as $key=>$child) {
            if (is_string($key) && str_starts_with($key,'@') && $key!=='@type' && !($key==='@id' && isset($allowedReferences[$child]))) throw new \InvalidArgumentException('Mapper cannot emit identities/context.');
            $this->safe($child,$depth+1,$allowedReferences);
        }
    }
}
