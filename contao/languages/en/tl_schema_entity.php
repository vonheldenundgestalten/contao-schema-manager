<?php
declare(strict_types=1);
$GLOBALS['TL_LANG']['tl_schema_entity']['identity_legend'] = 'Identity (shared across languages)';
$GLOBALS['TL_LANG']['tl_schema_entity']['facts_legend'] = 'Shared facts';
$GLOBALS['TL_LANG']['tl_schema_entity']['publish_legend'] = 'Publication';
$GLOBALS['TL_LANG']['tl_schema_entity']['home_legend'] = 'Localized home';
$GLOBALS['TL_LANG']['tl_schema_entity']['content_legend'] = 'Localized content';
$GLOBALS['TL_LANG']['tl_schema_entity']['name'] = ['Name', 'Shared public name. Organization and person names are not translated.'];
$GLOBALS['TL_LANG']['tl_schema_entity']['entityType'] = ['Schema type', 'Controls the relevant fields.'];
$GLOBALS['TL_LANG']['tl_schema_entity']['identityBase'] = ['Identity origin', 'Permanent public origin, e.g. https://www.vhug.tech. Never use the preview server hostname.'];
$GLOBALS['TL_LANG']['tl_schema_entity']['entityId'] = ['Permanent entity ID', 'Generated on first save; retained across translations, page moves and type changes.'];
$GLOBALS['TL_LANG']['tl_schema_entity']['legalName'] = ['Legal name', 'Authoritative legal name shared across languages.'];
$GLOBALS['TL_LANG']['tl_schema_entity']['telephone'] = ['Telephone', 'Shared public telephone number.'];
$GLOBALS['TL_LANG']['tl_schema_entity']['email'] = ['Email', 'Shared public contact address.'];
$GLOBALS['TL_LANG']['tl_schema_entity']['streetAddress'] = ['Street address', 'Shared location address.'];
$GLOBALS['TL_LANG']['tl_schema_entity']['postalCode'] = ['Postal code', ''];
$GLOBALS['TL_LANG']['tl_schema_entity']['addressLocality'] = ['City', ''];
$GLOBALS['TL_LANG']['tl_schema_entity']['addressCountry'] = ['Country code', 'Two-letter country code, e.g. DE.'];
$GLOBALS['TL_LANG']['tl_schema_entity']['organization'] = ['Related organization', 'Employer for people, provider for services, organizer for events, parent for organizations; seller for product offers.'];
$GLOBALS['TL_LANG']['tl_schema_entity']['startDate'] = ['Start', 'YYYY-MM-DD or YYYY-MM-DDTHH:MM:SS+HH:MM.'];
$GLOBALS['TL_LANG']['tl_schema_entity']['endDate'] = ['End', 'YYYY-MM-DD or YYYY-MM-DDTHH:MM:SS+HH:MM.'];
$GLOBALS['TL_LANG']['tl_schema_entity']['locationName'] = ['Venue', 'Name of the physical venue.'];
$GLOBALS['TL_LANG']['tl_schema_entity']['eventStatus'] = ['Event status', ''];
$GLOBALS['TL_LANG']['tl_schema_entity']['published'] = ['Published', 'Both the entity and its localized home must be published to emit the full description.'];
$GLOBALS['TL_LANG']['tl_schema_entity']['page'] = ['Representative page', 'One home per language. The language is taken from this page\'s website root.'];
$GLOBALS['TL_LANG']['tl_schema_entity']['language'] = ['Language', 'Derived from the selected page.'];
$GLOBALS['TL_LANG']['tl_schema_entity']['description'] = ['Description', 'Plain text matching the visible content in this language.'];
$GLOBALS['TL_LANG']['tl_schema_entity']['jobTitle'] = ['Job title', 'Localized role of this person.'];
$GLOBALS['TL_LANG']['tl_schema_entity']['isMainEntity'] = ['Main subject of this page', 'Link the existing WebPage to this entity using mainEntity.'];

