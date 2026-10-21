<?php
declare(strict_types=1);
namespace VHUG\SchemaManagerBundle\Extension\V1;

final readonly class ReadContext
{
    public function __construct(
        public string $purpose,
        public string $site,
        public string $language,
        public ?int $actorId = null,
        public bool $authorized = false,
        public int $now = 0,
        public ?string $root = null,
    ) { if (!in_array($purpose,["frontend","preview","discovery","AI"],true) || $site==="" || $language==="") throw new \InvalidArgumentException("Invalid read context."); if ($purpose!=="frontend" && (!$authorized || !$actorId)) throw new \InvalidArgumentException("Authorized actor required."); }
}
