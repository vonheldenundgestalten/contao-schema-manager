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
        'operations' => ['edit', 'delete', 'show'],
    ],
    'palettes' => [
        'default' => '{home_legend},page,language,isMainEntity;{content_legend},name,description,jobTitle;{publish_legend},published',
    ],
    'fields' => [
        'schemaPreview' => ['inputType' => 'textarea', 'eval' => ['readonly' => true, 'doNotSave' => true, 'rows' => 16, 'tl_class' => 'clr']],
        'sourceContent' => ['inputType' => 'select', 'eval' => ['includeBlankOption' => true, 'chosen' => true, 'submitOnChange' => true, 'tl_class' => 'w50'], 'sql' => 'int unsigned NOT NULL default 0'],
        'sourceRow' => ['inputType' => 'select', 'eval' => ['includeBlankOption' => true, 'tl_class' => 'w50'], 'sql' => "varchar(32) NOT NULL default ''"],
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
            'inputType' => 'checkbox', 'eval' => ['doNotCopy' => true, 'tl_class' => 'clr'],
            'sql' => "char(1) NOT NULL default ''",
        ],
    ],
];
