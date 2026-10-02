<?php
declare(strict_types=1);
use Contao\CoreBundle\DataContainer\PaletteManipulator;
$GLOBALS['TL_DCA']['tl_user']['fields']['schemaPerson'] = [
    'inputType'=>'select', 'options_callback'=>[VHUG\SchemaManagerBundle\EventListener\SourceSettingsListener::class,'people'],
    'eval'=>['includeBlankOption'=>true,'chosen'=>true,'tl_class'=>'w50'], 'sql'=>'int unsigned NOT NULL default 0',
];
foreach (array_keys($GLOBALS['TL_DCA']['tl_user']['palettes']) as $palette) {
    if ($palette === '__selector__') { continue; }
    PaletteManipulator::create()->addLegend('schema_legend', 'name_legend', PaletteManipulator::POSITION_AFTER)
        ->addField('schemaPerson','schema_legend',PaletteManipulator::POSITION_APPEND)->applyToPalette($palette,'tl_user');
}
