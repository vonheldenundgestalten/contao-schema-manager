<?php
declare(strict_types=1);
namespace VHUG\SchemaManagerBundle\Schema;
use Contao\CoreBundle\Routing\ResponseContext\JsonLd\JsonLdManager;
use Contao\CoreBundle\Routing\ContentUrlGenerator;
use Contao\CoreBundle\Cache\CacheTagManager;
use Doctrine\DBAL\Connection;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Spatie\SchemaOrg\Event;
final class CalendarEventGraph
{
    public function __construct(private readonly Connection $db,private readonly EntityGraph $entities,private readonly ContentUrlGenerator $urls,private readonly CacheTagManager $tags){}
    public function apply(array $items,string $language,JsonLdManager $manager,array &$emitted): array
    {
        if(!class_exists(\Contao\CalendarEventsModel::class))return [];
        $graph=$manager->getGraphForSchema($manager::SCHEMA_ORG);$subjects=[];
        foreach($items as $item){
            $key=$item['key'];if(!$graph->has(Event::class,$key))continue; // Respect core/template list-vs-reader decisions.
            $record=$this->db->fetchAssociative('SELECT * FROM tl_calendar_events WHERE id=?',[$item['id']]);
            $calendar=$record?$this->db->fetchAssociative('SELECT * FROM tl_calendar WHERE id=?',[$record['pid']]):false;
            if(!$calendar)continue;
            $this->tags->tagWithModelClass(\Contao\CalendarModel::class);$this->tags->tagWithModelClass(\Contao\CalendarEventsModel::class);
            if(($calendar['schemaMode']??'')==='suppress'){$graph->hide(Event::class,$key);continue;}
            if(empty($record['published'])||!empty($calendar['protected'])||(!empty($record['start'])&&$record['start']>time())||(!empty($record['stop'])&&$record['stop']<=time())){$graph->hide(Event::class,$key);continue;}
            $values=[];foreach(array_keys(CalendarEventFields::defaults()) as $field)$values[$field]=($record[$field]??'')?:($calendar[$field]??'');
            $location=LocationData::calendar($record,$calendar);
            if(!$location['id']&&empty($record['schemaLocationMode'])&&empty($calendar['schemaLocationMode'])&&!array_filter($location['facts'],static fn($value)=>trim((string)$value)!=='')&&($calendar['schemaMode']??'')!=='enrich'&&!array_filter($values)&&empty($record['schemaAbout'])&&empty($record['schemaPerformer']))continue;
            $node=$graph->get(Event::class,$key)->toArray();unset($node['@context']);
            $model=\Contao\CalendarEventsModel::findById($record['id']);if(!$model)continue;
            $url=$this->urls->generate($model,[],UrlGeneratorInterface::ABSOLUTE_URL);
            // Recurring records retain the core identity/dates; no invented occurrence expansion.
            $identity=!empty($record['recurring'])?($node['@id']??$key):(($record['schemaIdentity']??'')?:$url.'#event');
            unset($node['identifier']);$node['@id']=$identity;$node['url']=$url;$node['inLanguage']=$language;$node['mainEntityOfPage']=['@id'=>$url.'#webpage'];
            if($values['schemaEventStatus'])$node['eventStatus']='https://schema.org/'.$values['schemaEventStatus'];
            $mode=$values['schemaAttendanceMode'];if($mode)$node['eventAttendanceMode']='https://schema.org/'.$mode;
            if($location['mode']==='existing'){
                $place=$mode==='OnlineEventAttendanceMode'?null:$this->entities->venue($location['id'],$language,$manager,$emitted);
            }else{
                $place=LocationData::physical($location['facts']);
                // Preserve core location only for legacy/default custom fields, not an explicit replacement.
                if(empty($record['schemaLocationMode']) && empty($calendar['schemaLocationMode'])){
                    $core=$node['location']??[];
                    if(is_array($core)&&!array_is_list($core))$place=array_replace_recursive($core,$place);
                }
            }
            unset($node['location']);
            if($locations=LocationData::combine($mode,$place,(string)$values['schemaEventUrl']))$node['location']=$locations;
            if($organizer=$this->entities->emit((int)$values['schemaOrganizer'],$language,$manager,$emitted))$node['organizer']=['@id'=>$organizer['@id']];
            foreach(['schemaAbout'=>'about','schemaPerformer'=>'performer'] as $field=>$property){$refs=[];foreach(\Contao\StringUtil::deserialize($record[$field]??null,true) as $id){if($entity=$this->entities->emit((int)$id,$language,$manager,$emitted))$refs[]=['@id'=>$entity['@id']];}if($refs){$existing=$node[$property]??[];if(!is_array($existing)||!array_is_list($existing))$existing=[$existing];$node[$property]=array_values(array_unique(array_merge($existing,$refs),SORT_REGULAR));}}
            if($identity!==$key)$graph->hide(Event::class,$key);$graph->set($manager->createSchemaOrgTypeFromArray($node),$identity);
            $subjects[]=['@id'=>$identity];
        }
        return $subjects;
    }
}
