<?php
declare(strict_types=1);
namespace VHUG\SchemaManagerBundle\Extension\V1;

final readonly class RelationshipDefinition
{
    public function __construct(
        public string $key,
        public string $label,
        public array $sourceTypes,
        public array $targetTypes,
        public ?string $property = null,
        public bool $multiple = true,
        public bool $localized = false,
        public ?string $slot = null,
        public bool $pending = false,
        public bool $allowCycles = true,
        public array $projection = ["@id","@type","name","url"],
        public bool $ai = false,
    ) {  }
}