$GLOBALS['TL_LANG']['tl_schema_entity']['links_legend'] = 'Links and image';
$GLOBALS['TL_LANG']['tl_schema_entity']['vatID'] = ['VAT ID', 'Legal VAT identification number.'];
$GLOBALS['TL_LANG']['tl_schema_entity']['taxID'] = ['Registration / tax ID', 'Public company registration or tax identifier.'];
$GLOBALS['TL_LANG']['tl_schema_entity']['sameAs'] = ['Official profiles', 'One verified public profile URL per line.'];
$GLOBALS['TL_LANG']['tl_schema_entity']['image'] = ['Logo / portrait / product image', 'Choose an existing public image.'];
$GLOBALS['TL_LANG']['tl_schema_entity']['sku'] = ['SKU', 'Internal product stock keeping unit.'];
$GLOBALS['TL_LANG']['tl_schema_entity']['mpn'] = ['MPN', 'Manufacturer part number, if known.'];
$GLOBALS['TL_LANG']['tl_schema_entity']['brand'] = ['Brand', 'Product brand name, if known.'];

$GLOBALS['TL_LANG']['tl_schema_entity']['alternateName'] = ['Alternate name', 'Shared public short name or alias.'];

$GLOBALS['TL_LANG']['tl_schema_entity']['foundingDate'] = ['Founding date', 'YYYY or YYYY-MM-DD; do not invent an unknown month or day.'];

$GLOBALS['TL_LANG']['tl_schema_entity']['areaServed'] = ['Countries served', 'Shared across languages.'];

$GLOBALS['TL_LANG']['tl_schema_entity']['subservices'] = ['Services in catalogue', 'Reusable Service entities; only published localized destinations are linked.'];

$GLOBALS['TL_LANG']['tl_schema_entity']['contacts'] = ['Contact points', 'Edit organization contact points'];

$GLOBALS['TL_LANG']['tl_schema_entity']['business_legend'] = 'Business facts';

$GLOBALS['TL_LANG']['tl_schema_entity']['relations_legend'] = 'Offices, memberships and services';

$GLOBALS['TL_LANG']['tl_schema_entity']['location_legend'] = 'Location and access';

$GLOBALS['TL_LANG']['tl_schema_entity']['addressRegion'] = ['State / region', ''];

$GLOBALS['TL_LANG']['tl_schema_entity']['postOfficeBoxNumber'] = ['PO box number', ''];

$GLOBALS['TL_LANG']['tl_schema_entity']['faxNumber'] = ['Fax', ''];

$GLOBALS['TL_LANG']['tl_schema_entity']['numberOfEmployees'] = ['Employee count', 'Whole number; leave blank when unknown.'];

$GLOBALS['TL_LANG']['tl_schema_entity']['latitude'] = ['Latitude', 'Decimal degrees from -90 to 90. Supply both coordinates.'];

$GLOBALS['TL_LANG']['tl_schema_entity']['longitude'] = ['Longitude', 'Decimal degrees from -180 to 180. Supply both coordinates.'];

$GLOBALS['TL_LANG']['tl_schema_entity']['hasMap'] = ['Map URL', ''];

$GLOBALS['TL_LANG']['tl_schema_entity']['openingHours'] = ['Opening hours', 'One period per line: Mo-Fr 09:00-17:00. Days without times mean open all day.'];

$GLOBALS['TL_LANG']['tl_schema_entity']['externalUrl'] = ['External organization website', 'For external networks/partners without a local home page. A published local translation takes precedence.'];

$GLOBALS['TL_LANG']['tl_schema_entity']['eventUrl'] = ['Public online event URL', 'Public attendance link for online or mixed events; no private access tokens.'];

$GLOBALS['TL_LANG']['tl_schema_entity']['priceRange'] = ['Price range', 'LocalBusiness only; use publicly supported information, e.g. €€€.'];

$GLOBALS['TL_LANG']['tl_schema_entity']['locations'] = ['Additional offices', 'Direct LocalBusiness children are linked automatically. Select any additional offices shown in your content.'];

