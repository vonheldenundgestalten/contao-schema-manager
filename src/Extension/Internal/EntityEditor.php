<?php
declare(strict_types=1);
namespace VHUG\SchemaManagerBundle\Extension\Internal;
use Doctrine\DBAL\Connection;
use VHUG\SchemaManagerBundle\Extension\V1 as Api;
final class EntityEditor implements Api\EntityEditorInterface
{
    public function __construct(private readonly Connection $connection,private readonly Registry $registry,private readonly Store $store,private readonly FieldValidator $validator,private readonly Runtime $runtime,private readonly Api\BindingManagerInterface $bindings,private readonly Api\SourceInvalidatorInterface $invalidator,private readonly SiteScope $scopes=new SiteScope()) {}
    private function editable(Api\EntityReference $entity,Api\WriteContext $context): array
    {
        $row=$this->store->record($entity->id);
        if (!$row || $row['identityBase']!==$context->identityOrigin) throw new \InvalidArgumentException('Entity outside write scope.');
        if ($source=$this->store->binding($entity->id)) {
            if ($source->installationScope!==$context->read->site || !$adapter=$this->registry->adapter($source->adapterKey)) throw new \InvalidArgumentException('Source unavailable.');
            if ($adapter->describe()->automatic) throw new \InvalidArgumentException('Edit the original source record. Automatic sources have no supplementary entity editor.');
            $snapshot=$adapter->resolve($source,$context->read)->snapshot;
            if (!$snapshot || $snapshot->typeKey!==$row['entityType']) throw new \InvalidArgumentException('Source unavailable or changed type.');
            if ($context->expectedSourceRevision===null || $snapshot->revision!==$context->expectedSourceRevision) throw new \InvalidArgumentException('Source changed; refresh before editing supplementary data.');
        }
        return $row;
    }
    public function saveFields(Api\EntityReference $entity,array $values,bool $localized,int $expectedRevision,Api\WriteContext $context): int
    {
        $context=$this->scopes->write($context);
        $row=$this->editable($entity,$context);if((int)$row['schemaExtensionRevision']!==$expectedRevision)throw new \InvalidArgumentException('Entity changed; reload before saving.');$type=$this->registry->type($row['entityType']);
        if (!$type || (int)$row['schemaExtensionVersion']!==$type->schemaVersion) throw new \InvalidArgumentException('Definition unavailable or migration required.');
        $allowed=[];foreach($type->fields as $field) if($field->localized===$localized)$allowed[$field->key]=$field;
        if(array_diff_key($values,$allowed))throw new \InvalidArgumentException('Unknown field/scope.');
        if($source=$this->store->binding($entity->id)) {
            $definition=$this->registry->adapter($source->adapterKey)->describe();
            if(array_intersect(array_keys($values),$localized?$definition->ownedLocalizedFields:$definition->ownedGlobalFields))throw new \InvalidArgumentException('Source-owned fields cannot be edited, even when empty.');
        }
        $translation=$this->store->translation($entity->id,$context->read->language);
        if($localized && !$translation)throw new \InvalidArgumentException('Create the language record first.');
        $current=Store::decode(($localized?$translation:$row)['schemaExtensionData']??null);
        $merged=array_replace($current,$values);
        $sharedName=!$localized && isset($allowed['name'])?($values['name']??$row['name']):null;
        if(!$localized && isset($allowed['name'])){if(!is_string($sharedName) || trim($sharedName)==='')throw new \InvalidArgumentException('A shared entity name is required.');$merged['name']=$sharedName;}
        // Draft edits validate supplied fields without demanding unrelated required source fields.
        $partial=new Api\TypeDefinition($type->key,$type->label,$type->schemaTypes,$type->mapper,array_map(static fn($f)=>new Api\FieldDefinition($f->key,$f->type,$f->localized,$f->multiple,false,$f->constraints,$f->default,$f->widget,$f->ai),array_values($allowed)));
        $this->validator->validate($partial,$localized?[]:$merged,$localized?$merged:[]);
        foreach($allowed as $field)if($field->type==='reference' && isset($values[$field->key]))foreach($field->multiple?$values[$field->key]:[$values[$field->key]] as $ref){$target=$this->store->record($ref->id);if(!$target || !in_array($target['entityType'],$field->constraints['targetTypes']??[],true))throw new \InvalidArgumentException('Invalid reference target type.');}
        $revision=$this->connection->transactional(function()use($entity,$expectedRevision,$localized,$translation,$merged,$sharedName) {
            $revision=$this->connection->executeStatement('UPDATE tl_schema_entity SET schemaExtensionRevision=schemaExtensionRevision+1,tstamp=? WHERE id=? AND schemaExtensionRevision=?',[time(),$entity->id,$expectedRevision]);
            if($revision!==1)throw new \InvalidArgumentException('Entity changed; reload before saving.');
            $payload=$merged;if($sharedName!==null){unset($payload['name']);$this->connection->update('tl_schema_entity',['name'=>$sharedName],['id'=>$entity->id]);}
            $this->connection->update($localized?'tl_schema_translation':'tl_schema_entity',['schemaExtensionData'=>Store::encode($payload)],['id'=>$localized?$translation['id']:$entity->id]);
            return $expectedRevision+1;
        });
        $this->invalidator->adapterChanged('schema_manager.supplement');
        return $revision;
    }
    public function saveRelationship(Api\EntityReference $entity,string $key,array $values,int $expectedRevision,Api\WriteContext $context): int
    {
        $context=$this->scopes->write($context);
        $row=$this->editable($entity,$context);if((int)$row['schemaExtensionRevision']!==$expectedRevision)throw new \InvalidArgumentException('Entity changed; reload before saving.');$definition=$this->registry->relationship($key);
        if(!$definition || !in_array($row['entityType'],$definition->sourceTypes,true) || (!$definition->multiple && count($values)>1) || count($values)>100) throw new \InvalidArgumentException('Invalid relationship/cardinality.');
        if($source=$this->store->binding($entity->id)) if(in_array($key,$this->registry->adapter($source->adapterKey)->describe()->ownedRelationships,true))throw new \InvalidArgumentException('Source-owned relationship.');
        $targets=[];
        foreach($values as $value) {
            if(!$value instanceof Api\RelationshipValue || ($value->language!==null && (!$definition->localized || $value->language!==$context->read->language)))throw new \InvalidArgumentException('Invalid relationship scope.');
            $ref=$value->target instanceof Api\SourceReference?$this->bindings->resolve($value->target):$value->target;
            $target=$ref?$this->store->record($ref->id):null;
            if(!$target || !in_array($target['entityType'],$definition->targetTypes,true))throw new \InvalidArgumentException('Unbound/incompatible relationship target.');
            if(!$definition->allowCycles) {
                $queue=[$ref->id];$seen=[];
                while($queue) {
                    $next=array_pop($queue);
                    if($next===$entity->id)throw new \InvalidArgumentException('Relationship cycle.');
                    if(isset($seen[$next]))continue;
                    $seen[$next]=true;if(count($seen)>1000)throw new \InvalidArgumentException('Hierarchy budget exceeded.');
                    foreach($this->store->relations($next,$context->read->language)[$key]??[] as $edge) if($edge->target instanceof Api\EntityReference)$queue[]=$edge->target->id;
                }
            }
            $targets[$ref->id]=$value;
        }
        $revision=$this->connection->transactional(function()use($entity,$key,$targets,$definition,$expectedRevision,$context) {
            if($this->connection->executeStatement('UPDATE tl_schema_entity SET schemaExtensionRevision=schemaExtensionRevision+1 WHERE id=? AND schemaExtensionRevision=?',[$entity->id,$expectedRevision])!==1)throw new \InvalidArgumentException('Entity changed; reload before saving.');
            $language=$definition->localized?$context->read->language:'';
            $this->connection->delete('tl_schema_extension_relation',['entity'=>$entity->id,'relationKey'=>$key,'language'=>$language]);
            foreach($targets as $id=>$value)$this->connection->insert('tl_schema_extension_relation',['entity'=>$entity->id,'relationKey'=>$key,'language'=>$language,'target'=>$id,'evidence'=>$value->evidence]);
            return $expectedRevision+1;
        });
        $this->invalidator->adapterChanged('schema_manager.relationship');
        return $revision;
    }
    public function discover(string $adapterKey,Api\CandidateQuery $query): Api\CandidatePage
    {
        $query=new Api\CandidateQuery($this->scopes->read($query->context),$query->search,$query->typeKeys,$query->cursor,$query->limit);
        $adapter=$this->registry->adapter($adapterKey);
        if(!$adapter)throw new \InvalidArgumentException('Adapter unavailable.');
        $page=$adapter->findCandidates($query);
        if(count($page->candidates)>$query->limit)throw new \InvalidArgumentException('Adapter exceeded candidate limit.');
        foreach($page->candidates as $candidate)if($candidate->source->adapterKey!==$adapterKey || $candidate->source->installationScope!==$query->context->site || !in_array($candidate->typeKey,$adapter->describe()->typeKeys,true) || ($query->typeKeys && !in_array($candidate->typeKey,$query->typeKeys,true)))throw new \InvalidArgumentException('Invalid candidate scope/type.');
        return $page;
    }
    public function inspect(Api\EntityReference $entity,Api\ReadContext $context): array
    {
        if(!$context->authorized || !$context->actorId || $context->purpose==='frontend')throw new \InvalidArgumentException('Authorized inspection required.');
        return $this->runtime->render($entity->id,$context);
    }
}
