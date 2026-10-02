<?php
declare(strict_types=1);
require getcwd().'/vendor/autoload.php';
$kernel=Contao\ManagerBundle\HttpKernel\ContaoKernel::fromInput(getcwd(),new Symfony\Component\Console\Input\ArgvInput());
if(getenv('SCHEMA_TEST_DB_TCP')==='1'){
 foreach(['_SERVER','_ENV'] as $scope){if(isset($GLOBALS[$scope]['DATABASE_URL'])){$GLOBALS[$scope]['DATABASE_URL']=str_replace('@localhost','@127.0.0.1',$GLOBALS[$scope]['DATABASE_URL']);}}
 if(isset($_SERVER['DATABASE_URL'])){putenv('DATABASE_URL='.$_SERVER['DATABASE_URL']);}
}
$kernel->boot();$c=$kernel->getContainer();$c->get('contao.framework')->initialize();$db=$c->get('database_connection');

// List consumers pass these names to field metadata lookups and SQL builders.
foreach (['tl_schema_entity', 'tl_schema_translation'] as $table) {
    Contao\Controller::loadDataContainer($table);
    $dca = $GLOBALS['TL_DCA'][$table];
    foreach (['sorting', 'label'] as $section) {
        $fields = $dca['list'][$section]['fields'];
        if (!array_is_list($fields)) {
            throw new RuntimeException("$table list.$section.fields must be a list of field names");
        }
        foreach ($fields as $field) {
            if (!is_string($field) || !isset($dca['fields'][$field])) {
                throw new RuntimeException("$table list.$section references an invalid field");
            }
        }
    }
}
echo "DCA list field validation passed for entities and translations.\n";
