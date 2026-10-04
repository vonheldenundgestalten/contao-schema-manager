<?php
declare(strict_types=1);
$GLOBALS['TL_LANG']['tl_schema_entity']['identity_legend'] = 'Identität (sprachübergreifend)';
$GLOBALS['TL_LANG']['tl_schema_entity']['facts_legend'] = 'Gemeinsame Angaben';
$GLOBALS['TL_LANG']['tl_schema_entity']['publish_legend'] = 'Veröffentlichung';
$GLOBALS['TL_LANG']['tl_schema_entity']['home_legend'] = 'Sprachabhängige Hauptseite';
$GLOBALS['TL_LANG']['tl_schema_entity']['content_legend'] = 'Übersetzte Inhalte';
$GLOBALS['TL_LANG']['tl_schema_entity']['name'] = ['Name', 'Gemeinsamer öffentlicher Name. Namen von Organisationen und Personen werden nicht übersetzt.'];
$GLOBALS['TL_LANG']['tl_schema_entity']['entityType'] = ['Schema-Typ', 'Bestimmt die angezeigten Felder.'];
$GLOBALS['TL_LANG']['tl_schema_entity']['identityBase'] = ['Domain der Identität', 'Dauerhafte öffentliche Domain, z. B. https://www.vhug.tech. Nicht die Domain des Abnahmeservers verwenden.'];
$GLOBALS['TL_LANG']['tl_schema_entity']['entityId'] = ['Dauerhafte Entitäts-ID', 'Wird beim ersten Speichern erzeugt und bleibt bei Übersetzungen, Seitenumzügen und Typwechseln erhalten.'];
$GLOBALS['TL_LANG']['tl_schema_entity']['legalName'] = ['Rechtlicher Name', 'Verbindlicher rechtlicher Name für alle Sprachen.'];
$GLOBALS['TL_LANG']['tl_schema_entity']['telephone'] = ['Telefon', 'Gemeinsame öffentliche Telefonnummer.'];
$GLOBALS['TL_LANG']['tl_schema_entity']['email'] = ['E-Mail', 'Gemeinsame öffentliche Kontaktadresse.'];
$GLOBALS['TL_LANG']['tl_schema_entity']['streetAddress'] = ['Straße und Hausnummer', 'Gemeinsame Anschrift des Standorts.'];
$GLOBALS['TL_LANG']['tl_schema_entity']['postalCode'] = ['Postleitzahl', ''];
$GLOBALS['TL_LANG']['tl_schema_entity']['addressLocality'] = ['Ort', ''];
$GLOBALS['TL_LANG']['tl_schema_entity']['addressCountry'] = ['Ländercode', 'Zweistelliger Ländercode, z. B. DE.'];
$GLOBALS['TL_LANG']['tl_schema_entity']['organization'] = ['Verknüpfte Organisation', 'Arbeitgeber bei Personen, Anbieter bei Leistungen, Veranstalter bei Events, übergeordnete Organisation; Verkäufer bei Produktangeboten.'];
$GLOBALS['TL_LANG']['tl_schema_entity']['startDate'] = ['Beginn', 'JJJJ-MM-TT oder JJJJ-MM-TTTHH:MM:SS+HH:MM.'];
$GLOBALS['TL_LANG']['tl_schema_entity']['endDate'] = ['Ende', 'JJJJ-MM-TT oder JJJJ-MM-TTTHH:MM:SS+HH:MM.'];
$GLOBALS['TL_LANG']['tl_schema_entity']['locationName'] = ['Veranstaltungsort', 'Name des Veranstaltungsorts.'];
$GLOBALS['TL_LANG']['tl_schema_entity']['eventStatus'] = ['Veranstaltungsstatus', ''];
$GLOBALS['TL_LANG']['tl_schema_entity']['published'] = ['Veröffentlicht', 'Entität und Sprachversion müssen veröffentlicht sein, um die vollständige Beschreibung auszugeben.'];
$GLOBALS['TL_LANG']['tl_schema_entity']['page'] = ['Repräsentative Seite', 'Eine Hauptseite pro Sprache. Die Sprache wird aus dem Startpunkt dieser Seite übernommen.'];
$GLOBALS['TL_LANG']['tl_schema_entity']['language'] = ['Sprache', 'Wird aus der ausgewählten Seite ermittelt.'];
$GLOBALS['TL_LANG']['tl_schema_entity']['description'] = ['Beschreibung', 'Klartext passend zum sichtbaren Inhalt in dieser Sprache.'];
$GLOBALS['TL_LANG']['tl_schema_entity']['jobTitle'] = ['Berufsbezeichnung', 'Übersetzte Funktion dieser Person.'];
$GLOBALS['TL_LANG']['tl_schema_entity']['isMainEntity'] = ['Hauptthema dieser Seite', 'Verknüpft die bestehende WebPage über mainEntity mit dieser Entität.'];

