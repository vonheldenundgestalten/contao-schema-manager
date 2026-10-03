<?php
declare(strict_types=1);
namespace VHUG\SchemaManagerBundle\Schema;

/** Pure mapping: identity and shared facts never depend on the current request. */
final class EntityMapper
{
    public function map(array $entity, ?array $translation, ?string $url, ?array $organization = null): array
    {
        $type = $entity['entityType'];
        $name = in_array($type, ['Service', 'Product', 'Event'], true)
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
            foreach (['legalName', 'alternateName', 'foundingDate', 'telephone', 'email'] as $key) {
                $node[$key] = $entity[$key] ?? null;
            }
            $address = [];
            foreach (['streetAddress', 'postalCode', 'addressLocality', 'addressCountry'] as $key) {
                if (!empty($entity[$key])) { $address[$key] = $entity[$key]; }
            }
            if ($address) { $node['address'] = ['@type' => 'PostalAddress'] + $address; }
        }
        if ($type === 'Person') {
            $node['jobTitle'] = $translation['jobTitle'] ?? null;
            foreach (['telephone', 'email'] as $key) { $node[$key] = $entity[$key] ?? null; }
        }
        if ($type === 'Service') {
            $node['serviceType'] = $translation['serviceType'] ?? null;
            if (!empty($translation['audienceType'])) { $node['audience'] = ['@type' => 'Audience', 'audienceType' => $translation['audienceType']]; }
        }
        if ($organization && $type !== 'Product') {
            $property = match ($type) {
                'Person' => 'worksFor', 'Service' => 'provider',
                'Event' => 'organizer', default => 'parentOrganization',
            };
            $node[$property] = ['@id' => $organization['entityId']];
        }
        if ($type === 'Product') {
            foreach (['sku', 'mpn'] as $key) { $node[$key] = $entity[$key] ?? null; }
            if (!empty($entity['brand'])) { $node['brand'] = ['@type' => 'Brand', 'name' => $entity['brand']]; }
        }
        if (in_array($type, ['Product', 'Service'], true) && $translation && $url) {
            $mode = $translation['offerMode'] ?? '';
            if (in_array($mode, ['exact', 'from', 'quote'], true)) {
                $offer = ['@type' => 'Offer', '@id' => $entity['entityId'].'/offer',
                    'itemOffered' => ['@id' => $entity['entityId']], 'name' => $name, 'url' => $url];
                if (!empty($translation['offerDescription'])) { $offer['description'] = $translation['offerDescription']; }
                if ($organization) { $offer['seller'] = ['@id' => $organization['entityId']]; }
                if ($mode !== 'quote') {
                    $amount = (string) ($translation['offerPrice'] ?? '');
                    $currency = (string) ($translation['offerCurrency'] ?? '');
                    if (!preg_match('/^[0-9]+(?:\.[0-9]{1,4})?$/D', $amount) || !preg_match('/^[A-Z]{3}$/D', $currency)) {
                        return array_filter($node, static fn ($v): bool => $v !== null && $v !== '' && $v !== []);
                    }
                    $spec = ['@type' => 'UnitPriceSpecification', 'priceCurrency' => $currency,
                        $mode === 'from' ? 'minPrice' : 'price' => $amount];
                    if (in_array($translation['offerUnit'] ?? '', ['MON', 'ANN', 'HUR', 'DAY'], true)) {
                        $spec['referenceQuantity'] = ['@type' => 'QuantitativeValue', 'value' => 1, 'unitCode' => $translation['offerUnit']];
                    }
                    $offer['priceSpecification'] = $spec;
                }
                if (in_array($translation['offerAvailability'] ?? '', ['InStock', 'OutOfStock', 'PreOrder', 'LimitedAvailability', 'Discontinued'], true)) {
                    $offer['availability'] = 'https://schema.org/'.$translation['offerAvailability'];
                }
                $node['offers'] = $offer;
            }
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
