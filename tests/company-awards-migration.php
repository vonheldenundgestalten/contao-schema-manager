<?php
declare(strict_types=1);
// Run from the installed Contao application's root; all fixture writes roll back.
require getcwd().'/vendor/autoload.php';
$kernel = Contao\ManagerBundle\HttpKernel\ContaoKernel::fromInput(getcwd(), new Symfony\Component\Console\Input\ArgvInput());
if (getenv('SCHEMA_TEST_DB_TCP') === '1') {
    foreach (['_SERVER', '_ENV'] as $scope) {
        if (isset($GLOBALS[$scope]['DATABASE_URL'])) {
            $GLOBALS[$scope]['DATABASE_URL'] = str_replace('@localhost', '@127.0.0.1', $GLOBALS[$scope]['DATABASE_URL']);
        }
    }
    if (isset($_SERVER['DATABASE_URL'])) { putenv('DATABASE_URL='.$_SERVER['DATABASE_URL']); }
}
$kernel->boot();
$c = $kernel->getContainer();
$c->get('contao.framework')->initialize();
$db = $c->get('database_connection');

$check = static function (bool $ok, string $message): void { if (!$ok) { throw new RuntimeException($message); } };
if (!$db->createSchemaManager()->introspectTable('tl_schema_entity')->hasColumn('award')) { throw new RuntimeException('Update the test database schema before running this rollback-only test.'); }
$db->beginTransaction();
try {
    $migration = new VHUG\SchemaManagerBundle\Migration\CompanyAwardsMigration($db);
    $ids = [];
    foreach (['Organization','LocalBusiness','Person'] as $type) {
        $db->insert('tl_schema_entity', ['name'=>'Award migration fixture','entityType'=>$type,'award'=>'Existing award']);
        $id = (int) $db->lastInsertId(); $ids[$type] = $id;
        foreach (['en'=>"Existing award\nShared award\nEnglish wording", 'de'=>"Shared award\r\nDeutscher Titel"] as $language=>$awards) {
            $db->insert('tl_schema_translation', ['pid'=>$id,'language'=>$language,'award'=>$awards]);
        }
    }
    $check($migration->shouldRun(), 'Legacy company awards detected');
    $migration->run();
    foreach (['Organization','LocalBusiness'] as $type) {
        $check($db->fetchOne('SELECT award FROM tl_schema_entity WHERE id=?', [$ids[$type]]) === "Existing award\nShared award\nEnglish wording\nDeutscher Titel", 'All values retained and exact duplicates removed');
        $check(!(int) $db->fetchOne("SELECT COUNT(*) FROM tl_schema_translation WHERE pid=? AND award IS NOT NULL", [$ids[$type]]), 'Company translation fields cleared after transfer');
    }
    $check((int) $db->fetchOne("SELECT COUNT(*) FROM tl_schema_translation WHERE pid=? AND award IS NOT NULL", [$ids['Person']]) === 2, 'Person translations untouched');
    $check(!$migration->shouldRun(), 'Migration is idempotent');
    $db->update('tl_schema_entity', ['award'=>'Editorial replacement'], ['id'=>$ids['Organization']]);
    $migration->run();
    $check($db->fetchOne('SELECT award FROM tl_schema_entity WHERE id=?', [$ids['Organization']]) === 'Editorial replacement', 'Rerun does not resurrect old values');
    echo "PASS: multilingual company awards migrated losslessly, deduplicated, person records unchanged, rerun safe.\n";
} finally { $db->rollBack(); echo "Award fixtures rolled back.\n"; }
