<?php
declare(strict_types=1);
use Contao\CoreBundle\DataContainer\PaletteManipulator;
$GLOBALS['TL_DCA']['tl_page']['fields']['schemaPublisher'] = [
    'inputType'=>'select','options_callback'=>[VHUG\SchemaManagerBundle\EventListener\DataContainerListener::class,'organizations'],
    'eval'=>['includeBlankOption'=>true,'chosen'=>true,'tl_class'=>'w50'],'sql'=>'int unsigned NOT NULL default 0',
];
$GLOBALS['TL_DCA']['tl_page']['fields']['schemaWebsiteId'] = [
    'inputType'=>'text','eval'=>['readonly'=>true,'doNotCopy'=>true,'tl_class'=>'clr long'],'sql'=>"varchar(255) NOT NULL default ''",
];
$GLOBALS['TL_DCA']['tl_page']['fields']['schemaSiteName'] = [
    'inputType'=>'text','eval'=>['maxlength'=>255,'tl_class'=>'w50'],'sql'=>"varchar(255) NOT NULL default ''",
];
$GLOBALS['TL_DCA']['tl_page']['fields']['schemaEntities'] = [
    'inputType'=>'select','options_callback'=>[VHUG\SchemaManagerBundle\EventListener\SourceSettingsListener::class,'entities'],
    'eval'=>['multiple'=>true,'chosen'=>true,'tl_class'=>'clr'],'sql'=>'blob NULL',
];
foreach (['root','rootfallback'] as $palette) {
    if (!isset($GLOBALS['TL_DCA']['tl_page']['palettes'][$palette])) { continue; }
    PaletteManipulator::create()->addLegend('schema_legend','title_legend',PaletteManipulator::POSITION_AFTER)
        ->addField(['schemaPublisher','schemaSiteName','schemaWebsiteId'],'schema_legend',PaletteManipulator::POSITION_APPEND)->applyToPalette($palette,'tl_page');
}
PaletteManipulator::create()->addLegend('schema_legend','meta_legend',PaletteManipulator::POSITION_AFTER)
    ->addField('schemaEntities','schema_legend',PaletteManipulator::POSITION_APPEND)->applyToPalette('regular','tl_page');
