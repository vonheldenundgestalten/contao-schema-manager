<?php
declare(strict_types=1);
namespace VHUG\SchemaManagerBundle\Extension\V1;

final readonly class ResolvedEntity
{
    public function __construct(
        public EntityReference $reference,
        public string $identity,
        public TypeDefinition $type,
        public array $fields,
        public array $relationships,
        public ?string $url,
        public array $provenance = [],
    ) {  }
}