$GLOBALS['TL_LANG']['tl_schema_entity']['memberOf'] = ['Member of', 'Networks and associations, distinct from a parent company.'];

$GLOBALS['TL_LANG']['tl_schema_entity']['workLocation'] = ['Workplaces', 'Physical offices; select the employer separately.'];

$GLOBALS['TL_LANG']['tl_schema_entity']['eventAttendanceMode'] = ['Attendance mode', ''];

$GLOBALS['TL_LANG']['tl_schema_entity']['subservices'] = ['Service catalogue', 'Reusable Service entities with published localized homes.'];

$GLOBALS['TL_LANG']['tl_schema_entity']['entityId'] = ['Permanent entity ID', 'Before first save, optionally enter an established HTTPS ID; leave blank to generate one. Locked afterwards.'];
$GLOBALS['TL_LANG']['tl_schema_entity']['attendanceModes'][''] = 'Not specified';
$GLOBALS['TL_LANG']['tl_schema_entity']['attendanceModes']['OfflineEventAttendanceMode'] = 'In person';
$GLOBALS['TL_LANG']['tl_schema_entity']['attendanceModes']['OnlineEventAttendanceMode'] = 'Online';
$GLOBALS['TL_LANG']['tl_schema_entity']['attendanceModes']['MixedEventAttendanceMode'] = 'Mixed: in person and online';

$GLOBALS['TL_LANG']['tl_schema_entity']['relationships'] = ['Entity relationships', 'Explore the connections between your managed entities. Select an entity to see its relationships and localized homes.'];

$GLOBALS['TL_LANG']['tl_schema_entity']['knowledgeTopics'] = ['Linked knowledge topics', 'Select subjects this person or organization knows about, such as services or products. These links are shared across languages and complement the free-text topics in translations. They do not imply service provision or responsibility.'];

$GLOBALS['TL_LANG']['tl_schema_entity']['ai'] = ['AI helper', 'AI helper'];

$GLOBALS['TL_LANG']['tl_schema_entity']['award'] = ['Awards', 'One public award per line, including its year where useful. Shared across all languages; use the official award name.'];

$GLOBALS['TL_LANG']['tl_schema_entity']['areaServedWorldwide'] = ["Worldwide coverage", "Outputs Worldwide instead of the selected countries. Applies to every language."];

$GLOBALS['TL_LANG']['tl_schema_entity']['registrationIdentifiers'] = ["Registration identifiers", "Register name and registration number, one pair per row. These are not tax IDs."];

$GLOBALS['TL_LANG']['tl_schema_entity']['registerName'] = "Register name";

$GLOBALS['TL_LANG']['tl_schema_entity']['registerNumber'] = "Registration number";

$GLOBALS['TL_LANG']['tl_schema_entity']['registerName'] = "Default register name";

$GLOBALS['TL_LANG']['tl_schema_entity']['applicationCategory'] = ['Application category', 'For example BusinessApplication or DeveloperApplication.'];

$GLOBALS['TL_LANG']['tl_schema_entity']['operatingSystem'] = ['Operating system', 'Supported systems, or platform independence when applicable.'];

$GLOBALS['TL_LANG']['tl_schema_entity']['softwareVersion'] = ['Software version', 'The current version, if relevant.'];

$GLOBALS['TL_LANG']['tl_schema_entity']['runtimePlatform'] = ['Runtime platform', 'For example Contao 5.7 and PHP 8.3.'];

$GLOBALS['TL_LANG']['tl_schema_entity']['eventLocationMode'] = ['Event location', 'Choose an existing venue or use a custom address.'];

$GLOBALS['TL_LANG']['tl_schema_entity']['eventPlace'] = ['Existing location', 'Only published Places and LocalBusiness entries are output. Hidden custom address fields remain saved.'];
$GLOBALS['TL_LANG']['tl_schema_entity']['locationModes'][''] = 'Custom location';
$GLOBALS['TL_LANG']['tl_schema_entity']['locationModes']['existing'] = 'Existing location';
$GLOBALS['TL_LANG']['MSC']['schemaLocationDraft'] = 'unpublished';
