<?php
declare(strict_types=1);
if (!isset($GLOBALS['TL_DCA']['tl_news']['config'])) { return; }
$GLOBALS['TL_DCA']['tl_news']['fields']['schemaIdentity'] = [
    'inputType'=>'text', 'eval'=>['readonly'=>true,'doNotCopy'=>true,'tl_class'=>'clr long'],
    'sql'=>"varchar(255) NOT NULL default ''",
];
$GLOBALS['TL_DCA']['tl_news']['fields']['schemaAuthor'] = [
    'inputType'=>'select', 'options_callback'=>[VHUG\SchemaManagerBundle\EventListener\SourceSettingsListener::class,'people'],
    'eval'=>['includeBlankOption'=>true,'chosen'=>true,'tl_class'=>'w50'], 'sql'=>'int unsigned NOT NULL default 0',
];
$GLOBALS['TL_DCA']['tl_news']['fields']['schemaDateModified'] = [
    'inputType'=>'text', 'eval'=>['rgxp'=>'datim','datepicker'=>true,'tl_class'=>'w50 wizard'],
    'sql'=>"varchar(10) NOT NULL default ''",
];
