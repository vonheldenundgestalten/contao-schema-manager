<?php
declare(strict_types=1);
namespace VHUG\SchemaManagerBundle\Extension\V1;

final readonly class TypeDefinition
{
    public function __construct(
        public string $key,
        public string $label,
        public array $schemaTypes,
        public string $mapper,
        public array $fields = [],
        public array $relationships = [],
        public array $validators = [],
        public array $capabilities = [],
        public int $schemaVersion = 1,
        public bool $manual = true,
        public bool $sourceBindable = true,
        public bool $ai = false,
        public array $properties = [],
        public array $slots = [],
    ) {  }
}
