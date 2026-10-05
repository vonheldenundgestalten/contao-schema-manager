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
        'dataContainer' => DC_Table::class, 'ctable' => ['tl_schema_translation', 'tl_schema_contact'],
        'enableVersioning' => true, 'switchToEdit' => true,
        'sql' => ['keys' => ['id' => 'primary', 'entityId' => 'unique']],
    ],
    'list' => [
        'sorting' => ['mode' => DataContainer::MODE_SORTED, 'fields' => ['name'], 'flag' => 1, 'panelLayout' => 'filter;search,limit'],
        'label' => ['fields' => ['name', 'entityType'], 'format' => '%s [%s]'],
        'operations' => ['!edit', '!children', '!toggle', 'contacts' => ['href' => 'table=tl_schema_contact', 'icon' => 'member.svg'], 'copy', 'delete', 'show'],
    ],
    'palettes' => [
        '__selector__' => ['entityType'],
        'default' => '{identity_legend},name,entityType,identityBase,entityId;{links_legend},sameAs,image;{publish_legend},published',
        'Organization' => '{identity_legend},name,entityType,identityBase,entityId;{facts_legend},legalName,alternateName,foundingDate,vatID,taxID,telephone,email,streetAddress,postalCode,addressLocality,addressCountry,organization;{links_legend},sameAs,image;{publish_legend},published',
        'LocalBusiness' => '{identity_legend},name,entityType,identityBase,entityId;{facts_legend},legalName,alternateName,foundingDate,vatID,taxID,telephone,email,streetAddress,postalCode,addressLocality,addressCountry,organization;{links_legend},sameAs,image;{publish_legend},published',
        'Person' => '{identity_legend},name,entityType,identityBase,entityId;{facts_legend},organization,telephone,email;{links_legend},sameAs,image;{publish_legend},published',
        'Service' => '{identity_legend},name,entityType,identityBase,entityId;{facts_legend},organization,areaServed,subservices;{links_legend},sameAs,image;{publish_legend},published',
        'SoftwareApplication' => '{identity_legend},name,entityType,identityBase,entityId;{facts_legend},applicationCategory,operatingSystem,softwareVersion,runtimePlatform,organization;{links_legend},sameAs,image;{publish_legend},published',
        'Product' => '{identity_legend},name,entityType,identityBase,entityId;{facts_legend},sku,mpn,brand,organization;{links_legend},sameAs,image;{publish_legend},published',
        'Event' => '{identity_legend},name,entityType,identityBase,entityId;{facts_legend},startDate,endDate,eventStatus,locationName,organization;{links_legend},sameAs,image;{publish_legend},published',
    ],
    'fields' => [
        'id' => ['sql' => 'int unsigned NOT NULL auto_increment'],
        'tstamp' => ['sql' => 'int unsigned NOT NULL default 0'],
        'name' => $text(true) + ['search' => true],
        'entityType' => [
            'inputType' => 'select', 'options' => ['Organization', 'LocalBusiness', 'Person', 'Service', 'Product', 'SoftwareApplication', 'Event'],
            'eval' => ['mandatory' => true, 'submitOnChange' => true, 'tl_class' => 'w50'], 'filter' => true,
            'sql' => "varchar(32) NOT NULL default 'Organization'",
        ],
        'identityBase' => $text(true),
        'entityId' => [
            'inputType' => 'text', 'eval' => ['readonly' => true, 'doNotCopy' => true, 'tl_class' => 'clr long'],
            'sql' => 'varchar(255) DEFAULT NULL',
        ],
        'alternateName' => $text(), 'foundingDate' => $text(),
        'areaServed' => ['inputType' => 'select', 'options_callback' => [VHUG\SchemaManagerBundle\EventListener\EntityDetailsListener::class, 'countries'], 'eval' => ['multiple' => true, 'chosen' => true, 'tl_class' => 'clr'], 'sql' => 'blob NULL'],
        'subservices' => ['inputType' => 'select', 'options_callback' => [VHUG\SchemaManagerBundle\EventListener\EntityDetailsListener::class, 'services'], 'eval' => ['multiple' => true, 'chosen' => true, 'tl_class' => 'clr'], 'sql' => 'blob NULL'],
        'vatID' => $text(), 'taxID' => $text(),
        'sameAs' => ['inputType' => 'textarea', 'eval' => ['tl_class' => 'clr'], 'sql' => 'text NULL'],
        'image' => ['inputType' => 'fileTree', 'eval' => ['fieldType' => 'radio', 'filesOnly' => true, 'extensions' => 'jpg,jpeg,png,webp,svg', 'tl_class' => 'clr'], 'sql' => 'binary(16) NULL'],
        'applicationCategory' => $text(), 'operatingSystem' => $text(), 'softwareVersion' => $text(), 'runtimePlatform' => $text(),
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
            'toggle' => true, 'filter' => true,
            'inputType' => 'checkbox', 'eval' => ['doNotCopy' => true, 'tl_class' => 'clr'],
            'sql' => "char(1) NOT NULL default ''",
        ],
    ],
];

