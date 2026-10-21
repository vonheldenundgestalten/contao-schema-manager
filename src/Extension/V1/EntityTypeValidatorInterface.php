<?php
declare(strict_types=1);
namespace VHUG\SchemaManagerBundle\Extension\V1;

interface EntityTypeValidatorInterface
{
    /** @return iterable<Violation> */
    public function validate(ResolvedEntity $entity, ValidationContext $context): iterable;
}
