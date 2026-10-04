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
        if ($id) { return $id; }
        $value = trim((string) $value);
        if ($value === '') { return null; }
        $origin = (string) $this->connection->fetchOne('SELECT identityBase FROM tl_schema_entity WHERE id=?', [$dc->id]);
        $value = \VHUG\SchemaManagerBundle\Schema\EntityIdentity::validate($value, $origin);
        if ($this->connection->fetchOne('SELECT id FROM tl_schema_entity WHERE entityId=? AND id<>?', [$value,$dc->id])) {
            throw new \InvalidArgumentException('Another entity already uses this ID.');
        }
        return $value;
    }

    #[AsCallback(table: 'tl_schema_entity', target: 'config.onload')]
    public function identityEditor(DataContainer $dc): void
    {
        if (!$dc->id) { return; }
        $id = $this->connection->fetchOne('SELECT entityId FROM tl_schema_entity WHERE id=?', [$dc->id]);
        $GLOBALS['TL_DCA']['tl_schema_entity']['fields']['entityId']['eval']['readonly'] = (bool) $id;
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
            'Person' => 'description,jobTitle,knowsAbout,credentials,award',
            'Organization', 'LocalBusiness' => 'description,slogan,knowsAbout,catalogName',
            'Service' => 'name,description,serviceType,audienceType,catalogName',
            'Product', 'Event' => 'name,description',
            default => 'description',
        };
        $GLOBALS['TL_DCA']['tl_schema_translation']['palettes']['default'] =
            '{home_legend},page,language,isMainEntity;{content_legend},'.$fields.
            (in_array($type, ['Service', 'Product'], true) ? ';{offer_legend},offerMode,offerDescription' : '').';{publish_legend},published;{import_legend:hide},schemaImportedData;{preview_legend:hide},schemaPreview';
    }


    #[AsCallback(table: 'tl_schema_translation', target: 'fields.offerPrice.save')]
    public function offerPrice(mixed $value): string
    {
        $value = str_replace(',', '.', trim((string) $value));
        if (!preg_match('/^[0-9]+(?:\.[0-9]{1,4})?$/D', $value)) {
            throw new \InvalidArgumentException('Enter a non-negative amount without currency or thousands separators, e.g. 19.90.');
        }
        return $value;
    }

    #[AsCallback(table: 'tl_schema_translation', target: 'fields.offerCurrency.save')]
    public function offerCurrency(mixed $value): string
    {
        $value = strtoupper(trim((string) $value));
        if (!preg_match('/^[A-Z]{3}$/D', $value) || !\Symfony\Component\Intl\Currencies::exists($value)) {
            throw new \InvalidArgumentException('Enter a three-letter ISO currency code, e.g. EUR.');
        }
        return $value;
    }

    #[AsCallback(table: 'tl_schema_translation', target: 'list.sorting.child_record')]
    public function label(array $row): string
    {
        $page = $this->connection->fetchOne('SELECT title FROM tl_page WHERE id = ?', [$row['page']]);
        return htmlspecialchars(($row['language'] ?: '—').' · '.($page ?: '—'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
