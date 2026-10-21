<?php
declare(strict_types=1);
namespace VHUG\SchemaManagerBundle\Extension\V1;

final readonly class NodeContribution
{
    public function __construct(
        public array $properties,
        public array $diagnostics = [],
    ) {  }
}
