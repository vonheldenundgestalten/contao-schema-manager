<?php
declare(strict_types=1);
namespace VHUG\SchemaManagerBundle\Extension\V1;

final readonly class WriteContext
{
    public function __construct(
        public ReadContext $read,
        public string $identityOrigin,
        public ?string $expectedSourceRevision = null,
    ) { if (!$read->authorized || !$read->actorId || $read->purpose==="frontend" || !preg_match("~^https://[a-z0-9.-]+(?::[0-9]+)?$~i",$identityOrigin)) throw new \InvalidArgumentException("Authorized write context required."); }
}
