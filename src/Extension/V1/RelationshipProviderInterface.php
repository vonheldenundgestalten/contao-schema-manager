<?php
declare(strict_types=1);
namespace VHUG\SchemaManagerBundle\Extension\V1;

interface RelationshipProviderInterface
{
    /** @return iterable<RelationshipDefinition> */
    public function relationships(): iterable;
}
