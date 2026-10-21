<?php
declare(strict_types=1);
namespace VHUG\SchemaManagerBundle\Extension\V1;

final readonly class SourceDefinition
{
    public function __construct(
        public array $typeKeys,
        public array $ownedGlobalFields = [],
        public array $ownedLocalizedFields = [],
        public array $ownedRelationships = [],
    ) {  }
}
