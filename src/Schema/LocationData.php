<?php
declare(strict_types=1);
namespace VHUG\SchemaManagerBundle\Schema;

/** Shared physical-location facts; coordinates are always entered manually. */
final class LocationData
{
    public const ADDRESS=['streetAddress','postalCode','addressLocality','addressRegion','addressCountry'];
    public const CALENDAR=['schemaLocationName'=>'locationName','schemaStreetAddress'=>'streetAddress','schemaPostalCode'=>'postalCode','schemaAddressLocality'=>'addressLocality','schemaAddressRegion'=>'addressRegion','schemaAddressCountry'=>'addressCountry','schemaLatitude'=>'latitude','schemaLongitude'=>'longitude','schemaHasMap'=>'hasMap'];
    public static function coordinate(mixed $value,int $limit): string
    {
        $value=str_replace(',', '.', trim((string)$value));
        if($value!=='' && (!preg_match('/^[+-]?(?:\d+(?:\.\d*)?|\.\d+)$/D',$value)||abs((float)$value)>$limit))throw new \InvalidArgumentException('Enter a decimal coordinate between -'.$limit.' and '.$limit.'.');
        return $value;
    }
    public static function physical(array $facts): array
    {
        $place=['@type'=>'Place'];
        if(!empty($facts['locationName']))$place['name']=$facts['locationName'];
        $address=[];foreach(self::ADDRESS as $field)if(trim((string)($facts[$field]??''))!=='')$address[$field]=$facts[$field];
        if($address)$place['address']=['@type'=>'PostalAddress']+$address;
        $lat=$facts['latitude']??'';$lon=$facts['longitude']??'';
        if($lat!==''&&$lon!==''&&is_numeric($lat)&&is_numeric($lon)&&abs((float)$lat)<=90&&abs((float)$lon)<=180)$place['geo']=['@type'=>'GeoCoordinates','latitude'=>(float)$lat,'longitude'=>(float)$lon];
        if(!empty($facts['hasMap']))$place['hasMap']=$facts['hasMap'];
        return $place;
    }
    public static function combine(string $attendance,?array $physical,string $url): array
    {
        $locations=[];
        if($attendance!=='OnlineEventAttendanceMode' && $physical && count($physical)>1)$locations[]=$physical;
        if(in_array($attendance,['OnlineEventAttendanceMode','MixedEventAttendanceMode'],true)&&$url!=='')$locations[]=['@type'=>'VirtualLocation','url'=>$url];
        return count($locations)===1?$locations[0]:$locations;
    }
    /** Explicit choices never mix two addresses; blank retains legacy calendar defaults. */
    public static function calendar(array $event,array $calendar=[]): array
    {
        $mode=$event['schemaLocationMode']??'';
        $hasOwn=false;foreach(self::CALENDAR as $field=>$unused)if(trim((string)($event[$field]??''))!=='')$hasOwn=true;
        if($mode==='')$mode=$hasOwn?'custom':(($calendar['schemaLocationMode']??'')?:'custom');
        $inheritFacts=($event['schemaLocationMode']??'')==='' && ($calendar['schemaLocationMode']??'')!=='existing';
        $facts=[];
        foreach(self::CALENDAR as $field=>$property)$facts[$property]=($event[$field]??'')!==''?$event[$field]:($inheritFacts?($calendar[$field]??''):'');
        // A partial coordinate pair must never be completed from another location.
        if(trim((string)($event['schemaLatitude']??''))!==''||trim((string)($event['schemaLongitude']??''))!==''){
            $facts['latitude']=$event['schemaLatitude']??'';$facts['longitude']=$event['schemaLongitude']??'';
        }
        $id=($event['schemaLocationMode']??'')==='existing'?(int)($event['schemaPlace']??0):(int)(($event['schemaPlace']??0)?:($calendar['schemaPlace']??0));
        return ['mode'=>$mode,'id'=>$id,'facts'=>$facts];
    }
}
