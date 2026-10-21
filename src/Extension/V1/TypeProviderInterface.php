<?php
declare(strict_types=1);
namespace VHUG\SchemaManagerBundle\Extension\V1;

interface TypeProviderInterface
{
    /** @return iterable<TypeDefinition> */
    public function types(): iterable;
}
