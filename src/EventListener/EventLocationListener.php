<?php
declare(strict_types=1);
namespace VHUG\SchemaManagerBundle\EventListener;
use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\DataContainer;
use Contao\Input;
use Doctrine\DBAL\Connection;
use VHUG\SchemaManagerBundle\Schema\LocationData;

final class EventLocationListener
{
    private function choices(): \VHUG\SchemaManagerBundle\Schema\EntitySelection { return $this->selection??new \VHUG\SchemaManagerBundle\Schema\EntitySelection($this->db, $this->registry); }
    public function __construct(private readonly Connection $db, private readonly ?\VHUG\SchemaManagerBundle\Extension\Internal\Registry $registry=null, private readonly ?\VHUG\SchemaManagerBundle\Schema\EntitySelection $selection=null) {}
    public function places(): array
    {
        $result=[];
        $types=$this->registry?->placeTypes()??['Place','LocalBusiness'];
        foreach($this->choices()->rows($types) as $row){
            $result[$row['id']]=$row['name'].' ['.$row['entityType'].']'.($row['addressLocality']?' — '.$row['addressLocality']:'');
        }
        return $result;
    }
    public function validatePlace(mixed $value): int
    {
        $id=(int)$value;
        if($id && (!$this->choices()->contains($id) || !in_array($this->db->fetchOne('SELECT entityType FROM tl_schema_entity WHERE id=?',[$id]),($this->registry?->placeTypes()??['Place','LocalBusiness']),true)))throw new \InvalidArgumentException('Select an existing location.');
        return $id;
    }
    #[AsCallback(table:'tl_schema_entity',target:'config.onload',priority:-100)]
    #[AsCallback(table:'tl_calendar',target:'config.onload',priority:-100)]
    #[AsCallback(table:'tl_calendar_events',target:'config.onload',priority:-100)]
    public function palette(DataContainer $dc): void
    {
        $table=$dc->table;
        if(!in_array($table,['tl_schema_entity','tl_calendar','tl_calendar_events'],true)||!$dc->id)return;
        $row=$this->db->fetchAssociative('SELECT * FROM '.$table.' WHERE id=?',[$dc->id]);
        if(!$row)return;
        $standalone=$table==='tl_schema_entity';
        $attendance=$standalone?'eventAttendanceMode':'schemaAttendanceMode';
        $selector=$standalone?'eventLocationMode':'schemaLocationMode';
        $picker=$standalone?'eventPlace':'schemaPlace';
        $url=$standalone?'eventUrl':'schemaEventUrl';
        // submitOnChange needs the submitted selection while the form is rebuilt.
        if(Input::post('FORM_SUBMIT')===$table){foreach([$attendance,$selector,'entityType'] as $field)if(Input::post($field)!==null)$row[$field]=Input::post($field);}
        if($standalone && $row['entityType']!=='Event')return;
        $calendar=$table==='tl_calendar_events'?($this->db->fetchAssociative('SELECT * FROM tl_calendar WHERE id=?',[$row['pid']])?:[]):[];
        $mode=($row[$attendance]??'')?:($calendar[$attendance]??'');
        $existing=$standalone?($row[$selector]??'')==='existing':LocationData::calendar($row,$calendar)['mode']==='existing';
        $physical=$standalone?array_values(LocationData::CALENDAR):array_keys(LocationData::CALENDAR);
        $visible=[];
        if($mode!=='OnlineEventAttendanceMode')$visible=array_merge([$selector],$existing?[$picker]:$physical);
        if(in_array($mode,['OnlineEventAttendanceMode','MixedEventAttendanceMode'],true))$visible[]=$url;
        $dca=&$GLOBALS['TL_DCA'][$table];
        foreach($dca['palettes'] as $key=>&$palette){
            if($key==='__selector__'||!is_string($palette)||($standalone&&$key!=='Event'))continue;
            foreach(array_merge($physical,[$selector,$picker,$url]) as $field)$palette=preg_replace('/,'.preg_quote($field,'/').'(?=[,;]|$)/','',$palette);
            $palette=str_replace($attendance,$attendance.($visible?','.implode(',',$visible):''),$palette);
        }
        unset($palette);
        if($table==='tl_calendar'){
            $dca['fields'][$selector]['options']=['','existing'];
            $dca['fields'][$selector]['reference']=$GLOBALS['TL_LANG']['tl_calendar']['locationModes']??[''=>'Custom location','existing'=>'Existing location'];
        }
    }
}
