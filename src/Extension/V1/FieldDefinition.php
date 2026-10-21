<?php
declare(strict_types=1);
namespace VHUG\SchemaManagerBundle\Extension\V1;

final readonly class FieldDefinition
{
    public function __construct(
        public string $key,
        public string $type = "text",
        public bool $localized = false,
        public bool $multiple = false,
        public bool $required = false,
        public array $constraints = [],
        public mixed $default = null,
        public string $widget = "text",
        public bool $ai = false,
    ) {  }
}
