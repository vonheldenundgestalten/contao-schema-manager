<?php
declare(strict_types=1);
namespace VHUG\SchemaManagerBundle\Schema;
final class CalendarEventFields
{
    public static function defaults(): array
    {
        $fields=[];
        foreach(['schemaLocationName','schemaStreetAddress','schemaPostalCode','schemaAddressLocality','schemaAddressCountry','schemaEventUrl'] as $name)$fields[$name]=['inputType'=>'text','eval'=>['maxlength'=>255,'tl_class'=>'w50'],'sql'=>"varchar(255) NOT NULL default ''"];
        $fields['schemaEventUrl']['eval']['rgxp']='url';
        $fields['schemaOrganizer']=['inputType'=>'select','options_callback'=>[\VHUG\SchemaManagerBundle\EventListener\DataContainerListener::class,'organizations'],'eval'=>['includeBlankOption'=>true,'chosen'=>true,'tl_class'=>'w50'],'sql'=>'int unsigned NOT NULL default 0'];
        $fields['schemaEventStatus']=['inputType'=>'select','options'=>['','EventScheduled','EventCancelled','EventPostponed','EventRescheduled','EventMovedOnline'],'eval'=>['tl_class'=>'w50'],'sql'=>"varchar(32) NOT NULL default ''"];
        $fields['schemaAttendanceMode']=['inputType'=>'select','options'=>['','OfflineEventAttendanceMode','OnlineEventAttendanceMode','MixedEventAttendanceMode'],'eval'=>['tl_class'=>'w50'],'sql'=>"varchar(32) NOT NULL default ''"];
        return $fields;
    }
}
