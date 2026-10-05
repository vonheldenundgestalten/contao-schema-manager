<?php
declare(strict_types=1);
namespace VHUG\SchemaManagerBundle\EventListener;
use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\DataContainer;
use Contao\StringUtil;
use Doctrine\DBAL\Connection;
final class BusinessDetailsListener
{
    public function __construct(private readonly Connection $connection) {}
    #[AsCallback(table: 'tl_schema_entity', target: 'fields.registrationIdentifiers.save')]
    public function registrations(mixed $value): mixed
    {
        \VHUG\SchemaManagerBundle\Schema\BusinessFacts::registrations(StringUtil::deserialize($value, true));
        return $value;
    }
    #[AsCallback(table: 'tl_schema_translation', target: 'fields.sameAs.load')]
    public function localizedLinks(mixed $value, DataContainer $dc): mixed
    {
        if ($value !== null) { return $value; }
        return $this->connection->fetchOne('SELECT e.sameAs FROM tl_schema_entity e JOIN tl_schema_translation t ON t.pid=e.id WHERE t.id=?', [$dc->id]) ?: '';
    }
    #[AsCallback(table: 'tl_schema_translation', target: 'fields.sameAs.save')]
    public function saveLocalizedLinks(mixed $value): string
    {
        $value=trim((string)$value);
        foreach (preg_split('/\R/u',$value) as $url) { if (trim($url)!=='') { $this->url(trim($url)); } }
        return $value;
    }
    #[AsCallback(table: 'tl_schema_translation', target: 'fields.registrationNames.load')]
    public function registrationNames(mixed $value, DataContainer $dc): mixed
    {
        if ($value !== null) { return $value; }
        $rows=StringUtil::deserialize($this->connection->fetchOne('SELECT e.registrationIdentifiers FROM tl_schema_entity e JOIN tl_schema_translation t ON t.pid=e.id WHERE t.id=?', [$dc->id]),true);
        return serialize(array_map(static fn($row)=>['key'=>$row['value'],'value'=>$row['key']],$rows));
    }
    #[AsCallback(table: 'tl_schema_translation', target: 'fields.registrationNames.save')]
    public function saveRegistrationNames(mixed $value, DataContainer $dc): mixed
    {
        $rows=StringUtil::deserialize($value,true);
        $valid=array_column(StringUtil::deserialize($this->connection->fetchOne('SELECT e.registrationIdentifiers FROM tl_schema_entity e JOIN tl_schema_translation t ON t.pid=e.id WHERE t.id=?', [$dc->id]),true),'value');
        $seen=[];
        foreach (\VHUG\SchemaManagerBundle\Schema\BusinessFacts::registrations($rows) as $row) {
            if(!in_array($row['name'],array_map('strval',$valid),true)||isset($seen[$row['name']]))throw new \InvalidArgumentException('Use each existing registration number only once. Change registration numbers on the shared entity.');
            $seen[$row['name']]=true;
        }
        return $value;
    }
    public function organizations(): array { return $this->connection->fetchAllKeyValue("SELECT id,name FROM tl_schema_entity WHERE entityType IN ('Organization','LocalBusiness') ORDER BY name"); }
    public function offices(): array { return $this->connection->fetchAllKeyValue("SELECT id,name FROM tl_schema_entity WHERE entityType='LocalBusiness' ORDER BY name"); }
    #[AsCallback(table: 'tl_schema_entity', target: 'fields.hasMap.save')]
    #[AsCallback(table: 'tl_schema_entity', target: 'fields.externalUrl.save')]
    #[AsCallback(table: 'tl_schema_entity', target: 'fields.eventUrl.save')]
    public function url(mixed $value): string
    {
        $value = trim((string) $value);
        if ($value === '') { return ''; }
        if (!filter_var($value, FILTER_VALIDATE_URL) || !in_array(strtolower((string) parse_url($value, PHP_URL_SCHEME)), ['http','https'], true)
            || parse_url($value, PHP_URL_USER) !== null || parse_url($value, PHP_URL_PASS) !== null) {
            throw new \InvalidArgumentException('Use a complete public HTTP(S) URL without embedded credentials.');
        }
        return $value;
    }
    #[AsCallback(table: 'tl_schema_entity', target: 'fields.latitude.save')]
    public function latitude(mixed $value): string { return $this->coordinate($value, 90); }
    #[AsCallback(table: 'tl_schema_entity', target: 'fields.longitude.save')]
    public function longitude(mixed $value): string { return $this->coordinate($value, 180); }
    private function coordinate(mixed $value, int $limit): string
    {
        return \VHUG\SchemaManagerBundle\Schema\LocationData::coordinate($value,$limit);
    }
    #[AsCallback(table: 'tl_schema_entity', target: 'fields.numberOfEmployees.save')]
    public function employees(mixed $value): string
    {
        $value = trim((string) $value);
        if ($value !== '' && (!ctype_digit($value) || strlen($value) > 9)) { throw new \InvalidArgumentException('Enter a non-negative whole number, or leave blank.'); }
        return $value;
    }
    #[AsCallback(table: 'tl_schema_entity', target: 'fields.openingHours.save')]
    public function hours(mixed $value): string
    {
        $lines = array_values(array_unique(array_filter(array_map('trim', preg_split('/\R/u', (string) $value)))));
        $day = '(?:Mo|Tu|We|Th|Fr|Sa|Su)';
        $time = '(?:[01][0-9]|2[0-3]):[0-5][0-9]';
        foreach ($lines as $line) {
            if (!preg_match('/^'.$day.'(?:[-,]'.$day.')*(?: '.$time.'-'.$time.')?$/D', $line)) {
                throw new \InvalidArgumentException('One opening period per line, e.g. Mo-Fr 09:00-17:00. Use another line for a second period. Days without times mean open all day.');
            }
        }
        return implode("\n", $lines);
    }
    #[AsCallback(table: 'tl_schema_entity', target: 'fields.memberOf.save')]
    public function memberships(mixed $value, DataContainer $dc): mixed { return $this->relationships($value, $dc, ['Organization','LocalBusiness']); }
    #[AsCallback(table: 'tl_schema_entity', target: 'fields.locations.save')]
    #[AsCallback(table: 'tl_schema_entity', target: 'fields.workLocation.save')]
    public function locations(mixed $value, DataContainer $dc): mixed { return $this->relationships($value, $dc, ['LocalBusiness']); }
    public function knowledgeTopicOptions(DataContainer $dc): array
    {
        $options = [];
        foreach ($this->connection->fetchAllAssociative('SELECT id,name,entityType FROM tl_schema_entity WHERE id<>? ORDER BY name', [(int) $dc->id]) as $row) {
            $options[$row['id']] = $row['name'].' ['.$row['entityType'].']';
        }
        return $options;
    }
    #[AsCallback(table: 'tl_schema_entity', target: 'fields.knowledgeTopics.save')]
    public function knowledgeTopics(mixed $value, DataContainer $dc): mixed
    {
        return $this->relationships($value, $dc, ['Organization','LocalBusiness','Person','Service','Product','SoftwareApplication','Event']);
    }
    private function relationships(mixed $value, DataContainer $dc, array $types): mixed
    {
        foreach (StringUtil::deserialize($value, true) as $id) {
            $type = $this->connection->fetchOne('SELECT entityType FROM tl_schema_entity WHERE id=?', [$id]);
            if ((int) $id === (int) $dc->id || !in_array($type, $types, true)) { throw new \InvalidArgumentException('Choose other entities of the appropriate type.'); }
        }
        return $value;
    }
}
