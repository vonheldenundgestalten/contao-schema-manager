<?php
declare(strict_types=1);
namespace VHUG\SchemaManagerBundle\Extension\Internal;
/** Contao's factory requires a Spatie class for each type; this bridge preserves valid new vocabulary. */
final class JsonLdType implements \Spatie\SchemaOrg\Type
{
    public function __construct(private readonly array $node) {}
    public function toArray(): array { return ['@context'=>'https://schema.org']+$this->node; }
    public function toScript(): string { return '<script type="application/ld+json">'.json_encode($this->toArray(),JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_THROW_ON_ERROR).'</script>'; }
    public function __toString(): string { return $this->toScript(); }
}
