<?php
declare(strict_types=1);
namespace VHUG\SchemaManagerBundle\Ai;
use VHUG\SchemaManagerBundle\EventListener\BusinessDetailsListener;
use VHUG\SchemaManagerBundle\EventListener\DataContainerListener;
use VHUG\SchemaManagerBundle\EventListener\EntityDetailsListener;
final class FieldPolicy
{
    public const TYPES=['Organization','LocalBusiness','Person','Service','Product','Event'];
    public static function fields(string $table,string $type): array
    {
        if($table==='tl_calendar')return array_merge(['schemaMode'],array_keys(\VHUG\SchemaManagerBundle\Schema\CalendarEventFields::defaults()));
        if($table==='tl_calendar_events')return array_values(array_diff(array_keys(\VHUG\SchemaManagerBundle\Schema\CalendarEventFields::defaults()),['schemaOrganizer']));
        $org=in_array($type,['Organization','LocalBusiness'],true);
        if ($table==='tl_schema_entity') {
            $fields=$org?['name']:['name','sameAs'];
            if ($org) { $fields=array_merge($fields,['award','registrationIdentifiers','legalName','alternateName','foundingDate','vatID','taxID','telephone','email','faxNumber','streetAddress','postalCode','addressLocality','addressRegion','addressCountry','postOfficeBoxNumber','numberOfEmployees','externalUrl']); }
            if ($org || $type==='Service') { $fields[]='areaServedWorldwide'; }
            if ($type==='Person') { $fields=array_merge($fields,['telephone','email']); }
            if ($type==='LocalBusiness') { $fields=array_merge($fields,['latitude','longitude','hasMap','openingHours','priceRange']); }
            if ($type==='Product') { $fields=array_merge($fields,['sku','mpn','brand']); }
            if ($type==='Event') { $fields=array_merge($fields,['startDate','endDate','locationName','streetAddress','postalCode','addressLocality','addressRegion','addressCountry','eventUrl']); }
            return $fields;
        }
        if ($table==='tl_schema_translation') { return match($type) {
            'Person'=>['description','jobTitle','knowsAbout','credentials','award'],
            'Organization','LocalBusiness'=>['description','slogan','knowsAbout','catalogName','sameAs','registrationNames'],
            'Service'=>['name','description','serviceType','audienceType','catalogName','offerDescription'],
            'Product'=>['name','description','offerDescription'],default=>['name','description'],
        }; }
        if ($table==='tl_page') { return $type==='WebSite'?['schemaPublisher','schemaSiteName']:['schemaPageType']; }
        if ($table==='tl_news_archive') { return ['schemaType','schemaPublisher']; }
        return [];
    }
    public static function links(string $table,string $type): array
    {
        if($table==='tl_calendar_events')return ['schemaOrganizer'=>['Organization','LocalBusiness'],'schemaPerformer'=>['Person','Organization','LocalBusiness'],'schemaAbout'=>self::TYPES];
        if($table==='tl_user')return ['schemaPerson'=>['Person']];
        if ($table==='tl_news') { return ['schemaAbout'=>self::TYPES,'schemaMentions'=>self::TYPES]; }
        if ($table==='tl_page') { return ['schemaEntities'=>self::TYPES]; }
        if ($table!=='tl_schema_entity') { return []; }
        $links=['organization'=>['Organization','LocalBusiness']];
        if (in_array($type,['Person','Organization','LocalBusiness'],true)) { $links+=['knowledgeTopics'=>self::TYPES,'memberOf'=>['Organization','LocalBusiness']]; }
        if ($type==='Person') { $links['workLocation']=['LocalBusiness']; }
        if (in_array($type,['Organization','LocalBusiness'],true)) { $links['locations']=['LocalBusiness']; }
        // Catalogues require their existing cycle validator; defer them to the regular editor.
        return $links;
    }
    public function __construct(private readonly BusinessDetailsListener $business,private readonly DataContainerListener $data,private readonly EntityDetailsListener $details) {}
    public function validate(string $table,string $type,string $field,string $value): string
    {
        if (!in_array($field,self::fields($table,$type),true)) { throw new \InvalidArgumentException('Unsupported field.'); }
        $value=trim($value);
        if($table==='tl_calendar'&&$value==='')return '';
        if($field==='schemaMode'){if(!in_array($value,['','enrich','suppress'],true))throw new \InvalidArgumentException('Invalid calendar mode.');return $value;}
        if($field==='schemaEventStatus'&&!in_array($value,['EventScheduled','EventCancelled','EventPostponed','EventRescheduled','EventMovedOnline'],true))throw new \InvalidArgumentException('Invalid event status.');
        if($field==='schemaAttendanceMode'&&!in_array($value,['OfflineEventAttendanceMode','OnlineEventAttendanceMode','MixedEventAttendanceMode'],true))throw new \InvalidArgumentException('Invalid attendance mode.');
        if($field==='schemaAddressCountry'&&!\Symfony\Component\Intl\Countries::exists($value))throw new \InvalidArgumentException('Use an ISO country code.');
        if($field==='schemaEventUrl')return $this->business->url($value);
        if($field==='areaServedWorldwide') {
            if(!in_array($value,['','0','1'],true))throw new \InvalidArgumentException('Use 1 for evidenced worldwide coverage, or 0 to disable.');
            return $value==='1'?'1':'';
        }
        if(in_array($field,['registrationIdentifiers','registrationNames'],true)) {
            $rows=json_decode($value,true);
            if(strlen($value)>12000||!is_array($rows)||!array_is_list($rows)||count($rows)>20)throw new \InvalidArgumentException('Use a JSON list of register key/value pairs.');
            $nodes=\VHUG\SchemaManagerBundle\Schema\BusinessFacts::registrations($rows);
            return json_encode(array_map(static fn($node)=>['key'=>$node['name'],'value'=>$node['value']],$nodes),JSON_THROW_ON_ERROR|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
        }
        if($field==='schemaPublisher'||$field==='schemaOrganizer'){
            if(!ctype_digit($value))throw new \InvalidArgumentException('Choose an organization.');
            return $value;
        }
        if($field==='schemaType'){
            if(!in_array($value,['','Article','NewsArticle','BlogPosting','JobPosting','suppress'],true))throw new \InvalidArgumentException('Choose a supported archive type.');
            return $value;
        }
        $long=in_array($field,['description','sameAs','knowsAbout','credentials','award','openingHours','offerDescription'],true);
        if ($value==='' || mb_strlen($value)>($long?6000:255) || preg_match('/[\x00-\x08\x0b\x0c\x0e-\x1f]/',$value) || strip_tags($value)!==$value) { throw new \InvalidArgumentException('Use non-empty plain text within the field limit.'); }
        if ($field==='email' && !filter_var($value,FILTER_VALIDATE_EMAIL)) { throw new \InvalidArgumentException('Invalid public email address.'); }
        if (in_array($field,['externalUrl','hasMap','eventUrl'],true)) { return $this->business->url($value); }
        if ($field==='sameAs') { foreach (preg_split('/\R/',$value) as $url) { $this->business->url($url); } }
        if ($field==='addressCountry' && !\Symfony\Component\Intl\Countries::exists($value)) { throw new \InvalidArgumentException('Use an ISO country code.'); }
        if ($field==='foundingDate') { return $this->details->foundingDate($value); }
        if ($field==='numberOfEmployees') { return $this->business->employees($value); }
        if ($field==='latitude') { return $this->business->latitude($value); }
        if ($field==='longitude') { return $this->business->longitude($value); }
        if ($field==='openingHours') { return $this->business->hours($value); }
        if (in_array($field,['startDate','endDate'],true)) { return $this->data->date($value); }
        if ($field==='schemaPageType' && !in_array($value,['WebPage','AboutPage','ContactPage','CollectionPage','ProfilePage','ItemPage'],true)) { throw new \InvalidArgumentException('Unsupported page type.'); }
        return $value;
    }
}
