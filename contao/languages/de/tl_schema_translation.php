<?php
declare(strict_types=1);
$GLOBALS['TL_LANG']['tl_schema_translation']['identity_legend'] = 'Identität (sprachübergreifend)';
$GLOBALS['TL_LANG']['tl_schema_translation']['facts_legend'] = 'Gemeinsame Angaben';
$GLOBALS['TL_LANG']['tl_schema_translation']['publish_legend'] = 'Veröffentlichung';
$GLOBALS['TL_LANG']['tl_schema_translation']['home_legend'] = 'Sprachabhängige Hauptseite';
$GLOBALS['TL_LANG']['tl_schema_translation']['content_legend'] = 'Übersetzte Inhalte';
$GLOBALS['TL_LANG']['tl_schema_translation']['name'] = ['Name', 'Gemeinsamer öffentlicher Name. Namen von Organisationen und Personen werden nicht übersetzt.'];
$GLOBALS['TL_LANG']['tl_schema_translation']['entityType'] = ['Schema-Typ', 'Bestimmt die angezeigten Felder.'];
$GLOBALS['TL_LANG']['tl_schema_translation']['identityBase'] = ['Domain der Identität', 'Dauerhafte öffentliche Domain, z. B. https://www.vhug.tech. Nicht die Domain des Abnahmeservers verwenden.'];
$GLOBALS['TL_LANG']['tl_schema_translation']['entityId'] = ['Dauerhafte Entitäts-ID', 'Wird beim ersten Speichern erzeugt und bleibt bei Übersetzungen, Seitenumzügen und Typwechseln erhalten.'];
$GLOBALS['TL_LANG']['tl_schema_translation']['legalName'] = ['Rechtlicher Name', 'Verbindlicher rechtlicher Name für alle Sprachen.'];
$GLOBALS['TL_LANG']['tl_schema_translation']['telephone'] = ['Telefon', 'Gemeinsame öffentliche Telefonnummer.'];
$GLOBALS['TL_LANG']['tl_schema_translation']['email'] = ['E-Mail', 'Gemeinsame öffentliche Kontaktadresse.'];
$GLOBALS['TL_LANG']['tl_schema_translation']['streetAddress'] = ['Straße und Hausnummer', 'Gemeinsame Anschrift des Standorts.'];
$GLOBALS['TL_LANG']['tl_schema_translation']['postalCode'] = ['Postleitzahl', ''];
$GLOBALS['TL_LANG']['tl_schema_translation']['addressLocality'] = ['Ort', ''];
$GLOBALS['TL_LANG']['tl_schema_translation']['addressCountry'] = ['Ländercode', 'Zweistelliger Ländercode, z. B. DE.'];
$GLOBALS['TL_LANG']['tl_schema_translation']['organization'] = ['Verknüpfte Organisation', 'Arbeitgeber bei Personen, Anbieter bei Leistungen, Veranstalter bei Events, übergeordnete Organisation.'];
$GLOBALS['TL_LANG']['tl_schema_translation']['startDate'] = ['Beginn', 'JJJJ-MM-TT oder JJJJ-MM-TTTHH:MM:SS+HH:MM.'];
$GLOBALS['TL_LANG']['tl_schema_translation']['endDate'] = ['Ende', 'JJJJ-MM-TT oder JJJJ-MM-TTTHH:MM:SS+HH:MM.'];
$GLOBALS['TL_LANG']['tl_schema_translation']['locationName'] = ['Veranstaltungsort', 'Name des Veranstaltungsorts.'];
$GLOBALS['TL_LANG']['tl_schema_translation']['eventStatus'] = ['Veranstaltungsstatus', ''];
$GLOBALS['TL_LANG']['tl_schema_translation']['published'] = ['Veröffentlicht', 'Entität und Sprachversion müssen veröffentlicht sein, um die vollständige Beschreibung auszugeben.'];
$GLOBALS['TL_LANG']['tl_schema_translation']['page'] = ['Repräsentative Seite', 'Eine Hauptseite pro Sprache. Die Sprache wird aus dem Startpunkt dieser Seite übernommen.'];
$GLOBALS['TL_LANG']['tl_schema_translation']['language'] = ['Sprache', 'Wird aus der ausgewählten Seite ermittelt.'];
$GLOBALS['TL_LANG']['tl_schema_translation']['description'] = ['Beschreibung', 'Klartext passend zum sichtbaren Inhalt in dieser Sprache.'];
$GLOBALS['TL_LANG']['tl_schema_translation']['jobTitle'] = ['Berufsbezeichnung', 'Übersetzte Funktion dieser Person.'];
$GLOBALS['TL_LANG']['tl_schema_translation']['isMainEntity'] = ['Hauptthema dieser Seite', 'Verknüpft die bestehende WebPage über mainEntity mit dieser Entität.'];
$GLOBALS['TL_LANG']['tl_schema_translation']['name'] = ['Übersetzter Name', 'Nur für Veranstaltungen und Leistungen. Leer lassen, um den gemeinsamen Namen zu verwenden.'];

