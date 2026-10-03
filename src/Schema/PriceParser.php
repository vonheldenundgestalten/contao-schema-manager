<?php
declare(strict_types=1);
namespace VHUG\SchemaManagerBundle\Schema;

/** Conservative adapter for visible pricing text; an unknown price is never zero. */
final class PriceParser
{
    public function parse(string $text): array
    {
        $text = trim(html_entity_decode(strip_tags(str_replace('*', '', $text)), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        if (!preg_match('~^(?:(from|ab)\s+)?([0-9]+(?:[.,][0-9]+)*)\s*(?:€|EUR)(?:\s*/\s*(month|monat|monatlich|year|jahr|jährlich))?$~iu', $text, $m)) {
            return [];
        }
        $number = $m[2];
        if (preg_match('/^[0-9]{1,3}(?:[.,][0-9]{3})+$/', $number)) {
            $number = str_replace(['.', ','], '', $number);
        } elseif (str_contains($number, ',') && str_contains($number, '.')) {
            return []; // Ambiguous mixed notation requires an explicit editorial value.
        } else {
            $number = str_replace(',', '.', $number);
        }
        if (!is_numeric($number)) { return []; }
        $spec = ['@type' => 'UnitPriceSpecification', 'priceCurrency' => 'EUR'];
        $spec[empty($m[1]) ? 'price' : 'minPrice'] = $number;
        if (!empty($m[3])) {
            $unit = in_array(mb_strtolower($m[3]), ['month','monat','monatlich'], true) ? 'MON' : 'ANN';
            $spec['referenceQuantity'] = ['@type' => 'QuantitativeValue', 'value' => 1, 'unitCode' => $unit];
        }
        return $spec;
    }
}