$GLOBALS['TL_LANG']['tl_schema_entity']['links_legend'] = 'Links und Bild';
$GLOBALS['TL_LANG']['tl_schema_entity']['vatID'] = ['Umsatzsteuer-ID', 'Umsatzsteuer-Identifikationsnummer.'];
$GLOBALS['TL_LANG']['tl_schema_entity']['taxID'] = ['Register-/Steuernummer', 'Öffentliche Register- oder Steuernummer.'];
$GLOBALS['TL_LANG']['tl_schema_entity']['sameAs'] = ['Offizielle Profile', 'Eine bestätigte öffentliche Profil-URL pro Zeile.'];
$GLOBALS['TL_LANG']['tl_schema_entity']['image'] = ['Logo / Porträt / Produktbild', 'Vorhandenes öffentliches Bild auswählen.'];
$GLOBALS['TL_LANG']['tl_schema_entity']['sku'] = ['SKU', 'Interne Artikelnummer des Produkts.'];
$GLOBALS['TL_LANG']['tl_schema_entity']['mpn'] = ['MPN', 'Hersteller-Artikelnummer, falls bekannt.'];
$GLOBALS['TL_LANG']['tl_schema_entity']['brand'] = ['Marke', 'Markenname des Produkts, falls bekannt.'];

$GLOBALS['TL_LANG']['tl_schema_entity']['alternateName'] = ['Alternativer Name', 'Gemeinsamer öffentlicher Kurzname.'];

$GLOBALS['TL_LANG']['tl_schema_entity']['foundingDate'] = ['Gründungsdatum', 'JJJJ oder JJJJ-MM-TT; keine unbekannten Tagesdaten erfinden.'];

$GLOBALS['TL_LANG']['tl_schema_entity']['areaServed'] = ['Bediente Länder', 'Gilt für alle Sprachen.'];

$GLOBALS['TL_LANG']['tl_schema_entity']['subservices'] = ['Leistungen im Katalog', 'Wiederverwendbare Service-Entitäten; nur veröffentlichte lokalisierte Ziele werden verknüpft.'];

$GLOBALS['TL_LANG']['tl_schema_entity']['contacts'] = ['Kontaktstellen', 'Kontaktstellen der Organisation bearbeiten'];

$GLOBALS['TL_LANG']['tl_schema_entity']['business_legend'] = 'Unternehmensdaten';

$GLOBALS['TL_LANG']['tl_schema_entity']['relations_legend'] = 'Standorte, Mitgliedschaften und Leistungen';

$GLOBALS['TL_LANG']['tl_schema_entity']['location_legend'] = 'Ort und Erreichbarkeit';

$GLOBALS['TL_LANG']['tl_schema_entity']['addressRegion'] = ['Bundesland / Region', ''];

$GLOBALS['TL_LANG']['tl_schema_entity']['postOfficeBoxNumber'] = ['Postfachnummer', ''];

$GLOBALS['TL_LANG']['tl_schema_entity']['faxNumber'] = ['Fax', ''];

$GLOBALS['TL_LANG']['tl_schema_entity']['numberOfEmployees'] = ['Anzahl Mitarbeitende', 'Ganze Zahl; unbekannte Werte leer lassen.'];

$GLOBALS['TL_LANG']['tl_schema_entity']['latitude'] = ['Breitengrad', 'Dezimalgrad -90 bis 90. Beide Koordinaten angeben.'];

$GLOBALS['TL_LANG']['tl_schema_entity']['longitude'] = ['Längengrad', 'Dezimalgrad -180 bis 180. Beide Koordinaten angeben.'];

$GLOBALS['TL_LANG']['tl_schema_entity']['hasMap'] = ['Kartenlink', ''];

$GLOBALS['TL_LANG']['tl_schema_entity']['openingHours'] = ['Öffnungszeiten', 'Eine Zeitspanne pro Zeile: Mo-Fr 09:00-17:00. Ohne Uhrzeit bedeutet ganztägig.'];

