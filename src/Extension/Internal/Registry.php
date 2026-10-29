<?php
declare(strict_types=1);
namespace VHUG\SchemaManagerBundle\Extension\Internal;
use VHUG\SchemaManagerBundle\Extension\V1 as Api;
final class Registry
{
    private array $types=[];
    private array $relationships=[];
    private array $adapters=[];
    public function __construct(iterable $typeProviders=[], iterable $relationshipProviders=[], iterable $adapters=[], private readonly iterable $mappers=[], private readonly iterable $validators=[])
    {
        foreach ($typeProviders as $provider) foreach ($provider->types() as $type) {
            if (!$type instanceof Api\TypeDefinition || isset($this->types[$type->key])) throw new \LogicException('Duplicate or invalid extension type.');
            self::key($type->key);
            if (!$type->schemaTypes || $type->schemaVersion<1) throw new \LogicException('Invalid type '.$type->key);
            foreach ($type->schemaTypes as $schema) if (!preg_match('~^(?:https://schema.org/)?[A-Z][A-Za-z0-9]+$~D',$schema)) throw new \LogicException('Invalid vocabulary type.');
            $fields=[];
            foreach ($type->fields as $field) {
                if (!$field instanceof Api\FieldDefinition || isset($fields[$field->key]) || !preg_match('/^[a-z][A-Za-z0-9_]*$/D',$field->key)) throw new \LogicException('Invalid or duplicate field.');
                if (!in_array($field->type,['text','url','date','datetime','boolean','integer','decimal','enum','measurement','reference','structure'],true) || !in_array($field->widget,['text','textarea','checkbox','select','measurement','reference','structure'],true)) throw new \LogicException('Unsupported field shape/widget.');
                if($field->type==='reference' && empty($field->constraints['targetTypes']))throw new \LogicException('Reference fields require targetTypes.');
                $fields[$field->key]=$field;
            }
            foreach (array_merge($type->properties,array_keys($type->slots)) as $property) self::property($property);
            foreach ($type->schemaTypes as $schema) if (!Vocabulary::is($schema,'Thing')) throw new \LogicException('Unknown schema type: '.$schema);
            if(in_array('url',$type->properties,true))throw new \LogicException('Core owns the canonical URL.');
            foreach ($type->properties as $property) Vocabulary::property($type->schemaTypes,$property);
            foreach($type->slots as $slot=>$slotTypes){if(!in_array($slot,$type->properties,true) || !is_array($slotTypes) || !$slotTypes)throw new \LogicException('Invalid typed slot.');Vocabulary::property($type->schemaTypes,$slot,$slotTypes);}
            $this->types[$type->key]=$type;
        }
        foreach ($relationshipProviders as $provider) foreach ($provider->relationships() as $relation) {
            if (!$relation instanceof Api\RelationshipDefinition || isset($this->relationships[$relation->key])) throw new \LogicException('Duplicate or invalid relationship.');
            $relation=new Api\RelationshipDefinition($relation->key,$relation->label,$this->expand($relation->sourceTypes),$this->expand($relation->targetTypes),$relation->property,$relation->multiple,$relation->localized,$relation->slot,$relation->pending,$relation->allowCycles,$relation->projection,$relation->ai);
            self::key($relation->key);
            if (!$relation->sourceTypes || !$relation->targetTypes) throw new \LogicException('Relationship requires endpoints.');
            if ($relation->property!==null) self::property($relation->property);
            if ($relation->slot!==null && $relation->property===null) throw new \LogicException('Editorial relationship cannot target a public slot.');
            foreach (array_merge($relation->sourceTypes,$relation->targetTypes) as $key) if (!$this->known($key)) throw new \LogicException('Unknown relationship endpoint '.$key);
            foreach ($relation->sourceTypes as $key) if (isset($this->types[$key]) && $relation->slot!==null && !array_key_exists($relation->slot,$this->types[$key]->slots)) throw new \LogicException('Unknown relationship slot.');
            if (array_diff($relation->projection,['@id','@type','name','url','address','geo','hasMap'])) throw new \LogicException('Unsafe compact projection.');
            if ($relation->property!==null) foreach ($relation->sourceTypes as $source) foreach ($relation->targetTypes as $target) {
                $sourceTypes=$relation->slot!==null?($this->types[$source]->slots[$relation->slot]??[]):($this->types[$source]->schemaTypes??[$source]);
                Vocabulary::property($sourceTypes,$relation->property,$this->types[$target]->schemaTypes??[$target],$relation->pending);
            }
            $this->relationships[$relation->key]=$relation;
        }
        foreach ($this->types as $type) {
            foreach($type->fields as $field)if($field->type==='reference')foreach($field->constraints['targetTypes'] as $target)if(!$this->known($target))throw new \LogicException('Unknown reference target type.');
            $this->mapper($type->mapper);
            foreach ($type->validators as $validator) $this->validator($validator);
            foreach ($type->relationships as $key) if (!isset($this->relationships[$key]) || !in_array($type->key,$this->relationships[$key]->sourceTypes,true)) throw new \LogicException('Unknown/incompatible relationship '.$key);
        }
        foreach ($adapters as $adapter) {
            self::key($adapter->key());
            if (isset($this->adapters[$adapter->key()])) throw new \LogicException('Duplicate adapter '.$adapter->key());
            $description=$adapter->describe();
            $reserved=['id','pid','entityId','entityType','identityBase','published','schemaExtensionVersion','schemaExtensionRevision','schemaExtensionData','url'];
            if(array_intersect(array_merge($description->ownedGlobalFields,$description->ownedLocalizedFields),$reserved) || array_intersect($description->ownedGlobalFields,$description->ownedLocalizedFields))throw new \LogicException('Source cannot own identity, routing, publication or conflicting field scopes.');
            foreach($description->ownedRelationships as $key)if(!isset($this->relationships[$key]))throw new \LogicException('Unknown source-owned relationship.');
            foreach($description->typeKeys as $key)if($type=$this->type($key)){
                $global=[];$localized=[];foreach($type->fields as $field){if($field->localized)$localized[]=$field->key;else $global[]=$field->key;}
                if(array_diff($description->ownedGlobalFields,$global) || array_diff($description->ownedLocalizedFields,$localized))throw new \LogicException('Source ownership does not match field definitions.');
            }
            foreach($description->typeKeys as $key)if(!$this->type($key)){[$global,$localized]=NativeFacts::fields($key);if(array_diff($description->ownedGlobalFields,$global) || array_diff($description->ownedLocalizedFields,$localized))throw new \LogicException('Unsupported native source fact; register a type definition for richer fields.');}
            foreach ($description->typeKeys as $key) if (!$this->known($key)) throw new \LogicException('Unknown adapter type '.$key);
            $this->adapters[$adapter->key()]=$adapter;
        }
    }
    private function expand(array $selectors): array
    {
        $builtin=['Organization'=>['organization'],'LocalBusiness'=>['organization','place'],'Place'=>['place'],'Person'=>['person'],'Service'=>['service'],'Product'=>['product'],'SoftwareApplication'=>['software','creative_work'],'Event'=>['event']];
        foreach($this->types as $key=>$type)$builtin[$key]=$type->capabilities;
        $keys=[];
        foreach($selectors as $selector){
            if(!str_starts_with($selector,'capability:')){$keys[]=$selector;continue;}
            $found=false;$capability=substr($selector,11);
            foreach($builtin as $key=>$capabilities)if(in_array($capability,$capabilities,true)){$keys[]=$key;$found=true;}
            if(!$found)throw new \LogicException('Unknown/empty capability selector '.$selector);
        }
        return array_values(array_unique($keys));
    }
    public static function key(string $key): void { if (strlen($key)>128 || !preg_match('/^[a-z][a-z0-9_]*(?:\.[a-z][a-z0-9_]*)+$/D',$key)) throw new \LogicException('Expected namespaced extension key: '.$key); }
    public static function property(string $key): void { if (!preg_match('/^[a-z][A-Za-z0-9]*$/D',$key)) throw new \LogicException('Invalid public property '.$key); }
    public function known(string $key): bool { return isset($this->types[$key]) || in_array($key,['Organization','LocalBusiness','Place','Person','Service','Product','SoftwareApplication','Event'],true); }
    public function placeTypes(): array
    {
        $keys=['Place','LocalBusiness'];
        foreach($this->types as $type)if(in_array('place',$type->capabilities,true))$keys[]=$type->key;
        return array_values(array_unique($keys));
    }
    public function types(): array { return $this->types; }
    public function relationships(): array { return $this->relationships; }
    public function adapters(): array { return $this->adapters; }
    public function type(string $key): ?Api\TypeDefinition { return $this->types[$key]??null; }
    public function relationship(string $key): ?Api\RelationshipDefinition { return $this->relationships[$key]??null; }
    public function adapter(string $key): ?Api\SourceAdapterInterface { return $this->adapters[$key]??null; }
    public function mapper(string $key): Api\EntityTypeMapperInterface { foreach ($this->mappers as $id=>$mapper) if ($id===$key) return $mapper; throw new \LogicException('Missing extension mapper '.$key); }
    public function validator(string $key): Api\EntityTypeValidatorInterface { foreach ($this->validators as $id=>$validator) if ($id===$key) return $validator; throw new \LogicException('Missing extension validator '.$key); }
}
