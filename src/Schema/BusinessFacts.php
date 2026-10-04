<?php
declare(strict_types=1);
namespace VHUG\SchemaManagerBundle\Schema;
final class BusinessFacts
{
    public static function areaServed(array $countries, bool $worldwide): array
    {
        return $worldwide ? ['@type'=>'AdministrativeArea','name'=>'Worldwide'] : array_values($countries);
    }
    public static function registrations(array $rows, array $names = []): array
    {
        $result=[];
        foreach ($rows as $row) {
            if (!is_array($row)) { throw new \InvalidArgumentException('Use a register name and registration number.'); }
            $name=$row['key']??''; $value=$row['value']??'';
            if (!is_scalar($name)||!is_scalar($value)) { throw new \InvalidArgumentException('Use plain text for registration identifiers.'); }
            $name=trim((string)$name); $value=trim((string)$value);
            if ($name==='' && $value==='') { continue; }
            if ($name==='' || $value==='' || mb_strlen($name)>255 || mb_strlen($value)>255 || strip_tags($name)!==$name || strip_tags($value)!==$value) {
                throw new \InvalidArgumentException('Each identifier needs a register name and number, up to 255 plain-text characters each.');
            }
            foreach ($names as $label) {
                if (is_array($label) && (string)($label['key']??'')===$value && is_string($label['value']??null) && trim($label['value'])!=='') { $name=trim($label['value']);break; }
            }
            $node=['@type'=>'PropertyValue','name'=>$name,'value'=>$value];
            if (!in_array($node,$result,true)) { $result[]=$node; }
        }
        return $result;
    }
}
