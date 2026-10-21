<?php
declare(strict_types=1);
namespace VHUG\SchemaManagerBundle\Extension\V1;

final readonly class BindRequest
{
    public function __construct(
        public SourceReference $source,
        public string $expectedRevision,
        public string $typeKey,
        public ?EntityReference $existing = null,
    ) {  }
}
