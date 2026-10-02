<?php
declare(strict_types=1);
namespace VHUG\SchemaManagerBundle\EventListener;
use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\DataContainer;
use Contao\PageModel;
use Doctrine\DBAL\Connection;

final class DataContainerListener
{
    public function __construct(private readonly Connection $connection) {}

    #[AsCallback(table: 'tl_schema_entity', target: 'fields.identityBase.save')]
    public function identityBase(mixed $value, DataContainer $dc): string
    {
        $value = rtrim(trim((string) $value), '/');
        if (!preg_match('~^https://[a-z0-9.-]+(?::[0-9]+)?$~i', $value) || strlen($value) > 180) {
            throw new \InvalidArgumentException('Use an HTTPS origin, e.g. https://example.org (no path, query or fragment).');
        }
        $existing = $this->connection->fetchAssociative('SELECT identityBase, entityId FROM tl_schema_entity WHERE id = ?', [$dc->id]);
        if (!empty($existing['entityId']) && $value !== $existing['identityBase']) {
            throw new \InvalidArgumentException('The identity origin is fixed after the first save.');
        }
        return $value;
    }

    #[AsCallback(table: 'tl_schema_entity', target: 'fields.entityId.save')]
    public function immutableId(mixed $value, DataContainer $dc): ?string
    {
        $id = $this->connection->fetchOne('SELECT entityId FROM tl_schema_entity WHERE id = ?', [$dc->id]);
        return $id ?: null;
    }

    #[AsCallback(table: 'tl_schema_entity', target: 'config.onsubmit')]
    public function saveIdentity(DataContainer $dc): void
    {
        $row = $this->connection->fetchAssociative('SELECT * FROM tl_schema_entity WHERE id = ?', [$dc->id]);
        if ($row && !$row['entityId'] && $row['identityBase']) {
            // Independent of record IDs, schema type, language and deployment host.
            $this->connection->update('tl_schema_entity', [
                'entityId' => $row['identityBase'].'/#entity-'.bin2hex(random_bytes(16)),
            ], ['id' => $dc->id]);
        }
    }

    #[AsCallback(table: 'tl_schema_entity', target: 'fields.startDate.save')]
    #[AsCallback(table: 'tl_schema_entity', target: 'fields.endDate.save')]
    public function date(mixed $value): string
    {
        $value = trim((string) $value);
        if ($value === '') { return ''; }
        $format = strlen($value) === 10 ? '!Y-m-d' : 'Y-m-d\TH:i:sP';
        $date = \DateTimeImmutable::createFromFormat($format, $value);
        $errors = \DateTimeImmutable::getLastErrors();
        if (!$date || ($errors && ($errors['warning_count'] || $errors['error_count']))) {
            throw new \InvalidArgumentException('Use YYYY-MM-DD or YYYY-MM-DDTHH:MM:SS+HH:MM, including the event time-zone offset.');
        }
        return $value;
    }

    #[AsCallback(table: 'tl_schema_entity', target: 'fields.organization.options')]
    public function organizations(): array
    {
        return $this->connection->fetchAllKeyValue(
            "SELECT id, name FROM tl_schema_entity WHERE entityType IN ('Organization', 'LocalBusiness') ORDER BY name"
        );
    }

    #[AsCallback(table: 'tl_schema_entity', target: 'fields.organization.save')]
    public function organization(mixed $value, DataContainer $dc): int
    {
        $value = (int) $value;
        $seen = [(int) $dc->id => true];
        $current = $value;
        while ($current) {
            if (isset($seen[$current])) { throw new \InvalidArgumentException('Organization relationships must not form a cycle.'); }
            $seen[$current] = true;
            $row = $this->connection->fetchAssociative('SELECT entityType, organization FROM tl_schema_entity WHERE id = ?', [$current]);
            if (!$row || !in_array($row['entityType'], ['Organization', 'LocalBusiness'], true)) {
                throw new \InvalidArgumentException('Select an organization or local business.');
            }
            $current = (int) $row['organization'];
        }
        return $value;
    }

