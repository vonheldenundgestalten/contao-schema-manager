<?php
declare(strict_types=1);
namespace VHUG\SchemaManagerBundle\Schema;

/** Pure mapping: identity and shared facts never depend on the current request. */
final class EntityMapper
{
    public function map(array $entity, ?array $translation, ?string $url, ?array $organization = null): array
    {
        $type = $entity['entityType'];
        $name = in_array($type, ['Service', 'Product', 'SoftwareApplication', 'Event', 'Place'], true)
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
            foreach (['legalName', 'alternateName', 'foundingDate', 'telephone', 'email', 'faxNumber'] as $key) {
                $node[$key] = $entity[$key] ?? null;
            }
            $address = [];
            foreach (['streetAddress', 'postalCode', 'addressLocality', 'addressRegion', 'addressCountry', 'postOfficeBoxNumber'] as $key) {
                if (!empty($entity[$key])) { $address[$key] = $entity[$key]; }
            }
            if ($address) { $node['address'] = ['@type' => 'PostalAddress'] + $address; }
        }
        if (in_array($type, ['Organization', 'LocalBusiness', 'Person'], true)) {
            if ($values = self::lines($translation['knowsAbout'] ?? '')) { $node['knowsAbout'] = $values; }
            $awards = $type === 'Person' ? ($translation['award'] ?? '') : ($entity['award'] ?? '');
            if ($values = self::lines($awards)) { $node['award'] = $values; }
        }
        if (in_array($type, ['Organization', 'LocalBusiness'], true)) {
            $node['slogan'] = $translation['slogan'] ?? null;
            $count = (string) ($entity['numberOfEmployees'] ?? '');
            if ($count !== '' && ctype_digit($count)) { $node['numberOfEmployees'] = ['@type' => 'QuantitativeValue', 'value' => (int) $count]; }
        }
        if ($type === 'LocalBusiness') {
            foreach (['hasMap', 'priceRange'] as $field) { $node[$field] = $entity[$field] ?? null; }
            if ($hours = self::lines($entity['openingHours'] ?? '')) { $node['openingHours'] = $hours; }
            if($geo=LocationData::physical($entity)['geo']??null)$node['geo']=$geo;
        }
        if ($type === 'Place') { $node += array_diff_key(LocationData::physical($entity), ['@type'=>true]); }
        if ($type === 'Person') {
            $node['jobTitle'] = $translation['jobTitle'] ?? null;
            if ($credentials = self::lines($translation['credentials'] ?? '')) {
                $node['hasCredential'] = array_map(static fn ($name) => ['@type' => 'EducationalOccupationalCredential', 'name' => $name], $credentials);
            }
            foreach (['telephone', 'email'] as $key) { $node[$key] = $entity[$key] ?? null; }
        }
        if ($type === 'Service') {
            $node['serviceType'] = $translation['serviceType'] ?? null;
            if (!empty($translation['audienceType'])) { $node['audience'] = ['@type' => 'Audience', 'audienceType' => $translation['audienceType']]; }
        }
        if ($organization && !in_array($type, ['Product','Place'], true)) {
            $property = match ($type) {
                'Person' => 'worksFor', 'Service' => 'provider',
                'Event' => 'organizer', 'SoftwareApplication' => 'publisher', default => 'parentOrganization',
            };
            $node[$property] = ['@id' => $organization['entityId']];
        }
        if ($type === 'SoftwareApplication') {
            foreach (['applicationCategory', 'operatingSystem', 'softwareVersion', 'runtimePlatform'] as $key) { $node[$key] = $entity[$key] ?? null; }
            foreach (['softwareRequirements', 'featureList'] as $key) { $node[$key] = $translation[$key] ?? null; }
        }
        if ($type === 'Product') {
            foreach (['sku', 'mpn'] as $key) { $node[$key] = $entity[$key] ?? null; }
            if (!empty($entity['brand'])) { $node['brand'] = ['@type' => 'Brand', 'name' => $entity['brand']]; }
        }
        if (in_array($type, ['Product', 'Service', 'SoftwareApplication'], true) && $translation && $url) {
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
            $mode = $entity['eventAttendanceMode'] ?? '';
            if (in_array($mode, ['OfflineEventAttendanceMode', 'OnlineEventAttendanceMode', 'MixedEventAttendanceMode'], true)) {
                $node['eventAttendanceMode'] = 'https://schema.org/'.$mode;
            }
            $physical=($entity['eventLocationMode']??'')==='existing'?null:LocationData::physical($entity);
            if($locations=LocationData::combine($mode,$physical,(string)($entity['eventUrl']??'')))$node['location']=$locations;
        }
        return array_filter($node, static fn ($v): bool => $v !== null && $v !== '' && $v !== []);
    }
    private static function lines(string $value): array
    {
        return array_values(array_unique(array_filter(array_map('trim', preg_split('/\R/u', $value)))));
    }
}
