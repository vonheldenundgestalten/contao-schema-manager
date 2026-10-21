<?php
declare(strict_types=1);
namespace VHUG\SchemaManagerBundle\Extension\Internal;
final class Vocabulary
{
    private static ?array $data=null;
    private static function data(): array { return self::$data??=json_decode(file_get_contents(dirname(__DIR__,3).'/resources/schema-vocabulary.json'),true,32,JSON_THROW_ON_ERROR); }
    public static function name(string $type): string { return str_replace('https://schema.org/','',$type); }
    public static function is(string $type,string $parent,array $seen=[]): bool
    {
        $type=self::name($type);$parent=self::name($parent);
        if($type===$parent)return true;
        if(isset($seen[$type]))return false;$seen[$type]=true;
        foreach(self::data()['types'][$type]??[] as $base)if(self::is($base,$parent,$seen))return true;
        return false;
    }
    public static function property(array $types,string $property,?array $targets=null,bool $pending=false): void
    {
        $definition=self::data()['properties'][$property]??null;
        if(!$definition || ($definition['pending'] && !$pending))throw new \LogicException('Unknown or unapproved pending property: '.$property);
        $valid=false;foreach($types as $type)foreach($definition['domain'] as $domain)if(self::is($type,$domain))$valid=true;
        if(!$valid)throw new \LogicException('Invalid domain for '.$property);
        if($targets!==null)foreach($targets as $type){$valid=false;foreach($definition['range'] as $range)if(self::is($type,$range))$valid=true;if(!$valid)throw new \LogicException('Invalid range for '.$property);}
    }
    public static function node(array $types,array $properties,array $referenceTypes=[]): void
    {
        foreach($properties as $property=>$value) {
            self::property($types,$property);
            $range=self::data()['properties'][$property]['range'];
            foreach(is_array($value) && array_is_list($value)?$value:[$value] as $item) {
                if(is_array($item)) {
                    if(array_keys($item)===['@id']){if(!isset($referenceTypes[$item['@id']]))throw new \LogicException('Unresolved mapper reference.');self::property($types,$property,$referenceTypes[$item['@id']]);continue;}
                    $nested=(array)($item['@type']??[]);
                    if(!$nested)throw new \LogicException('Typed nested value required.');
                    self::property($types,$property,$nested);
                    unset($item['@type']);self::node($nested,$item,$referenceTypes);
                } elseif(is_string($item)) {
                    if(!array_intersect($range,['Text','URL','Date','DateTime','Time','Number','Float','Integer','Boolean']) && !str_starts_with($item,'https://schema.org/'))throw new \LogicException('Invalid scalar range for '.$property);
                } elseif(is_bool($item)) { if(!in_array('Boolean',$range,true))throw new \LogicException('Boolean range required.'); }
                elseif(is_int($item)||is_float($item)) { if(!array_intersect($range,['Number','Float','Integer']))throw new \LogicException('Numeric range required.'); }
                elseif($item!==null)throw new \LogicException('Invalid property value.');
            }
        }
    }
}
