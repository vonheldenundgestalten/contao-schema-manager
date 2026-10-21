<?php
declare(strict_types=1);
namespace VHUG\SchemaManagerBundle\Extension\V1;

final readonly class RelationshipValue
{
    public function __construct(
        public EntityReference|SourceReference $target,
        public ?string $language = null,
        public ?string $evidence = null,
    ) {  }
}
