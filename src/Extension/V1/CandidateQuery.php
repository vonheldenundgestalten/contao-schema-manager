<?php
declare(strict_types=1);
namespace VHUG\SchemaManagerBundle\Extension\V1;

final readonly class CandidateQuery
{
    public function __construct(
        public ReadContext $context,
        public string $search = "",
        public array $typeKeys = [],
        public ?string $cursor = null,
        public int $limit = 50,
    ) { if ($limit<1 || $limit>100 || $context->purpose!=="discovery") throw new \InvalidArgumentException("Invalid discovery query."); }
}
