<?php
declare(strict_types=1);
namespace VHUG\SchemaManagerBundle\EventListener;
use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\DataContainer;
use Doctrine\DBAL\Connection;
use Contao\CoreBundle\Cache\CacheTagManager;
final class CalendarSettingsListener
{
    public function __construct(private readonly Connection $db,private readonly CacheTagManager $tags){}
    #[AsCallback(table:'tl_calendar_events',target:'config.onsubmit')]
    public function saved(DataContainer $dc):void{$this->ensureIdentity((int)$dc->id);$this->invalidate();}
    #[AsCallback(table:'tl_calendar',target:'config.onsubmit')]
    public function calendarSaved(DataContainer $dc):void{foreach($this->db->fetchFirstColumn('SELECT id FROM tl_calendar_events WHERE pid=?',[$dc->id]) as $id)$this->ensureIdentity((int)$id);$this->invalidate();}
    public function performers():array{return $this->db->fetchAllKeyValue("SELECT id,name FROM tl_schema_entity WHERE entityType IN ('Person','Organization','LocalBusiness') ORDER BY name");}
    public function ensureIdentity(int $id):void
    {
        $row=$this->db->fetchAssociative('SELECT n.schemaIdentity,e.identityBase FROM tl_calendar_events n JOIN tl_calendar c ON c.id=n.pid JOIN tl_schema_entity e ON e.id=COALESCE(NULLIF(n.schemaOrganizer,0),c.schemaOrganizer) WHERE n.id=?',[$id]);
        if($row&&!$row['schemaIdentity'])$this->db->update('tl_calendar_events',['schemaIdentity'=>rtrim($row['identityBase'],'/').'/#event-'.bin2hex(random_bytes(16))],['id'=>$id]);
    }
    #[AsCallback(table:'tl_calendar_events',target:'fields.schemaIdentity.save')]
    public function identity(mixed $value,DataContainer $dc):string{return (string)$this->db->fetchOne('SELECT schemaIdentity FROM tl_calendar_events WHERE id=?',[$dc->id]);}
    public function invalidate():void{if(class_exists(\Contao\CalendarEventsModel::class)){$this->tags->invalidateTagsForModelClass(\Contao\CalendarEventsModel::class);$this->tags->invalidateTagsForModelClass(\Contao\CalendarModel::class);}}
}
