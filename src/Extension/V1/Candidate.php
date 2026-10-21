<?php
declare(strict_types=1);
namespace VHUG\SchemaManagerBundle\Extension\V1;

final readonly class Candidate
{
    public function __construct(
        public SourceReference $source,
        public string $label,
        public string $typeKey,
        public string $revision,
    ) {  }
}
