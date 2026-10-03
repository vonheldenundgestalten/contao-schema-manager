<?php
declare(strict_types=1);
use Contao\DataContainer;
use Contao\DC_Table;
$text = static fn (bool $mandatory = false): array => [
    'inputType' => 'text', 'eval' => ['mandatory' => $mandatory, 'maxlength' => 255, 'tl_class' => 'w50'],
    'sql' => "varchar(255) NOT NULL default ''",
];
$GLOBALS['TL_DCA']['tl_schema_entity'] = [
    'config' => [
        'dataContainer' => DC_Table::class, 'ctable' => ['tl_schema_translation'],
        'enableVersioning' => true, 'switchToEdit' => true,
        'sql' => ['keys' => ['id' => 'primary', 'entityId' => 'unique']],
    ],
    'list' => [
        'sorting' => ['mode' => DataContainer::MODE_SORTED, 'fields' => ['name'], 'flag' => 1, 'panelLayout' => 'filter;search,limit'],
        'label' => ['fields' => ['name', 'entityType'], 'format' => '%s [%s]'],
        'operations' => ['edit', 'children', 'copy', 'delete', 'show'],
    ],
    'palettes' => [
        '__selector__' => ['entityType'],
        'default' => '{identity_legend},name,entityType,identityBase,entityId;{links_legend},sameAs,image;{publish_legend},published',
        'Organization' => '{identity_legend},name,entityType,identityBase,entityId;{facts_legend},legalName,vatID,taxID,telephone,email,streetAddress,postalCode,addressLocality,addressCountry,organization;{links_legend},sameAs,image;{publish_legend},published',
        'LocalBusiness' => '{identity_legend},name,entityType,identityBase,entityId;{facts_legend},legalName,vatID,taxID,telephone,email,streetAddress,postalCode,addressLocality,addressCountry,organization;{links_legend},sameAs,image;{publish_legend},published',
        'Person' => '{identity_legend},name,entityType,identityBase,entityId;{facts_legend},organization;{links_legend},sameAs,image;{publish_legend},published',
        'Service' => '{identity_legend},name,entityType,identityBase,entityId;{facts_legend},organization;{links_legend},sameAs,image;{publish_legend},published',
        'Product' => '{identity_legend},name,entityType,identityBase,entityId;{facts_legend},sku,mpn,brand,organization;{links_legend},sameAs,image;{publish_legend},published',
        'Event' => '{identity_legend},name,entityType,identityBase,entityId;{facts_legend},startDate,endDate,eventStatus,locationName,organization;{links_legend},sameAs,image;{publish_legend},published',
    ],
    'fields' => [
        'id' => ['sql' => 'int unsigned NOT NULL auto_increment'],
        'tstamp' => ['sql' => 'int unsigned NOT NULL default 0'],
        'name' => $text(true) + ['search' => true],
        'entityType' => [
            'inputType' => 'select', 'options' => ['Organization', 'LocalBusiness', 'Person', 'Service', 'Product', 'Event'],
            'eval' => ['mandatory' => true, 'submitOnChange' => true, 'tl_class' => 'w50'], 'filter' => true,
            'sql' => "varchar(32) NOT NULL default 'Organization'",
        ],
        'identityBase' => $text(true),
        'entityId' => [
            'inputType' => 'text', 'eval' => ['readonly' => true, 'doNotCopy' => true, 'tl_class' => 'clr long'],
            'sql' => 'varchar(255) DEFAULT NULL',
        ],
        'vatID' => $text(), 'taxID' => $text(),
        'sameAs' => ['inputType' => 'textarea', 'eval' => ['tl_class' => 'clr'], 'sql' => 'text NULL'],
        'image' => ['inputType' => 'fileTree', 'eval' => ['fieldType' => 'radio', 'filesOnly' => true, 'extensions' => 'jpg,jpeg,png,webp,svg', 'tl_class' => 'clr'], 'sql' => 'binary(16) NULL'],
        'sku' => $text(), 'mpn' => $text(), 'brand' => $text(),
        'legalName' => $text(), 'telephone' => $text(), 'email' => $text(),
        'streetAddress' => $text(), 'postalCode' => $text(), 'addressLocality' => $text(), 'addressCountry' => $text(),
        'organization' => [
            'inputType' => 'select', 'eval' => ['includeBlankOption' => true, 'chosen' => true, 'tl_class' => 'w50'],
            'sql' => 'int unsigned NOT NULL default 0',
        ],
        'startDate' => $text(true), 'endDate' => $text(), 'locationName' => $text(),
        'eventStatus' => [
            'inputType' => 'select',
            'options' => ['EventScheduled', 'EventCancelled', 'EventPostponed', 'EventRescheduled', 'EventMovedOnline'],
            'eval' => ['tl_class' => 'w50'], 'sql' => "varchar(32) NOT NULL default 'EventScheduled'",
        ],
        'published' => [
            'inputType' => 'checkbox', 'eval' => ['doNotCopy' => true, 'tl_class' => 'clr'],
            'sql' => "char(1) NOT NULL default ''",
        ],
    ],
];
