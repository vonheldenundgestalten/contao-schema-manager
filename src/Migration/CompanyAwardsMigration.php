<?php
declare(strict_types=1);
namespace VHUG\SchemaManagerBundle\Migration;
use Contao\CoreBundle\Migration\AbstractMigration;
use Contao\CoreBundle\Migration\MigrationResult;
use Doctrine\DBAL\Connection;

/** Move company awards once, retaining every distinct entry from every language. */
final class CompanyAwardsMigration extends AbstractMigration
{
    public function __construct(private readonly Connection $connection) {}

    public function shouldRun(): bool
    {
        $schema = $this->connection->createSchemaManager();
        if (!$schema->tablesExist(['tl_schema_entity', 'tl_schema_translation'])) { return false; }
        if (!$schema->introspectTable('tl_schema_translation')->hasColumn('award')) { return false; }
        return (bool) $this->connection->fetchOne("SELECT t.id FROM tl_schema_translation t JOIN tl_schema_entity e ON e.id=t.pid WHERE e.entityType IN ('Organization','LocalBusiness') AND t.award IS NOT NULL AND t.award<>'' LIMIT 1");
    }

    public function run(): MigrationResult
    {
        if (!$this->shouldRun()) { return $this->createResult(true); }
        // Contao runs migrations before the DCA database update.
        $schema = $this->connection->createSchemaManager();
        $old = $schema->introspectTable('tl_schema_entity');
        if (!$old->hasColumn('award')) {
            $new = clone $old;
            $new->addColumn('award', 'text', ['notnull'=>false]);
            $schema->alterTable($schema->createComparator()->compareTables($old, $new));
        }
        $this->connection->transactional(function (): void {
            $entities = $this->connection->fetchAllAssociative("SELECT id,award FROM tl_schema_entity WHERE entityType IN ('Organization','LocalBusiness') FOR UPDATE");
            foreach ($entities as $entity) {
                $translations = $this->connection->fetchAllAssociative("SELECT id,award FROM tl_schema_translation WHERE pid=? AND award IS NOT NULL AND award<>'' ORDER BY id FOR UPDATE", [$entity['id']]);
                if (!$translations) { continue; }
                $values = [$entity['award'] ?? ''];
                foreach ($translations as $translation) { $values[] = $translation['award']; }
                $lines = array_values(array_unique(array_filter(array_map('trim', preg_split('/\R/u', implode("\n", $values))), static fn ($value) => $value !== '')));
                $this->connection->update('tl_schema_entity', ['award'=>implode("\n", $lines)], ['id'=>$entity['id']]);
                foreach ($translations as $translation) {
                    $this->connection->update('tl_schema_translation', ['award'=>null], ['id'=>$translation['id']]);
                }
            }
        });
        return $this->createResult(true, 'Company awards moved to shared entity fields; distinct entries preserved.');
    }
}