    #[AsCallback(table: 'tl_schema_translation', target: 'fields.page.save')]
    public function page(mixed $value, DataContainer $dc): int
    {
        $page = PageModel::findById((int) $value);
        if (!$page || $page->type !== 'regular' || $page->requireItem) { throw new \InvalidArgumentException('Select a regular content page that does not require a reader item.'); }
        $page->loadDetails();
        $pid = (int) $this->connection->fetchOne('SELECT pid FROM tl_schema_translation WHERE id = ?', [$dc->id]);
        if ($this->connection->fetchOne(
            'SELECT id FROM tl_schema_translation WHERE pid = ? AND language = ? AND id <> ?',
            [$pid, $page->language, $dc->id]
        )) {
            throw new \InvalidArgumentException('This entity already has a home in that language. Edit the existing translation.');
        }

        return (int) $value;
    }

    #[AsCallback(table: 'tl_schema_translation', target: 'config.onsubmit')]
    public function saveLanguage(DataContainer $dc): void
    {
        $pageId = $this->connection->fetchOne('SELECT page FROM tl_schema_translation WHERE id = ?', [$dc->id]);
        if ($page = PageModel::findById($pageId)) {
            $page->loadDetails();
            $this->connection->update('tl_schema_translation', ['language' => $page->language], ['id' => $dc->id]);
        }
    }
    #[AsCallback(table: 'tl_schema_translation', target: 'config.onload')]
    public function translationPalette(DataContainer $dc): void
    {
        if (!$dc->id) { return; }
        $type = $this->connection->fetchOne(
            'SELECT e.entityType FROM tl_schema_entity e JOIN tl_schema_translation t ON t.pid = e.id WHERE t.id = ?', [$dc->id]
        );
        $fields = match ($type) {
            'Person' => 'description,jobTitle',
            'Service', 'Event' => 'name,description',
            default => 'description',
        };
        $GLOBALS['TL_DCA']['tl_schema_translation']['palettes']['default'] =
            '{home_legend},page,language,isMainEntity;{content_legend},'.$fields.
            ($type === 'Service' ? ';{source_legend},sourceContent,sourceRow' : '').';{publish_legend},published;{preview_legend:hide},schemaPreview';
    }


    #[AsCallback(table: 'tl_schema_translation', target: 'fields.sourceContent.options')]
    public function pricingElements(DataContainer $dc): array
    {
        $page = (int) $this->connection->fetchOne('SELECT page FROM tl_schema_translation WHERE id=?', [$dc->id]);
        $result = [];
        foreach ($this->connection->fetchAllAssociative("SELECT c.id,c.headline FROM tl_content c JOIN tl_article a ON a.id=c.pid WHERE c.ptable='tl_article' AND c.type='pricing' AND a.pid=? ORDER BY c.sorting", [$page]) as $row) {
            $headline = \Contao\StringUtil::deserialize($row['headline'], true);
            $result[$row['id']] = '#'.$row['id'].' '.($headline['value'] ?? 'Pricing');
        }
        return $result;
    }
    #[AsCallback(table: 'tl_schema_translation', target: 'fields.sourceRow.options')]
    public function pricingRows(DataContainer $dc): array
    {
        $content = $this->connection->fetchOne('SELECT sourceContent FROM tl_schema_translation WHERE id=?', [$dc->id]);
        $rows = \Contao\StringUtil::deserialize(($content ? \Contao\ContentModel::findById($content)?->pricing : null), true);
        $result = [];
        foreach ($rows as $key=>$row) { $result[$key] = $key.' · '.($row['headline'] ?? ''); }
        return $result;
    }
    #[AsCallback(table: 'tl_schema_translation', target: 'fields.sourceContent.save')]
    public function pricingElement(mixed $value, DataContainer $dc): int
    {
        if ($value && !array_key_exists((int) $value, $this->pricingElements($dc))) {
            throw new \InvalidArgumentException('Choose a pricing element on the selected home page.');
        }
        return (int) $value;
    }

    #[AsCallback(table: 'tl_schema_translation', target: 'list.sorting.child_record')]
    public function label(array $row): string
    {
        $page = $this->connection->fetchOne('SELECT title FROM tl_page WHERE id = ?', [$row['page']]);
        return htmlspecialchars(($row['language'] ?: '—').' · '.($page ?: '—'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
