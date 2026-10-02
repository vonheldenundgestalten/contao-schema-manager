<?php
declare(strict_types=1);
namespace VHUG\SchemaManagerBundle\Schema;

/** Pure mapping: identity and shared facts never depend on the current request. */
final class EntityMapper
{
    public function map(array $entity, ?array $translation, ?string $url, ?array $organization = null): array
    {
        $type = $entity['entityType'];
        $name = in_array($type, ['Service', 'Event'], true)
            ? ($translation['name'] ?? '') ?: $entity['name']
            : $entity['name'];
        $node = [
            '@type' => $type,
            '@id' => $entity['entityId'],
            'name' => $name,
            'url' => $url,
            'description' => $translation['description'] ?? null,
        ];
        if (in_array($type, ['Organization', 'LocalBusiness'], true)) {
            foreach (['legalName', 'telephone', 'email'] as $key) {
                $node[$key] = $entity[$key] ?? null;
            }
            $address = [];
            foreach (['streetAddress', 'postalCode', 'addressLocality', 'addressCountry'] as $key) {
                if (!empty($entity[$key])) { $address[$key] = $entity[$key]; }
            }
            if ($address) { $node['address'] = ['@type' => 'PostalAddress'] + $address; }
        }
        if ($type === 'Person') { $node['jobTitle'] = $translation['jobTitle'] ?? null; }
        if ($organization) {
            $property = match ($type) {
                'Person' => 'worksFor', 'Service' => 'provider',
                'Event' => 'organizer', default => 'parentOrganization',
            };
            $node[$property] = ['@id' => $organization['entityId']];
        }
        if ($type === 'Event') {
            foreach (['startDate', 'endDate'] as $key) { $node[$key] = $entity[$key] ?? null; }
            $node['eventStatus'] = 'https://schema.org/'.($entity['eventStatus'] ?: 'EventScheduled');
            if (!empty($entity['locationName'])) {
                $node['location'] = ['@type' => 'Place', 'name' => $entity['locationName']];
            }
        }
        return array_filter($node, static fn ($v): bool => $v !== null && $v !== '' && $v !== []);
    }
}
