<?php
declare(strict_types=1);
namespace VHUG\SchemaManagerBundle\Extension\V1;

interface SourceInvalidatorInterface
{
    public function changed(SourceReference $source): void;
    public function adapterChanged(string $adapterKey): void;
}
