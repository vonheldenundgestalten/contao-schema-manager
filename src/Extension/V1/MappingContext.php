<?php
declare(strict_types=1);
namespace VHUG\SchemaManagerBundle\Extension\V1;

final readonly class MappingContext
{
    public function __construct(
        public ReadContext $read,
    ) {}
    public function measurement(string $value, string $unit, string $basis): array
    {
        if (!preg_match('/^-?[0-9]+(?:\.[0-9]+)?$/D',$value) || $unit==='' || $basis==='') throw new \InvalidArgumentException('Measurement requires decimal, unit and basis.');
        return ['@type'=>'PropertyValue','name'=>$basis,'value'=>$value,'unitCode'=>$unit];
    }
    public function quantitativeValue(string $value,string $unit): array
    {
        if (!preg_match('/^-?[0-9]+(?:\.[0-9]+)?$/D',$value) || $unit==='') throw new \InvalidArgumentException('Invalid quantitative value.');
        return ['@type'=>'QuantitativeValue','value'=>$value,'unitCode'=>$unit];
    }
}
