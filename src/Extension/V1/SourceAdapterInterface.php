<?php
declare(strict_types=1);
namespace VHUG\SchemaManagerBundle\Extension\V1;

interface SourceAdapterInterface
{
    public function key(): string;
    public function describe(): SourceDefinition;
    public function findCandidates(CandidateQuery $query): CandidatePage;
    public function resolve(SourceReference $source, ReadContext $context): SourceResolution;
}