$fields = &$GLOBALS['TL_DCA']['tl_schema_entity']['fields'];
foreach (['addressRegion', 'postOfficeBoxNumber', 'faxNumber', 'latitude', 'longitude', 'hasMap', 'externalUrl', 'eventUrl', 'numberOfEmployees', 'priceRange'] as $field) { $fields[$field] = $text(); }
$fields['openingHours'] = ['inputType' => 'textarea', 'eval' => ['tl_class' => 'clr'], 'sql' => 'text NULL'];
$fields['eventAttendanceMode'] = ['inputType' => 'select', 'options' => ['', 'OfflineEventAttendanceMode', 'OnlineEventAttendanceMode', 'MixedEventAttendanceMode'], 'reference' => &$GLOBALS['TL_LANG']['tl_schema_entity']['attendanceModes'], 'eval' => ['tl_class' => 'w50'], 'sql' => "varchar(40) NOT NULL default ''"];
foreach (['memberOf' => 'organizations', 'locations' => 'offices', 'workLocation' => 'offices'] as $field => $callback) {
    $fields[$field] = ['inputType' => 'select', 'options_callback' => [VHUG\SchemaManagerBundle\EventListener\BusinessDetailsListener::class, $callback], 'eval' => ['multiple' => true, 'chosen' => true, 'tl_class' => 'clr'], 'sql' => 'blob NULL'];
}
$palettes = &$GLOBALS['TL_DCA']['tl_schema_entity']['palettes'];
foreach (['Organization', 'LocalBusiness'] as $type) {
    $palettes[$type] = str_replace('telephone,email', 'telephone,email,faxNumber', $palettes[$type]);
    $palettes[$type] = str_replace('addressLocality,addressCountry', 'addressLocality,addressRegion,addressCountry,postOfficeBoxNumber', $palettes[$type]);
    $palettes[$type] = str_replace(';{links_legend}', ';{business_legend},numberOfEmployees,areaServed,award;{relations_legend},locations,memberOf,subservices;{links_legend}', $palettes[$type]);
    $palettes[$type] = str_replace('sameAs,image', 'externalUrl,sameAs,image', $palettes[$type]);
}
$palettes['LocalBusiness'] = str_replace(';{business_legend}', ';{location_legend},latitude,longitude,hasMap,openingHours,priceRange;{business_legend}', $palettes['LocalBusiness']);
$palettes['Person'] = str_replace('organization,telephone,email', 'organization,telephone,email,workLocation,memberOf', $palettes['Person']);
$palettes['Event'] = str_replace('locationName,organization', 'organization;{location_legend},eventAttendanceMode,locationName,streetAddress,postalCode,addressLocality,addressRegion,addressCountry,eventUrl', $palettes['Event']);

$GLOBALS['TL_DCA']['tl_schema_entity']['list']['global_operations'] = ['relationships'=>['href'=>'key=relationships','primary'=>true,'icon'=>'root.svg','attributes'=>'data-turbo="false"'], 'all'];

$GLOBALS['TL_DCA']['tl_schema_entity']['fields']['knowledgeTopics'] = [
    'inputType' => 'select',
    'options_callback' => [VHUG\SchemaManagerBundle\EventListener\BusinessDetailsListener::class, 'knowledgeTopicOptions'],
    'eval' => ['multiple' => true, 'chosen' => true, 'tl_class' => 'clr'], 'sql' => 'blob NULL',
];
foreach (['Person', 'Organization', 'LocalBusiness'] as $type) {
    $GLOBALS['TL_DCA']['tl_schema_entity']['palettes'][$type] = str_replace(';{links_legend}', ',knowledgeTopics;{links_legend}', $GLOBALS['TL_DCA']['tl_schema_entity']['palettes'][$type]);
}

$GLOBALS['TL_DCA']['tl_schema_entity']['list']['global_operations']['ai'] = ['href'=>'key=ai','primary'=>true,'icon'=>'search.svg','attributes'=>'data-turbo="false"'];

$GLOBALS['TL_DCA']['tl_schema_entity']['fields']['award'] = ['inputType'=>'textarea', 'eval'=>['tl_class'=>'clr'], 'sql'=>'text NULL'];

$GLOBALS['TL_DCA']['tl_schema_entity']['fields']['areaServedWorldwide'] = ['inputType'=>'checkbox', 'eval'=>['tl_class'=>'clr'], 'sql'=>"char(1) NOT NULL default ''"];
$GLOBALS['TL_DCA']['tl_schema_entity']['fields']['registrationIdentifiers'] = ['inputType'=>'keyValueWizard', 'eval'=>['allowEmptyKeys'=>true,'tl_class'=>'clr','keyLabel'=>&$GLOBALS['TL_LANG']['tl_schema_entity']['registerName'],'valueLabel'=>&$GLOBALS['TL_LANG']['tl_schema_entity']['registerNumber']], 'sql'=>'blob NULL'];
foreach (['Organization','LocalBusiness','Service'] as $type) {
    $GLOBALS['TL_DCA']['tl_schema_entity']['palettes'][$type] = str_replace('areaServed,', 'areaServedWorldwide,areaServed,', $GLOBALS['TL_DCA']['tl_schema_entity']['palettes'][$type]);
}
foreach (['Organization','LocalBusiness'] as $type) {
    $GLOBALS['TL_DCA']['tl_schema_entity']['palettes'][$type] = str_replace('vatID,taxID,', 'vatID,taxID,registrationIdentifiers,', $GLOBALS['TL_DCA']['tl_schema_entity']['palettes'][$type]);
}

foreach (['Organization','LocalBusiness'] as $type) {
    $GLOBALS['TL_DCA']['tl_schema_entity']['palettes'][$type] = str_replace('sameAs,', '', $GLOBALS['TL_DCA']['tl_schema_entity']['palettes'][$type]);
}
