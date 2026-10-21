<?php
declare(strict_types=1);
namespace VHUG\SchemaManagerBundle\Extension\Internal;
use Doctrine\DBAL\Connection;
use VHUG\SchemaManagerBundle\Extension\V1 as Api;
/** AI sees only explicitly enabled public facts; writes use the ordinary revision/ownership API. */
final class AiBridge
{
    public function __construct(private readonly Registry $registry,private readonly Store $store,private readonly Runtime $runtime,private readonly Api\EntityEditorInterface $editor,private readonly Connection $connection,private readonly SiteScope $scopes=new SiteScope()) {}
    public function context(array $roots,string $language,int $actor): array
    {
        $result=[];
        foreach($this->connection->fetchAllAssociative("SELECT * FROM tl_schema_entity WHERE published='1' ORDER BY id") as $row) {
            $type=$this->registry->type($row['entityType']);if(!$type?->ai)continue;
            $source=$this->store->binding((int)$row['id']);$site='';$read=null;$render=null;
            foreach($roots as $root){
                $attempt=$this->scopes->read(new Api\ReadContext('AI',(string)$root,$language,$actor,true,time()));
                // The source-scope equality remains mandatory; only explicit root mappings can match another root.
                if($source && $source->installationScope!==$attempt->site)continue;
                $candidate=$this->runtime->render((int)$row['id'],$attempt);
                if($candidate['node']){$site=(string)$root;$read=$attempt;$render=$candidate;break;}
            }
            if(!$read || !$render)continue;
            $owned=$source?$this->registry->adapter($source->adapterKey)?->describe():null;
            $snapshot=$source?$this->registry->adapter($source->adapterKey)?->resolve($source,$read)->snapshot:null;
            $fields=[];
            foreach($type->fields as $field)if($field->ai && !in_array($field->key,$field->localized?($owned?->ownedLocalizedFields??[]):($owned?->ownedGlobalFields??[]),true))$fields[$field->key]=['type'=>$field->type,'localized'=>$field->localized,'multiple'=>$field->multiple,'constraints'=>array_diff_key($field->constraints,['fields'=>true])];
            $relations=[];foreach($type->relationships as $key){$rel=$this->registry->relationship($key);if($rel?->ai && !in_array($key,$owned?->ownedRelationships??[],true))$relations[$key]=['targets'=>$rel->targetTypes,'multiple'=>$rel->multiple,'editorialOnly'=>$rel->property===null];}
            if(!$fields && !$relations)continue;
            $result['entity:'.$row['id']]=['identityOrigin'=>$row['identityBase'],'id'=>(int)$row['id'],'entityType'=>$type->key,'name'=>$render['node']['name']??$type->label,'site'=>$site,'language'=>$language,'revision'=>(int)$row['schemaExtensionRevision'],'sourceRevision'=>$snapshot?->revision,'fields'=>$fields,'relationships'=>$relations];
        }
        return $result;
    }
    public function validate(array $proposal,array $inventory): array
    {
        $metadata=$inventory['extensions'][$proposal['target']]??null;
        if(!$metadata)throw new \InvalidArgumentException('Extension target not enabled for AI.');
        if(!in_array((int)$metadata['site'],array_map('intval',$inventory['roots']??[]),true))throw new \InvalidArgumentException('Extension target outside analysis scope.');
        $row=$this->store->record($metadata['id']);$type=$row?$this->registry->type($row['entityType']):null;
        if(!$type?->ai)throw new \InvalidArgumentException('Extension AI definition unavailable.');
        try{$value=json_decode($proposal['value'],true,16,JSON_THROW_ON_ERROR);}catch(\JsonException){throw new \InvalidArgumentException('Extension values require typed JSON.');}
        if($proposal['action']==='extension_set') {
            if(!isset($metadata['fields'][$proposal['field']]))throw new \InvalidArgumentException('Extension AI field is not allowed.');
            $field=null;foreach($type->fields as $candidate)if($candidate->key===$proposal['field'] && $candidate->ai)$field=$candidate;
            if(!$field)throw new \InvalidArgumentException('Field permission changed.');
            $partial=new Api\TypeDefinition($type->key,$type->label,$type->schemaTypes,$type->mapper,fields:[$field]);
            (new FieldValidator())->validate($partial,$field->localized?[]:[$field->key=>$value],$field->localized?[$field->key=>$value]:[]);
        } elseif($proposal['action']==='extension_add') {
            $definition=$this->registry->relationship($proposal['field']);
            if(!$definition?->ai || !isset($metadata['relationships'][$proposal['field']]) || !is_int($value) || !isset($inventory['records']['entity:'.$value]))throw new \InvalidArgumentException('Invalid extension AI relationship.');
        } else throw new \InvalidArgumentException('Unsupported extension action.');
        return [$metadata,$value];
    }
    public function apply(array $proposal,array $inventory,int $actor,string $origin): void
    {
        [$metadata,$value]=$this->validate($proposal,$inventory);
        $read=$this->scopes->read(new Api\ReadContext('AI',$metadata['site'],$metadata['language'],$actor,true,time()));
        $write=new Api\WriteContext($read,$metadata['identityOrigin'],$metadata['sourceRevision']);$ref=new Api\EntityReference($metadata['id']);
        if($proposal['action']==='extension_set')$this->editor->saveFields($ref,[$proposal['field']=>$value],$metadata['fields'][$proposal['field']]['localized'],$metadata['revision'],$write);
        else {
            $definition=$this->registry->relationship($proposal['field']);$existing=$this->store->relations($ref->id,$read->language)[$proposal['field']]??[];
            $existing[]=new Api\RelationshipValue(new Api\EntityReference($value),$definition->localized?$read->language:null,$proposal['quote']);
            $this->editor->saveRelationship($ref,$proposal['field'],$existing,$metadata['revision'],$write);
        }
    }
}
