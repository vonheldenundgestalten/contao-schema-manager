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

$fields = &$GLOBALS['TL_DCA']['tl_page']['fields'];
$text = ['inputType'=>'text','eval'=>['maxlength'=>255,'tl_class'=>'w50'],'sql'=>"varchar(255) NOT NULL default ''"];
$file = ['inputType'=>'fileTree','eval'=>['fieldType'=>'radio','filesOnly'=>true,'extensions'=>'jpg,jpeg,png,webp','tl_class'=>'clr'],'sql'=>'binary(16) NULL'];
$fields['schemaSiteAlternateName']=$text;
$fields['schemaWebsiteRoot']=['inputType'=>'select','options_callback'=>[VHUG\SchemaManagerBundle\EventListener\PageSettingsListener::class,'roots'],'eval'=>['includeBlankOption'=>true,'chosen'=>true,'tl_class'=>'w50'],'sql'=>'int unsigned NOT NULL default 0'];
$fields['schemaWebsiteHome']=['inputType'=>'pageTree','eval'=>['fieldType'=>'radio','tl_class'=>'clr'],'sql'=>'int unsigned NOT NULL default 0'];
$fields['schemaManageSocial']=['inputType'=>'checkbox','eval'=>['tl_class'=>'clr'],'sql'=>"char(1) NOT NULL default ''"];
$fields['schemaFallbackImage']=$file;
$fields['schemaFallbackAlt']=$text;
$fields['schemaSocialCrop']=['inputType'=>'select','options'=>['fit','crop'],'reference'=>&$GLOBALS['TL_LANG']['tl_page']['schemaSocialCropOptions'],'eval'=>['tl_class'=>'w50'],'sql'=>"varchar(16) NOT NULL default 'fit'"];
$fields['schemaPageType']=['inputType'=>'select','options'=>VHUG\SchemaManagerBundle\EventListener\PageMetadataListener::PAGE_TYPES,'eval'=>['tl_class'=>'w50'],'sql'=>"varchar(32) NOT NULL default 'WebPage'"];
$fields['schemaImageMode']=['inputType'=>'select','options'=>['auto','override','none'],'reference'=>&$GLOBALS['TL_LANG']['tl_page']['schemaImageModeOptions'],'eval'=>['tl_class'=>'w50'],'sql'=>"varchar(16) NOT NULL default 'auto'"];
$fields['schemaPageImage']=$file;
$fields['schemaImageAlt']=$text;
foreach(['root','rootfallback'] as $palette){
 if(!isset($GLOBALS['TL_DCA']['tl_page']['palettes'][$palette])){continue;}
 PaletteManipulator::create()->addField(['schemaSiteAlternateName','schemaWebsiteRoot','schemaWebsiteHome'],'schema_legend',PaletteManipulator::POSITION_APPEND)
 ->addLegend('schema_social_legend','schema_legend',PaletteManipulator::POSITION_AFTER)
 ->addField(['schemaManageSocial','schemaFallbackImage','schemaFallbackAlt','schemaSocialCrop'],'schema_social_legend',PaletteManipulator::POSITION_APPEND)->applyToPalette($palette,'tl_page');
}
PaletteManipulator::create()->addField('schemaPageType','schema_legend',PaletteManipulator::POSITION_APPEND)
 ->addLegend('schema_image_legend','schema_legend',PaletteManipulator::POSITION_AFTER)
 ->addField(['schemaImageMode','schemaPageImage','schemaImageAlt'],'schema_image_legend',PaletteManipulator::POSITION_APPEND)->applyToPalette('regular','tl_page');
