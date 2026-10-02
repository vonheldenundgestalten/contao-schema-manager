<?php
declare(strict_types=1);
require_once dirname(__DIR__).'/src/Schema/EntityMapper.php';
use VHUG\SchemaManagerBundle\Schema\EntityMapper;
$mapper = new EntityMapper();
$check = static function (bool $ok, string $message): void {
    if (!$ok) { throw new RuntimeException($message); }
};
$person = ['entityType' => 'Person', 'entityId' => 'https://example.org/#entity-42', 'name' => 'Anna'];
$organization = ['entityType' => 'Organization', 'entityId' => 'https://example.org/#company', 'name' => 'Example', 'legalName' => 'Example GmbH'];
$de = $mapper->map($person, ['name' => 'Wrong', 'description' => 'Deutsch', 'jobTitle' => 'Entwicklerin'], 'https://example.org/de/anna', $organization);
$en = $mapper->map($person, ['description' => 'English', 'jobTitle' => 'Developer'], 'https://example.org/en/anna', $organization);
$check($de['@id'] === $en['@id'], 'Language must not change identity');
$check($de['url'] !== $en['url'], 'Homes must localize');
$check($de['name'] === 'Anna', 'Person name is shared');
$check($de['description'] === 'Deutsch' && $en['jobTitle'] === 'Developer', 'Content must localize');
$check($de['worksFor'] === ['@id' => $organization['entityId']], 'Relationships reference shared IDs');
$company = $mapper->map($organization, ['name' => 'Wrong'], null);
$check($company['name'] === 'Example' && $company['legalName'] === 'Example GmbH', 'Company names remain shared');
$check(!isset($company['url']), 'Missing translation must not invent a localized URL');
$service = $person; $service['entityType'] = 'Service';
$check($mapper->map($service, ['name' => 'Beratung'], null)['name'] === 'Beratung', 'Service name translates');
$event = $person + ['startDate' => '2027-03-01T10:00:00+01:00', 'eventStatus' => 'EventScheduled'];
$event['entityType'] = 'Event';
$node = $mapper->map($event, null, null, $organization);
$check($node['organizer']['@id'] === $organization['entityId'] && $node['startDate'] === $event['startDate'], 'Standalone event keeps facts and organizer');
echo "PASS: 9 identity, localization and relationship checks\n";
