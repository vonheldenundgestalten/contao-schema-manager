<?php
declare(strict_types=1);
namespace VHUG\SchemaManagerBundle\Extension\V1;

interface EntityTypeMapperInterface
{
    public function map(ResolvedEntity $entity, MappingContext $context): NodeContribution;
}
