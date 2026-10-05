<?php
declare(strict_types=1);
use Contao\CoreBundle\DataContainer\PaletteManipulator;
if(!isset($GLOBALS['TL_DCA']['tl_calendar']['config']))return;
$fields=&$GLOBALS['TL_DCA']['tl_calendar']['fields'];
$fields+=(VHUG\SchemaManagerBundle\Schema\CalendarEventFields::defaults()+VHUG\SchemaManagerBundle\Schema\CalendarEventFields::manualFields());
$fields['schemaMode']=['inputType'=>'select','options'=>['','enrich','suppress'],'eval'=>['tl_class'=>'w50'],'sql'=>"varchar(16) NOT NULL default ''"];
$names=array_merge(['schemaMode'],array_keys((VHUG\SchemaManagerBundle\Schema\CalendarEventFields::defaults()+VHUG\SchemaManagerBundle\Schema\CalendarEventFields::manualFields())));
foreach(array_keys($GLOBALS['TL_DCA']['tl_calendar']['palettes']) as $palette){if($palette==='__selector__')continue;PaletteManipulator::create()->addLegend('schema_legend','details_legend',PaletteManipulator::POSITION_AFTER)->addField($names,'schema_legend',PaletteManipulator::POSITION_APPEND)->applyToPalette($palette,'tl_calendar');}

$fields['schemaAttendanceMode']['reference']=&$GLOBALS['TL_LANG']['tl_calendar']['attendanceModes'];
