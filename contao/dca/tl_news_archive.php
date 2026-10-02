<?php
declare(strict_types=1);
use Contao\CoreBundle\DataContainer\PaletteManipulator;
if (!isset($GLOBALS['TL_DCA']['tl_news_archive']['config'])) { return; }
PaletteManipulator::create()->addLegend('schema_legend', 'title_legend', PaletteManipulator::POSITION_AFTER)
    ->addField(['schemaType','schemaPublisher'], 'schema_legend', PaletteManipulator::POSITION_APPEND)
    ->applyToPalette('default', 'tl_news_archive');
$GLOBALS['TL_DCA']['tl_news_archive']['fields']['schemaType'] = [
    'inputType'=>'select', 'options'=>[''=>'core','BlogPosting'=>'BlogPosting','Article'=>'Article','NewsArticle'=>'NewsArticle','suppress'=>'suppress'],
    'eval'=>['tl_class'=>'w50'], 'sql'=>"varchar(32) NOT NULL default ''",
];
$GLOBALS['TL_DCA']['tl_news_archive']['fields']['schemaPublisher'] = [
    'inputType'=>'select', 'options_callback'=>[VHUG\SchemaManagerBundle\EventListener\DataContainerListener::class,'organizations'],
    'eval'=>['includeBlankOption'=>true,'chosen'=>true,'tl_class'=>'w50'], 'sql'=>'int unsigned NOT NULL default 0',
];