$GLOBALS['TL_LANG']['tl_schema_entity']['externalUrl'] = ['Externe Organisations-Website', 'Für externe Netzwerke/Partner ohne lokale Heimatseite. Lokale Übersetzungen haben Vorrang.'];

$GLOBALS['TL_LANG']['tl_schema_entity']['eventUrl'] = ['Öffentliche Online-Veranstaltungs-URL', 'Öffentlicher Zugangslink für Online- oder Hybrid-Veranstaltungen; keine privaten Zugangstoken.'];

$GLOBALS['TL_LANG']['tl_schema_entity']['priceRange'] = ['Preisspanne', 'Nur für LocalBusiness; nur öffentlich belegte Angaben, z. B. €€€.'];

$GLOBALS['TL_LANG']['tl_schema_entity']['locations'] = ['Zusätzliche Standorte', 'Direkt untergeordnete LocalBusiness-Entitäten werden automatisch verknüpft. Hier weitere sichtbare Standorte wählen.'];

$GLOBALS['TL_LANG']['tl_schema_entity']['memberOf'] = ['Mitglied bei', 'Netzwerke und Verbände; unabhängig von der Muttergesellschaft.'];

$GLOBALS['TL_LANG']['tl_schema_entity']['workLocation'] = ['Arbeitsorte', 'Physische Büros; der Arbeitgeber wird separat gewählt.'];

$GLOBALS['TL_LANG']['tl_schema_entity']['eventAttendanceMode'] = ['Teilnahmeform', ''];

$GLOBALS['TL_LANG']['tl_schema_entity']['subservices'] = ['Leistungskatalog', 'Wiederverwendbare Service-Entitäten mit veröffentlichten lokalisierten Heimatseiten.'];

$GLOBALS['TL_LANG']['tl_schema_entity']['entityId'] = ['Permanente Entitäts-ID', 'Vor dem ersten Speichern optional vorhandene HTTPS-ID eintragen; leer erzeugt eine neue ID. Danach gesperrt.'];
$GLOBALS['TL_LANG']['tl_schema_entity']['attendanceModes'][''] = 'Nicht angegeben';
$GLOBALS['TL_LANG']['tl_schema_entity']['attendanceModes']['OfflineEventAttendanceMode'] = 'Vor Ort';
$GLOBALS['TL_LANG']['tl_schema_entity']['attendanceModes']['OnlineEventAttendanceMode'] = 'Online';
$GLOBALS['TL_LANG']['tl_schema_entity']['attendanceModes']['MixedEventAttendanceMode'] = 'Hybrid: vor Ort und online';

$GLOBALS['TL_LANG']['tl_schema_entity']['relationships'] = ['Beziehungen der Entitäten', 'Entdecken Sie die Verbindungen zwischen Ihren Entitäten. Wählen Sie eine Entität für Beziehungen und sprachabhängige Hauptseiten.'];

$GLOBALS['TL_LANG']['tl_schema_entity']['knowledgeTopics'] = ['Verknüpfte Wissensgebiete', 'Themen auswählen, mit denen sich diese Person oder Organisation auskennt, z. B. Leistungen oder Produkte. Die sprachübergreifenden Verknüpfungen ergänzen die Freitext-Themen in den Übersetzungen. Sie bedeuten keine Leistungserbringung oder Zuständigkeit.'];

$GLOBALS['TL_LANG']['tl_schema_entity']['ai'] = ['KI-Assistent', 'KI-Assistent'];

$GLOBALS['TL_LANG']['tl_schema_entity']['award'] = ['Auszeichnungen', 'Eine öffentliche Auszeichnung pro Zeile, bei Bedarf mit Jahr. Für alle Sprachen gemeinsam; den offiziellen Namen verwenden.'];

$GLOBALS['TL_LANG']['tl_schema_entity']['areaServedWorldwide'] = ["Weltweit tätig", "Gibt Worldwide anstelle der ausgewählten Länder aus. Gilt für alle Sprachen."];

$GLOBALS['TL_LANG']['tl_schema_entity']['registrationIdentifiers'] = ["Registerkennungen", "Registername und Registernummer, ein Paar pro Zeile. Keine Steuernummern."];

$GLOBALS['TL_LANG']['tl_schema_entity']['registerName'] = "Registername";

$GLOBALS['TL_LANG']['tl_schema_entity']['registerNumber'] = "Registernummer";

$GLOBALS['TL_LANG']['tl_schema_entity']['registerName'] = "Standard-Registername";
