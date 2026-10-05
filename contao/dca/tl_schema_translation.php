<?php
declare(strict_types=1);
use Contao\DataContainer;
use Contao\DC_Table;
$GLOBALS['TL_DCA']['tl_schema_translation'] = [
    'config' => [
        'dataContainer' => DC_Table::class, 'ptable' => 'tl_schema_entity',
        'enableVersioning' => true, 'switchToEdit' => true,
        'sql' => ['keys' => ['id' => 'primary', 'pid' => 'index', 'page,published' => 'index']],
    ],
    'list' => [
        'sorting' => ['mode' => DataContainer::MODE_PARENT, 'fields' => ['language'], 'headerFields' => ['name', 'entityType', 'entityId'], 'panelLayout' => 'limit'],
        'label' => ['fields' => ['language'], 'format' => '%s'],
        'operations' => ['!edit', '!toggle', 'delete', 'show'],
    ],
    'palettes' => [
        '__selector__' => ['offerMode'],
        'default' => '{home_legend},page,language,isMainEntity;{content_legend},name,description,jobTitle;{publish_legend},published',
    ],
    'subpalettes' => [
        'offerMode_exact' => 'offerPrice,offerCurrency,offerUnit,offerAvailability',
        'offerMode_from' => 'offerPrice,offerCurrency,offerUnit,offerAvailability',
    ],
    'fields' => [
        'serviceType' => ['inputType' => 'text', 'eval' => ['maxlength' => 255, 'tl_class' => 'w50'], 'sql' => "varchar(255) NOT NULL default ''"],
        'audienceType' => ['inputType' => 'text', 'eval' => ['maxlength' => 255, 'tl_class' => 'w50'], 'sql' => "varchar(255) NOT NULL default ''"],
        'catalogName' => ['inputType' => 'text', 'eval' => ['maxlength' => 255, 'tl_class' => 'clr long'], 'sql' => "varchar(255) NOT NULL default ''"],
        'offerMode' => ['inputType' => 'select', 'options' => ['', 'exact', 'from', 'quote'], 'reference' => &$GLOBALS['TL_LANG']['tl_schema_translation']['offerModes'], 'eval' => ['submitOnChange' => true, 'tl_class' => 'w50'], 'sql' => "varchar(16) NOT NULL default ''"],
        'offerPrice' => ['inputType' => 'text', 'eval' => ['mandatory' => true, 'maxlength' => 32, 'tl_class' => 'w50'], 'sql' => "varchar(32) NOT NULL default ''"],
        'offerCurrency' => ['inputType' => 'text', 'eval' => ['mandatory' => true, 'maxlength' => 3, 'tl_class' => 'w50'], 'sql' => "varchar(3) NOT NULL default ''"],
        'offerUnit' => ['inputType' => 'select', 'options' => ['', 'MON', 'ANN', 'HUR', 'DAY'], 'reference' => &$GLOBALS['TL_LANG']['tl_schema_translation']['offerUnits'], 'eval' => ['tl_class' => 'w50'], 'sql' => "varchar(3) NOT NULL default ''"],
        'offerAvailability' => ['inputType' => 'select', 'options' => ['', 'InStock', 'OutOfStock', 'PreOrder', 'LimitedAvailability', 'Discontinued'], 'eval' => ['tl_class' => 'w50'], 'sql' => "varchar(32) NOT NULL default ''"],
        'offerDescription' => ['inputType' => 'textarea', 'eval' => ['tl_class' => 'clr'], 'sql' => 'text NULL'],
        'schemaPreview' => ['eval' => ['doNotSave' => true]],
        'id' => ['sql' => 'int unsigned NOT NULL auto_increment'],
        'pid' => ['sql' => 'int unsigned NOT NULL default 0'],
        'tstamp' => ['sql' => 'int unsigned NOT NULL default 0'],
        'page' => [
            'inputType' => 'pageTree', 'foreignKey' => 'tl_page.title',
            'eval' => ['mandatory' => true, 'fieldType' => 'radio', 'tl_class' => 'clr'],
            'sql' => 'int unsigned NOT NULL default 0',
        ],
        'language' => [
            'inputType' => 'text', 'eval' => ['readonly' => true, 'tl_class' => 'w50'],
            'sql' => "varchar(32) NOT NULL default ''",
        ],
        'name' => [
            'inputType' => 'text', 'eval' => ['maxlength' => 255, 'tl_class' => 'w50'],
            'sql' => "varchar(255) NOT NULL default ''",
        ],
        'description' => [
            'inputType' => 'textarea', 'eval' => ['tl_class' => 'clr'],
            'sql' => 'text NULL',
        ],
        'jobTitle' => [
            'inputType' => 'text', 'eval' => ['maxlength' => 255, 'tl_class' => 'w50'],
            'sql' => "varchar(255) NOT NULL default ''",
        ],
        'isMainEntity' => [
            'inputType' => 'checkbox', 'eval' => ['tl_class' => 'clr'],
            'sql' => "char(1) NOT NULL default ''",
        ],
        'published' => [
            'toggle' => true, 'filter' => true,
            'inputType' => 'checkbox', 'eval' => ['doNotCopy' => true, 'tl_class' => 'clr'],
            'sql' => "char(1) NOT NULL default ''",
        ],
    ],
];

foreach (['slogan', 'knowsAbout', 'award', 'credentials'] as $field) {
    $GLOBALS['TL_DCA']['tl_schema_translation']['fields'][$field] = ['inputType' => 'textarea', 'eval' => ['tl_class' => 'clr'], 'sql' => 'text NULL'];
}

$GLOBALS['TL_DCA']['tl_schema_translation']['fields']['schemaImportedData'] = ['inputType'=>'textarea','eval'=>['readonly'=>true,'tl_class'=>'clr','style'=>'min-height:12em'],'sql'=>'mediumtext NULL'];

$GLOBALS['TL_DCA']['tl_schema_translation']['fields']['sameAs'] = ['inputType'=>'textarea','eval'=>['tl_class'=>'clr'],'sql'=>'text NULL'];
$GLOBALS['TL_DCA']['tl_schema_translation']['fields']['registrationNames'] = ['inputType'=>'keyValueWizard','eval'=>['allowEmptyKeys'=>true,'tl_class'=>'clr','keyLabel'=>&$GLOBALS['TL_LANG']['tl_schema_translation']['registerNumber'],'valueLabel'=>&$GLOBALS['TL_LANG']['tl_schema_translation']['registerName']],'sql'=>'blob NULL'];

foreach (['softwareRequirements', 'featureList'] as $field) {
    $GLOBALS['TL_DCA']['tl_schema_translation']['fields'][$field] = ['inputType'=>'textarea', 'eval'=>['tl_class'=>'clr'], 'sql'=>'text NULL'];
}
