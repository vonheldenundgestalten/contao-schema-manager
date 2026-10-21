<?php
declare(strict_types=1);
namespace VHUG\SchemaManagerBundle\Extension\V1;
interface EntityEditorInterface
{
    public function saveFields(EntityReference $entity,array $values,bool $localized,int $expectedRevision,WriteContext $context): int;
    /** @param list<RelationshipValue> $values */
    public function saveRelationship(EntityReference $entity,string $key,array $values,int $expectedRevision,WriteContext $context): int;
    public function discover(string $adapterKey,CandidateQuery $query): CandidatePage;
    public function inspect(EntityReference $entity,ReadContext $context): array;
}
