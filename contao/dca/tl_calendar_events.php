<?php
declare(strict_types=1);
use Contao\CoreBundle\DataContainer\PaletteManipulator;
if(!isset($GLOBALS['TL_DCA']['tl_calendar_events']['config']))return;
$fields=&$GLOBALS['TL_DCA']['tl_calendar_events']['fields'];
$fields+=(VHUG\SchemaManagerBundle\Schema\CalendarEventFields::defaults()+VHUG\SchemaManagerBundle\Schema\CalendarEventFields::manualFields());
$fields['schemaIdentity']=['inputType'=>'text','eval'=>['readonly'=>true,'doNotCopy'=>true,'tl_class'=>'clr long'],'sql'=>"varchar(255) NOT NULL default ''"];
foreach(['schemaAbout','schemaPerformer'] as $name)$fields[$name]=['inputType'=>'select','options_callback'=>[VHUG\SchemaManagerBundle\EventListener\SourceSettingsListener::class,'entities'],'eval'=>['multiple'=>true,'chosen'=>true,'tl_class'=>'clr'],'sql'=>'blob NULL'];
$names=array_merge(array_keys((VHUG\SchemaManagerBundle\Schema\CalendarEventFields::defaults()+VHUG\SchemaManagerBundle\Schema\CalendarEventFields::manualFields())),['schemaAbout','schemaPerformer','schemaIdentity']);
foreach(array_keys($GLOBALS['TL_DCA']['tl_calendar_events']['palettes']) as $palette){if($palette==='__selector__')continue;PaletteManipulator::create()->addLegend('schema_legend','details_legend',PaletteManipulator::POSITION_AFTER)->addField($names,'schema_legend',PaletteManipulator::POSITION_APPEND)->applyToPalette($palette,'tl_calendar_events');}

$fields['schemaPerformer']['options_callback']=[VHUG\SchemaManagerBundle\EventListener\CalendarSettingsListener::class,'performers'];

$fields['schemaAttendanceMode']['reference']=&$GLOBALS['TL_LANG']['tl_calendar_events']['attendanceModes'];
