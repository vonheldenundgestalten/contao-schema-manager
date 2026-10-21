<?php
declare(strict_types=1);
namespace VHUG\SchemaManagerBundle\Extension\V1;

final readonly class ValidationContext
{
    public function __construct(
        public ReadContext $read,
        public bool $writing = false,
    ) {  }
}
