<?php
declare(strict_types=1);
namespace VHUG\SchemaManagerBundle\Schema;
/** Maps public job data only; no article-specific properties are inherited. */
final class JobMapper
{
    public function map(array $record, array $defaults, array $employer, string $url, string $language, string $title, string $description, ?int $now = null): ?array
    {
        $now ??= time();
        $expiry = (int) ($record['schemaJobValidThrough'] ?? 0);
        if ($expiry && $expiry <= $now) { return null; }
        if (empty($employer['@id']) || empty($record['schemaIdentity']) || trim($description) === '' || trim($title) === '' || empty($record['date'])) { return null; }
        $value = static function (string $name) use ($record, $defaults): mixed {
            $key = 'schemaJob'.$name;
            $own = $record[$key] ?? null;
            if (in_array($name, ['Employment', 'ApplicantCountries'], true)) {
                return self::list($own) ?: self::list($defaults[$key] ?? null);
            }
            return trim((string) $own) !== '' ? $own : ($defaults[$key] ?? '');
        };
        $remote = $value('Workplace') === 'remote';
        $countries = array_values(array_filter($value('ApplicantCountries'), static fn ($v) => is_string($v) && preg_match('/^[A-Z]{2}$/D', $v)));
        if ($remote && !$countries) { return null; }
        if (!$remote && (!$value('City') || !$value('Country'))) { return null; }
        $node = ['@type' => 'JobPosting', '@id' => $record['schemaIdentity'], 'title' => $title,
            'description' => $description, 'datePosted' => date('Y-m-d', (int) $record['date']),
            'url' => $url, 'inLanguage' => $language, 'mainEntityOfPage' => ['@id' => $url.'#webpage'],
            'hiringOrganization' => ['@id' => $employer['@id']]];
        if ($expiry) { $node['validThrough'] = date(DATE_ATOM, $expiry); }
        $employment = array_values(array_intersect($value('Employment'), JobFields::EMPLOYMENT));
        if ($employment) { $node['employmentType'] = $employment; }
        if ($remote) {
            $node['jobLocationType'] = 'TELECOMMUTE';
        } else {
            $address = ['@type' => 'PostalAddress'];
            foreach (['Street' => 'streetAddress', 'PostalCode' => 'postalCode', 'City' => 'addressLocality', 'Region' => 'addressRegion', 'Country' => 'addressCountry'] as $field => $property) {
                if ($value($field)) { $address[$property] = $value($field); }
            }
            $node['jobLocation'] = ['@type' => 'Place', 'address' => $address];
            if ($value('LocationName')) { $node['jobLocation']['name'] = $value('LocationName'); }
        }
        if ($countries) { $node['applicantLocationRequirements'] = array_map(static fn ($code) => ['@type' => 'Country', 'name' => $code], $countries); }
        return $node;
    }
    private static function list(mixed $value): array
    {
        if (is_array($value)) { return $value; }
        if (!$value) { return []; }
        $decoded = @unserialize((string) $value, ['allowed_classes' => false]);
        return is_array($decoded) ? $decoded : [];
    }
}
