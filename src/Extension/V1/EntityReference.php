<?php
declare(strict_types=1);
namespace VHUG\SchemaManagerBundle\Extension\V1;

final readonly class EntityReference
{
    public function __construct(
        public int $id,
    ) { if ($id<1) throw new \InvalidArgumentException("Invalid entity reference."); }
}
