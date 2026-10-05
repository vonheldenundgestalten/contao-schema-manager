<?php
declare(strict_types=1);
namespace VHUG\SchemaManagerBundle\Schema;
/** Reviewed legacy properties survive migration; native editor fields take precedence. */
final class ImportedSchema
{
    public static function merge(array $node, ?string $json): array
    {
        $legacy=json_decode($json??'',true);
        if(!is_array($legacy)||array_is_list($legacy))return $node;
        unset($legacy['@context']);
        $merged=self::properties($legacy,$node);
        if($identity=self::identity($json))$merged['@id']=$identity;
        return $merged;
    }
    public static function identity(?string $json): ?string
    {
        $id=json_decode($json??'',true)['@id']??null;
        return is_string($id)&&preg_match('~^https?://[^\s]+$~uD',$id)?$id:null;
    }
    private static function properties(array $legacy,array $native): array
    {
        foreach($native as $key=>$value){
            // Lists are editorial values: replacing a list must not resurrect removed entries.
            if(is_array($value)&&!array_is_list($value)&&isset($legacy[$key])&&is_array($legacy[$key])&&!array_is_list($legacy[$key])){
                $legacy[$key]=self::properties($legacy[$key],$value);
            }else{$legacy[$key]=$value;}
        }
        return $legacy;
    }
}
