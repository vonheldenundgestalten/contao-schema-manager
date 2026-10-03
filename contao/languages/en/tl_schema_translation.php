<?php
declare(strict_types=1);
$GLOBALS['TL_LANG']['tl_schema_translation']['identity_legend'] = 'Identity (shared across languages)';
$GLOBALS['TL_LANG']['tl_schema_translation']['facts_legend'] = 'Shared facts';
$GLOBALS['TL_LANG']['tl_schema_translation']['publish_legend'] = 'Publication';
$GLOBALS['TL_LANG']['tl_schema_translation']['home_legend'] = 'Localized home';
$GLOBALS['TL_LANG']['tl_schema_translation']['content_legend'] = 'Localized content';
$GLOBALS['TL_LANG']['tl_schema_translation']['name'] = ['Name', 'Shared public name. Organization and person names are not translated.'];
$GLOBALS['TL_LANG']['tl_schema_translation']['entityType'] = ['Schema type', 'Controls the relevant fields.'];
$GLOBALS['TL_LANG']['tl_schema_translation']['identityBase'] = ['Identity origin', 'Permanent public origin, e.g. https://www.vhug.tech. Never use the preview server hostname.'];
$GLOBALS['TL_LANG']['tl_schema_translation']['entityId'] = ['Permanent entity ID', 'Generated on first save; retained across translations, page moves and type changes.'];
$GLOBALS['TL_LANG']['tl_schema_translation']['legalName'] = ['Legal name', 'Authoritative legal name shared across languages.'];
$GLOBALS['TL_LANG']['tl_schema_translation']['telephone'] = ['Telephone', 'Shared public telephone number.'];
$GLOBALS['TL_LANG']['tl_schema_translation']['email'] = ['Email', 'Shared public contact address.'];
$GLOBALS['TL_LANG']['tl_schema_translation']['streetAddress'] = ['Street address', 'Shared location address.'];
$GLOBALS['TL_LANG']['tl_schema_translation']['postalCode'] = ['Postal code', ''];
$GLOBALS['TL_LANG']['tl_schema_translation']['addressLocality'] = ['City', ''];
$GLOBALS['TL_LANG']['tl_schema_translation']['addressCountry'] = ['Country code', 'Two-letter country code, e.g. DE.'];
$GLOBALS['TL_LANG']['tl_schema_translation']['organization'] = ['Related organization', 'Employer for people, provider for services, organizer for events, parent for organizations.'];
$GLOBALS['TL_LANG']['tl_schema_translation']['startDate'] = ['Start', 'YYYY-MM-DD or YYYY-MM-DDTHH:MM:SS+HH:MM.'];
$GLOBALS['TL_LANG']['tl_schema_translation']['endDate'] = ['End', 'YYYY-MM-DD or YYYY-MM-DDTHH:MM:SS+HH:MM.'];
$GLOBALS['TL_LANG']['tl_schema_translation']['locationName'] = ['Venue', 'Name of the physical venue.'];
$GLOBALS['TL_LANG']['tl_schema_translation']['eventStatus'] = ['Event status', ''];
$GLOBALS['TL_LANG']['tl_schema_translation']['published'] = ['Published', 'Both the entity and its localized home must be published to emit the full description.'];
$GLOBALS['TL_LANG']['tl_schema_translation']['page'] = ['Representative page', 'One home per language. The language is taken from this page\'s website root.'];
$GLOBALS['TL_LANG']['tl_schema_translation']['language'] = ['Language', 'Derived from the selected page.'];
$GLOBALS['TL_LANG']['tl_schema_translation']['description'] = ['Description', 'Plain text matching the visible content in this language.'];
$GLOBALS['TL_LANG']['tl_schema_translation']['jobTitle'] = ['Job title', 'Localized role of this person.'];
$GLOBALS['TL_LANG']['tl_schema_translation']['isMainEntity'] = ['Main subject of this page', 'Link the existing WebPage to this entity using mainEntity.'];
$GLOBALS['TL_LANG']['tl_schema_translation']['name'] = ['Localized name', 'For products, events and services. Leave empty to use the shared name.'];

$GLOBALS['TL_LANG']['tl_schema_translation']['preview_legend'] = 'Published JSON-LD preview';
$GLOBALS['TL_LANG']['tl_schema_translation']['schemaPreview'] = ['Saved output', 'Save first to refresh. Shows the published entity and its referenced organization; unpublished records are omitted.'];

$GLOBALS['TL_LANG']['tl_schema_translation']['offer_legend'] = 'Manual offer';
$GLOBALS['TL_LANG']['tl_schema_translation']['offerMode'] = ['Offer type', 'Maintain this offer by hand. Select no offer to omit pricing.'];
$GLOBALS['TL_LANG']['tl_schema_translation']['offerPrice'] = ['Amount', 'Non-negative amount without currency/thousands separators; e.g. 19.90.'];
$GLOBALS['TL_LANG']['tl_schema_translation']['offerCurrency'] = ['Currency', 'Three-letter ISO currency code, e.g. EUR.'];
$GLOBALS['TL_LANG']['tl_schema_translation']['offerUnit'] = ['Billing unit', 'Leave empty for a one-off amount.'];
$GLOBALS['TL_LANG']['tl_schema_translation']['offerAvailability'] = ['Availability', 'Only select a status confirmed by the visible page content.'];
$GLOBALS['TL_LANG']['tl_schema_translation']['offerDescription'] = ['Offer description', 'Optional localized price or quotation explanation matching visible content.'];
$GLOBALS['TL_LANG']['tl_schema_translation']['offerModes'] = ['' => 'No offer', 'exact' => 'Exact price', 'from' => 'Starting price', 'quote' => 'On request'];
$GLOBALS['TL_LANG']['tl_schema_translation']['offerUnits'] = ['' => 'One-off', 'MON' => 'Month', 'ANN' => 'Year', 'HUR' => 'Hour', 'DAY' => 'Day'];

$GLOBALS['TL_LANG']['tl_schema_translation']['serviceType'] = ['Service type', 'Localized classification, e.g. restructuring consultancy.'];

$GLOBALS['TL_LANG']['tl_schema_translation']['audienceType'] = ['Audience', 'Localized description of the intended audience.'];

$GLOBALS['TL_LANG']['tl_schema_translation']['catalogName'] = ['Catalogue title', 'Leave blank to use this service’s name.'];

$GLOBALS['TL_LANG']['tl_schema_translation']['slogan'] = ['Slogan', 'Localized public slogan.'];

$GLOBALS['TL_LANG']['tl_schema_translation']['knowsAbout'] = ['Expertise', 'One publicly stated area of expertise per line.'];

$GLOBALS['TL_LANG']['tl_schema_translation']['award'] = ['Awards', 'One public award per line, including its year where useful.'];

$GLOBALS['TL_LANG']['tl_schema_translation']['credentials'] = ['Professional qualifications', 'One substantiated public qualification per line.'];
