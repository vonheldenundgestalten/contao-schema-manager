<?php
declare(strict_types=1);
namespace VHUG\SchemaManagerBundle\EventListener;
use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\DataContainer;
use Contao\StringUtil;
use Doctrine\DBAL\Connection;
use Symfony\Component\Intl\Countries;
final class EntityDetailsListener
{
    public function __construct(private readonly Connection $connection) {}
    #[AsCallback(table: 'tl_schema_entity', target: 'list.operations.contacts.button')]
    public function contactsButton(\Contao\CoreBundle\DataContainer\DataContainerOperation $operation): void
    {
        if (!in_array($operation->getRecord()['entityType'] ?? '', ['Organization', 'LocalBusiness'], true)) { $operation->hide(); }
    }
    #[AsCallback(table: 'tl_schema_contact', target: 'fields.published.save')]
    public function publishContact(mixed $value, DataContainer $dc): mixed
    {
        if ($value) {
            $row = $this->connection->fetchAssociative('SELECT telephone,email FROM tl_schema_contact WHERE id=?', [$dc->id]);
            if (!$row || (!trim($row['telephone']) && !trim($row['email']))) { throw new \InvalidArgumentException('Provide a telephone number or email before publishing this contact point.'); }
        }
        return $value;
    }
    public function countries(): array { return Countries::getNames(); }
    public function services(): array { return $this->connection->fetchAllKeyValue("SELECT id,name FROM tl_schema_entity WHERE entityType='Service' ORDER BY name"); }
    #[AsCallback(table: 'tl_schema_entity', target: 'fields.foundingDate.save')]
    public function foundingDate(mixed $value): string
    {
        $value = trim((string) $value);
        if ($value === '' || preg_match('/^[1-9][0-9]{3}$/D', $value)) { return $value; }
        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $value);
        if (!$date || $date->format('Y-m-d') !== $value) { throw new \InvalidArgumentException('Use YYYY or YYYY-MM-DD. Leave blank when the date is unknown.'); }
        return $value;
    }
    #[AsCallback(table: 'tl_schema_contact', target: 'fields.availableLanguage.save')]
    public function languages(mixed $value): string
    {
        $parts = array_unique(array_filter(array_map('trim', explode(',', (string) $value))));
        foreach ($parts as $part) {
            if (!preg_match('/^[a-z]{2,3}(?:-[a-z0-9]{2,8})*$/iD', $part)) { throw new \InvalidArgumentException('Use comma-separated language codes, e.g. de, en, fr-CH.'); }
        }
        return implode(', ', $parts);
    }
    #[AsCallback(table: 'tl_schema_contact', target: 'list.sorting.child_record')]
    public function contactLabel(array $row): string
    {
        $type = $GLOBALS['TL_LANG']['tl_schema_contact']['types'][$row['contactType']] ?? $row['contactType'];
        return htmlspecialchars($type.' · '.$row['telephone'].' · '.$row['email'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
    #[AsCallback(table: 'tl_schema_contact', target: 'config.onload')]
    public function contactParent(DataContainer $dc): void
    {
        $pid = $dc->id && in_array(\Contao\Input::get('act'), ['edit', 'show', 'copy', 'delete'], true) ? $this->connection->fetchOne('SELECT pid FROM tl_schema_contact WHERE id=?', [$dc->id]) : \Contao\Input::get('id');
        if (!$pid) { return; }
        $type = $this->connection->fetchOne('SELECT entityType FROM tl_schema_entity WHERE id=?', [$pid]);
        if (!in_array($type, ['Organization', 'LocalBusiness'], true)) { throw new \InvalidArgumentException('Contact points belong to organizations or local businesses.'); }
    }
    #[AsCallback(table: 'tl_schema_entity', target: 'fields.subservices.save')]
    public function subservices(mixed $value, DataContainer $dc): mixed
    {
        $visit = function (int $id, array $path) use (&$visit): void {
            if (isset($path[$id])) { throw new \InvalidArgumentException('Service catalogues must not contain circular relationships.'); }
            $row = $this->connection->fetchAssociative('SELECT entityType,subservices FROM tl_schema_entity WHERE id=?', [$id]);
            if (!$row || $row['entityType'] !== 'Service') { throw new \InvalidArgumentException('Select service entities only.'); }
            $path[$id] = true;
            foreach (StringUtil::deserialize($row['subservices'], true) as $child) { $visit((int) $child, $path); }
        };
        foreach (StringUtil::deserialize($value, true) as $id) { $visit((int) $id, [(int) $dc->id => true]); }
        return $value;
    }
}
