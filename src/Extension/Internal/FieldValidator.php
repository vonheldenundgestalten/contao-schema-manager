<?php
declare(strict_types=1);
namespace VHUG\SchemaManagerBundle\Extension\Internal;
use VHUG\SchemaManagerBundle\Extension\V1 as Api;
final class FieldValidator
{
    public function validate(Api\TypeDefinition $type,array $global,array $localized): array
    {
        $values=[];$known=[];
        foreach ($type->fields as $field) {
            $known[$field->key]=true;
            $input=$field->localized?$localized:$global;
            $value=array_key_exists($field->key,$input)?$input[$field->key]:$field->default;
            $required=$field->required;
            foreach ($field->constraints['requiredIf']??[] as $key=>$expected) if ((($global+$localized)[$key]??null) === $expected) $required=true;
            if ($value===null || $value==='' || ($field->multiple && $value===[])) { if ($required) throw new \InvalidArgumentException('Required field: '.$field->key); continue; }
            $items=$field->multiple?$value:[$value];
            if (!is_array($items) || ($field->multiple && !array_is_list($items)) || count($items)>($field->constraints['maxItems']??100)) throw new \InvalidArgumentException('Invalid cardinality: '.$field->key);
            foreach ($items as $item) $this->value($field,$item);
            $values[$field->key]=$value;
        }
        if (array_diff_key($global+$localized,$known)) throw new \InvalidArgumentException('Unknown extension field.');
        foreach ($type->fields as $field) if (array_key_exists($field->key,$field->localized?$global:$localized)) throw new \InvalidArgumentException('Wrong field scope: '.$field->key);
        return $values;
    }
    private function value(Api\FieldDefinition $field,mixed $value): void
    {
        $valid=match($field->type) {
            'text'=>is_string($value) && mb_strlen($value)<=($field->constraints['maxLength']??10000),
            'url'=>is_string($value) && filter_var($value,FILTER_VALIDATE_URL) && in_array(parse_url($value,PHP_URL_SCHEME),['http','https'],true) && !parse_url($value,PHP_URL_USER),
            'date'=>is_string($value) && ($d=\DateTimeImmutable::createFromFormat('!Y-m-d',$value)) && $d->format('Y-m-d')===$value,
            'datetime'=>is_string($value) && preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(?:Z|[+-]\d{2}:\d{2})$/D',$value) && ($d=\DateTimeImmutable::createFromFormat('Y-m-d\TH:i:sP',$value)) && !\DateTimeImmutable::getLastErrors(),
            'boolean'=>is_bool($value), 'integer'=>is_int($value),
            'decimal'=>is_string($value) && preg_match('/^-?[0-9]+(?:\.[0-9]+)?$/D',$value),
            'enum'=>is_string($value) && in_array($value,$field->constraints['choices']??[],true),
            'measurement'=>is_array($value) && isset($value['value'],$value['unit'],$value['basis']) && is_string($value['value']) && preg_match('/^-?[0-9]+(?:\.[0-9]+)?$/D',$value['value']) && is_string($value['unit']) && $value['unit']!=='' && is_string($value['basis']) && $value['basis']!=='',
            'reference'=>$value instanceof Api\EntityReference,
            'structure'=>is_array($value) && !array_is_list($value) && isset($field->constraints['fields']),
            default=>false,
        };
        if (!$valid) throw new \InvalidArgumentException('Invalid '.$field->type.' field: '.$field->key);
        if (in_array($field->type,['integer','decimal'],true)) {
            if (isset($field->constraints['min']) && $value<$field->constraints['min'] || isset($field->constraints['max']) && $value>$field->constraints['max']) throw new \InvalidArgumentException('Field outside bounds: '.$field->key);
        }
        if ($field->type==='structure') {
            $nested=new Api\TypeDefinition('internal.structure','structure',['Thing'],'unused',fields:$field->constraints['fields']);
            $this->validate($nested,$value,[]);
        }
    }
}
