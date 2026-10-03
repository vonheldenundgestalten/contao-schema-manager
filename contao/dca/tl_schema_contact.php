<?php
declare(strict_types=1);
use Contao\DataContainer;
use Contao\DC_Table;
$text = static fn (): array => ['inputType' => 'text', 'eval' => ['maxlength' => 255, 'tl_class' => 'w50'], 'sql' => "varchar(255) NOT NULL default ''"];
$GLOBALS['TL_DCA']['tl_schema_contact'] = [
    'config' => ['dataContainer' => DC_Table::class, 'ptable' => 'tl_schema_entity', 'enableVersioning' => true, 'sql' => ['keys' => ['id' => 'primary', 'pid' => 'index']]],
    'list' => [
        'sorting' => ['mode' => DataContainer::MODE_PARENT, 'fields' => ['contactType'], 'headerFields' => ['name', 'entityType'], 'panelLayout' => 'limit'],
        'label' => ['fields' => ['contactType', 'telephone', 'email'], 'format' => '%s: %s %s'],
        'operations' => ['edit', 'copy', 'delete', 'show'],
    ],
    'palettes' => ['default' => '{contact_legend},contactType,telephone,email,availableLanguage,areaServed;{publish_legend},published'],
    'fields' => [
        'id' => ['sql' => 'int unsigned NOT NULL auto_increment'],
        'pid' => ['sql' => 'int unsigned NOT NULL default 0'],
        'tstamp' => ['sql' => 'int unsigned NOT NULL default 0'],
        'contactType' => ['inputType' => 'select', 'options' => ['customer service', 'sales', 'technical support', 'billing support', 'reservations'], 'reference' => &$GLOBALS['TL_LANG']['tl_schema_contact']['types'], 'eval' => ['mandatory' => true, 'tl_class' => 'w50'], 'sql' => "varchar(32) NOT NULL default 'customer service'"],
        'telephone' => $text(),
        'email' => ['inputType' => 'text', 'eval' => ['rgxp' => 'email', 'maxlength' => 255, 'tl_class' => 'w50'], 'sql' => "varchar(255) NOT NULL default ''"],
        'availableLanguage' => $text(),
        'areaServed' => ['inputType' => 'select', 'options_callback' => [VHUG\SchemaManagerBundle\EventListener\EntityDetailsListener::class, 'countries'], 'eval' => ['multiple' => true, 'chosen' => true, 'tl_class' => 'clr'], 'sql' => 'blob NULL'],
        'published' => ['inputType' => 'checkbox', 'eval' => ['doNotCopy' => true, 'tl_class' => 'clr'], 'sql' => "char(1) NOT NULL default ''"],
    ],
];
