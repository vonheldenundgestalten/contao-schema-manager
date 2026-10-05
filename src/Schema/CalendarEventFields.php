<?php
declare(strict_types=1);
namespace VHUG\SchemaManagerBundle\Schema;
final class CalendarEventFields
{
    public static function manualFields(): array
    {
        $fields=[];
        foreach(['schemaLatitude','schemaLongitude'] as $name)$fields[$name]=['inputType'=>'text','eval'=>['maxlength'=>32,'tl_class'=>'w50'],'save_callback'=>[[self::class,'validateCoordinate']],'sql'=>"varchar(32) NOT NULL default ''"];
        foreach(['schemaAddressRegion','schemaHasMap'] as $name)$fields[$name]=['inputType'=>'text','eval'=>['maxlength'=>255,'decodeEntities'=>true,'tl_class'=>'w50'],'sql'=>"varchar(255) NOT NULL default ''"];
        $fields['schemaHasMap']['eval']['rgxp']='url';
        $fields['schemaLocationMode']=['inputType'=>'select','options'=>['','existing','custom'],'reference'=>&$GLOBALS['TL_LANG']['tl_calendar_events']['locationModes'],'eval'=>['submitOnChange'=>true,'tl_class'=>'w50'],'sql'=>"varchar(16) NOT NULL default ''"];
        $fields['schemaPlace']=['inputType'=>'select','options_callback'=>[\VHUG\SchemaManagerBundle\EventListener\EventLocationListener::class,'places'],'save_callback'=>[[\VHUG\SchemaManagerBundle\EventListener\EventLocationListener::class,'validatePlace']],'eval'=>['includeBlankOption'=>true,'chosen'=>true,'tl_class'=>'clr'],'sql'=>'int unsigned NOT NULL default 0'];
        return $fields;
    }
    public static function validateCoordinate(mixed $value, \Contao\DataContainer $dc): string
    {
        return LocationData::coordinate($value,$dc->field==='schemaLatitude'?90:180);
    }
    public static function defaults(): array
    {
        $fields=[];
        foreach(['schemaLocationName','schemaStreetAddress','schemaPostalCode','schemaAddressLocality','schemaAddressCountry','schemaEventUrl'] as $name)$fields[$name]=['inputType'=>'text','eval'=>['maxlength'=>255,'tl_class'=>'w50'],'sql'=>"varchar(255) NOT NULL default ''"];
        $fields['schemaEventUrl']['eval']['rgxp']='url';
        $fields['schemaEventUrl']['eval']['decodeEntities']=true;
        $fields['schemaOrganizer']=['inputType'=>'select','options_callback'=>[\VHUG\SchemaManagerBundle\EventListener\DataContainerListener::class,'organizations'],'eval'=>['includeBlankOption'=>true,'chosen'=>true,'tl_class'=>'w50'],'sql'=>'int unsigned NOT NULL default 0'];
        $fields['schemaEventStatus']=['inputType'=>'select','options'=>['','EventScheduled','EventCancelled','EventPostponed','EventRescheduled','EventMovedOnline'],'eval'=>['tl_class'=>'w50'],'sql'=>"varchar(32) NOT NULL default ''"];
        $fields['schemaAttendanceMode']=['inputType'=>'select','options'=>['','OfflineEventAttendanceMode','OnlineEventAttendanceMode','MixedEventAttendanceMode'],'eval'=>['tl_class'=>'w50'],'sql'=>"varchar(32) NOT NULL default ''"];
        $fields['schemaAttendanceMode']['eval']['submitOnChange']=true;
        return $fields;
    }
}