$GLOBALS['TL_LANG']['tl_schema_translation']['preview_legend'] = 'Vorschau der veröffentlichten JSON-LD-Daten';
$GLOBALS['TL_LANG']['tl_schema_translation']['schemaPreview'] = ['Gespeicherte Ausgabe', 'Zum Aktualisieren zuerst speichern. Zeigt die veröffentlichte Entität und ihre Organisation; unveröffentlichte Datensätze werden ausgelassen.'];

$GLOBALS['TL_LANG']['tl_schema_translation']['offer_legend'] = 'Manuelles Angebot';
$GLOBALS['TL_LANG']['tl_schema_translation']['offerMode'] = ['Angebotstyp', 'Dieses Angebot wird manuell gepflegt. Ohne Angebot werden keine Preise ausgegeben.'];
$GLOBALS['TL_LANG']['tl_schema_translation']['offerPrice'] = ['Betrag', 'Nicht negativer Betrag ohne Währung oder Tausendertrennzeichen, z. B. 19,90.'];
$GLOBALS['TL_LANG']['tl_schema_translation']['offerCurrency'] = ['Währung', 'Dreistelliger ISO-Währungscode, z. B. EUR.'];
$GLOBALS['TL_LANG']['tl_schema_translation']['offerUnit'] = ['Abrechnungseinheit', 'Für einen einmaligen Betrag leer lassen.'];
$GLOBALS['TL_LANG']['tl_schema_translation']['offerAvailability'] = ['Verfügbarkeit', 'Nur angeben, wenn die sichtbaren Seiteninhalte diese bestätigen.'];
$GLOBALS['TL_LANG']['tl_schema_translation']['offerDescription'] = ['Angebotsbeschreibung', 'Optionale lokalisierte Preis- oder Anfragebeschreibung passend zum Seiteninhalt.'];
$GLOBALS['TL_LANG']['tl_schema_translation']['offerModes'] = ['' => 'Ohne Angebot', 'exact' => 'Festpreis', 'from' => 'Ab-Preis', 'quote' => 'Auf Anfrage'];
$GLOBALS['TL_LANG']['tl_schema_translation']['offerUnits'] = ['' => 'Einmalig', 'MON' => 'Monat', 'ANN' => 'Jahr', 'HUR' => 'Stunde', 'DAY' => 'Tag'];

$GLOBALS['TL_LANG']['tl_schema_translation']['serviceType'] = ['Leistungsart', 'Lokalisierte Bezeichnung, z. B. Restrukturierungsberatung.'];

$GLOBALS['TL_LANG']['tl_schema_translation']['audienceType'] = ['Zielgruppe', 'Lokalisierte Beschreibung der Zielgruppe.'];

$GLOBALS['TL_LANG']['tl_schema_translation']['catalogName'] = ['Katalogtitel', 'Leer: Name dieser Leistung verwenden.'];
