<?php
declare(strict_types=1);
namespace VHUG\SchemaManagerBundle\Extension\V1;

final readonly class CandidatePage
{
    public function __construct(
        public array $candidates,
        public ?string $nextCursor = null,
    ) { foreach ($candidates as $candidate) if (!$candidate instanceof Candidate) throw new \InvalidArgumentException("Invalid candidate."); }
}
