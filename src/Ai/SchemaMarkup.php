<?php
declare(strict_types=1);
namespace VHUG\SchemaManagerBundle\Ai;
/** Inert JSON-LD inspection. Never execute scripts or resolve remote contexts. */
final class SchemaMarkup
{
    public static function parse(string $html): array
    {
        $dom=new \DOMDocument();$old=libxml_use_internal_errors(true);
        try{$dom->loadHTML('<?xml encoding="UTF-8">'.$html,LIBXML_NONET|LIBXML_NOERROR|LIBXML_NOWARNING);}finally{libxml_clear_errors();libxml_use_internal_errors($old);}
        $blocks=[];$errors=[];
        foreach($dom->getElementsByTagName('script') as $script){
            if(strtolower(trim($script->getAttribute('type')))!=='application/ld+json')continue;
            try{$data=json_decode($script->textContent,true,64,JSON_THROW_ON_ERROR);if(!is_array($data))throw new \RuntimeException();$blocks[]=$data;}
            catch(\Throwable){$errors[]='Invalid JSON-LD block; inspect its source manually.';}
        }
        $nodes=[];$walk=function($value)use(&$walk,&$nodes){if(!is_array($value))return;if(isset($value['@type']))$nodes[]=$value;foreach($value as $child)if(is_array($child))$walk($child);};
        foreach($blocks as $block){if(($block['@context']??'')==='https://schema.contao.org')continue;$walk($block);}
        return ['blocks'=>$blocks,'nodes'=>$nodes,'errors'=>$errors];
    }
    public static function scriptOnly(string $html): bool
    {
        $remaining=preg_replace('~<script\b[^>]*type\s*=\s*[\'\"]application/ld\+json[\'\"][^>]*>.*?</script\s*>~is','',$html);
        $remaining=preg_replace('~<!--.*?-->~s','',$remaining);
        return trim($remaining)==='' && !self::parse($html)['errors'] && (bool)self::parse($html)['blocks'];
    }
    public static function label(array $node): string{return (is_array($node['@type']??null)?implode(', ',$node['@type']):($node['@type']??'Thing')).' — '.($node['name']??$node['headline']??$node['@id']??'Unnamed');}
    public static function sameThing(array $a,array $b): bool
    {
        if(!empty($a['@id'])&&$a['@id']===($b['@id']??null))return true;
        if(!array_intersect((array)($a['@type']??[]),(array)($b['@type']??[])))return false;
        $normalize=static fn($s)=>mb_strtolower(trim(preg_replace('/\s+/u',' ',(string)$s)));
        foreach(['name','legalName','url'] as $field)if(!empty($a[$field])&&isset($b[$field])&&$normalize($a[$field])===$normalize($b[$field]))return true;
        return false;
    }
    /** Strict structural subset: uncertain equivalence stays a manual review. */
    public static function missing(array $old,array $replacement): array
    {
        $missing=[];$walk=function($a,$b,$path='')use(&$walk,&$missing){
            foreach($a as $key=>$value){if(in_array($key,['@context','@id'],true)&&$path==='')continue;
                $p=$path===''?(string)$key:$path.'.'.$key;
                if(!array_key_exists($key,$b)){$missing[]=$p;continue;}
                if(is_array($value)&&is_array($b[$key]))$walk($value,$b[$key],$p);
                elseif($value!==$b[$key])$missing[]=$p;
            }
        };$walk($old,$replacement);return array_values(array_unique($missing));
    }
}
