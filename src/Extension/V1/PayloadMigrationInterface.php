<?php
declare(strict_types=1);
namespace VHUG\SchemaManagerBundle\Extension\V1;
/** Pure supplementary-data transformation; no source writes, IDs or public render hooks. */
interface PayloadMigrationInterface
{
    public function typeKey(): string;
    public function fromVersion(): int;
    public function toVersion(): int;
    public function global(array $values): array;
    public function localized(array $values,string $language): array;
}
