<?php
declare(strict_types=1);
$GLOBALS['TL_LANG']['schema_ai']['title'] = 'Schema-KI-Assistent';
$GLOBALS['TL_LANG']['schema_ai']['intro'] = 'Website-Inhalte in Schema-Einträge übernehmen. Jeden Vorschlag vor dem Speichern prüfen.';
$GLOBALS['TL_LANG']['schema_ai']['back'] = 'Zurück zu den Entitäten';
$GLOBALS['TL_LANG']['schema_ai']['keyTitle'] = 'OpenAI-API-Schlüssel';
$GLOBALS['TL_LANG']['schema_ai']['keyMissing'] = 'Kein Schlüssel eingerichtet. Ein eigener Schlüssel aktiviert die Analyse.';
$GLOBALS['TL_LANG']['schema_ai']['keyPresent'] = 'Schlüssel eingerichtet. Der gespeicherte Wert wird nicht angezeigt.';
$GLOBALS['TL_LANG']['schema_ai']['keyHelp'] = 'Wird auf diesem Server in .env.local als SCHEMA_AI_API_KEY gespeichert. Einen eigenen Schlüssel für diese Website verwenden. Keine Modellauswahl oder Budgetbegrenzung.';
$GLOBALS['TL_LANG']['schema_ai']['saveKey'] = 'Schlüssel speichern';
$GLOBALS['TL_LANG']['schema_ai']['replaceKey'] = 'Schlüssel ersetzen';
$GLOBALS['TL_LANG']['schema_ai']['keySaved'] = 'Schlüssel gespeichert. Es wurde keine kostenpflichtige Anfrage gesendet.';
$GLOBALS['TL_LANG']['schema_ai']['keyError'] = 'Schlüssel konnte nicht sicher gespeichert/gelesen werden. Dateirechte prüfen oder SCHEMA_AI_API_KEY serverseitig in .env.local einrichten.';
$GLOBALS['TL_LANG']['schema_ai']['migrate'] = 'Die Contao-Datenbank aktualisieren, um die optionale Analysetabelle anzulegen.';
$GLOBALS['TL_LANG']['schema_ai']['startTitle'] = 'Website analysieren';
$GLOBALS['TL_LANG']['schema_ai']['root'] = 'Website / Sprachstartpunkt';
$GLOBALS['TL_LANG']['schema_ai']['origin'] = 'Öffentlicher Identitätsursprung';
$GLOBALS['TL_LANG']['schema_ai']['originHelp'] = 'Dauerhafter öffentlicher HTTPS-Ursprung, z. B. https://www.beispiel.de. Nicht den Staging-Host verwenden.';
$GLOBALS['TL_LANG']['schema_ai']['discover'] = 'Neue Entitäten analysieren und vorbefüllen';
$GLOBALS['TL_LANG']['schema_ai']['improve'] = 'Schema auf Verbesserungen prüfen';
$GLOBALS['TL_LANG']['schema_ai']['changed'] = 'Nur neue oder geänderte Inhalte seit der letzten erfolgreichen Analyse dieser Art';
$GLOBALS['TL_LANG']['schema_ai']['prepare'] = 'Analyse vorbereiten';
$GLOBALS['TL_LANG']['schema_ai']['disclosure'] = 'Im nächsten Schritt werden öffentliche Texte und bestehende Schema-Daten an OpenAI gesendet. Neue Entitäten und Sprachzuordnungen werden erst nach Prüfung unveröffentlicht angelegt. Bestätigte Änderungen an veröffentlichten Datensätzen ändern die JSON-LD-Ausgabe.';
$GLOBALS['TL_LANG']['schema_ai']['coverage'] = 'Quellen: öffentlich erreichbares HTML, ersatzweise veröffentlichte Contao-Seiten-, Artikel-, Inhaltselement- und Nachrichtentexte. Noindex-, geschützte und unveröffentlichte Inhalte sind ausgeschlossen. JavaScript-Inhalte, Dateien und externe Recherche werden nicht erfasst. Die Quellenliste zeigt den Umfang.';
$GLOBALS['TL_LANG']['schema_ai']['recent'] = 'Letzte Analysen';
$GLOBALS['TL_LANG']['schema_ai']['run'] = 'Analyse';
$GLOBALS['TL_LANG']['schema_ai']['status'] = 'Status';
$GLOBALS['TL_LANG']['schema_ai']['sources'] = 'Quellen';
$GLOBALS['TL_LANG']['schema_ai']['remaining'] = 'Verbleibend';
$GLOBALS['TL_LANG']['schema_ai']['continue'] = 'Verbleibende Quellen analysieren';
$GLOBALS['TL_LANG']['schema_ai']['stop'] = 'Nach aktuellem Schritt stoppen';
$GLOBALS['TL_LANG']['schema_ai']['progress'] = 'Quellen werden analysiert …';
$GLOBALS['TL_LANG']['schema_ai']['review'] = 'Vorschläge prüfen';
$GLOBALS['TL_LANG']['schema_ai']['selectAll'] = 'Alle Vorschläge auswählen';
$GLOBALS['TL_LANG']['schema_ai']['clear'] = 'Auswahl aufheben';
$GLOBALS['TL_LANG']['schema_ai']['selectHelp'] = '1. Eintrag öffnen. 2. Gewünschte Änderungen auswählen; bei Unsicherheit die Quelle prüfen. 3. Auswahl übernehmen. Benötigte Entwurfs- und Seitenschritte werden gemeinsam ausgewählt.';
$GLOBALS['TL_LANG']['schema_ai']['apply'] = 'Ausgewählte Änderungen anwenden';
$GLOBALS['TL_LANG']['schema_ai']['reject'] = 'Ausgewählte Vorschläge ablehnen';
$GLOBALS['TL_LANG']['schema_ai']['applyHelp'] = 'Neue Einträge bleiben unveröffentlicht. Änderungen an veröffentlichten Einträgen sind nach dem Übernehmen live. Ablehnen verwirft ausgewählte Vorschläge; ohne Auswahl bleiben sie für später erhalten.';
$GLOBALS['TL_LANG']['schema_ai']['applied'] = '%s Vorschläge angewendet. Neue Datensätze bleiben unveröffentlicht.';
$GLOBALS['TL_LANG']['schema_ai']['rejected'] = 'Vorschläge abgelehnt. Identische Belege erzeugen diese Vorschläge nicht erneut.';
$GLOBALS['TL_LANG']['schema_ai']['selectSome'] = 'Mindestens einen offenen Vorschlag auswählen.';
$GLOBALS['TL_LANG']['schema_ai']['empty'] = 'Noch keine Vorschläge. Quellen analysieren oder bei abgeschlossener Analyse die Hinweise prüfen.';
$GLOBALS['TL_LANG']['schema_ai']['current'] = 'Aktuell';
$GLOBALS['TL_LANG']['schema_ai']['proposed'] = 'Vorgeschlagener Wert';
$GLOBALS['TL_LANG']['schema_ai']['evidence'] = 'Beleg';
$GLOBALS['TL_LANG']['schema_ai']['reason'] = 'Warum diese Änderung';
$GLOBALS['TL_LANG']['schema_ai']['pending'] = 'offen';
$GLOBALS['TL_LANG']['schema_ai']['invalid'] = 'ungültig';
$GLOBALS['TL_LANG']['schema_ai']['appliedState'] = 'angewendet';
$GLOBALS['TL_LANG']['schema_ai']['rejectedState'] = 'abgelehnt';
$GLOBALS['TL_LANG']['schema_ai']['error'] = 'Vorgang fehlgeschlagen. Es wurden keine neuen Schema-Änderungen angewendet.';
$GLOBALS['TL_LANG']['schema_ai']['usage'] = 'Gemeldeter Verbrauch';
$GLOBALS['TL_LANG']['schema_ai']['input'] = 'Eingabetoken';
$GLOBALS['TL_LANG']['schema_ai']['output'] = 'Ausgabetoken (inklusive Reasoning)';
$GLOBALS['TL_LANG']['schema_ai']['cost'] = 'Ungefähre Standard-API-Kosten ohne Steuern und Zuschläge';
$GLOBALS['TL_LANG']['schema_ai']['noAutomatic'] = 'Die Bestandsaufnahme sendet keine API-Anfrage. Die Analyse startet ausdrücklich im nächsten Schritt.';
$GLOBALS['TL_LANG']['schema_ai']['ready'] = 'bereit';
$GLOBALS['TL_LANG']['schema_ai']['working'] = 'läuft';
$GLOBALS['TL_LANG']['schema_ai']['paused'] = 'pausiert';
$GLOBALS['TL_LANG']['schema_ai']['complete'] = 'abgeschlossen';

$GLOBALS['TL_LANG']['schema_ai']['newEntity'] = 'Neue Entität';
$GLOBALS['TL_LANG']['schema_ai']['home'] = 'Seite für diese Sprache';

$GLOBALS['TL_LANG']['schema_ai']['newAnalysis'] = 'Weitere Analyse starten';

$GLOBALS['TL_LANG']['schema_ai']['analysisDetails'] = 'Quellen und API-Nutzung';

$GLOBALS['TL_LANG']['schema_ai']['createAction'] = 'Entwurf erstellen';

$GLOBALS['TL_LANG']['schema_ai']['homeAction'] = 'Seite zuordnen';

$GLOBALS['TL_LANG']['schema_ai']['linkAction'] = 'Verknüpfung ergänzen';

$GLOBALS['TL_LANG']['schema_ai']['fillAction'] = 'Leeres Feld ergänzen';

$GLOBALS['TL_LANG']['schema_ai']['replaceAction'] = 'Vorhandenen Wert ersetzen';

$GLOBALS['TL_LANG']['schema_ai']['selectedCount'] = 'Änderungen ausgewählt';

$GLOBALS['TL_LANG']['schema_ai']['toReview'] = 'zu prüfen';

$GLOBALS['TL_LANG']['schema_ai']['editSuggestion'] = 'Vorgeschlagenen Text bearbeiten';

$GLOBALS['TL_LANG']['schema_ai']['draftHint'] = 'Erstellt einen unveröffentlichten Schema-Eintrag. Nach der Prüfung im Schema Manager veröffentlichen.';

$GLOBALS['TL_LANG']['schema_ai']['homeHint'] = 'Diese Seite repräsentiert den Eintrag in dieser Sprache. Die Zuordnung bleibt zunächst unveröffentlicht.';

$GLOBALS['TL_LANG']['schema_ai']['newGroup'] = 'Neu:';

$GLOBALS['TL_LANG']['schema_ai']['updateGroup'] = 'Ergänzen:';

$GLOBALS['TL_LANG']['schema_ai']['editorReview'] = 'Redaktionelle Einschätzung';

$GLOBALS['TL_LANG']['schema_ai']['feedbackTitle'] = 'Vorschläge besprechen und verfeinern';

$GLOBALS['TL_LANG']['schema_ai']['feedbackHelp'] = 'Beschreiben Sie, was wichtig ist, zu vage bleibt oder welche Verknüpfungen fehlen. Der Helfer erklärt seine Entscheidungen und erstellt eine überarbeitete Auswahl zur Prüfung.';

$GLOBALS['TL_LANG']['schema_ai']['feedbackLabel'] = 'Ihr Feedback oder Ihre Frage';

$GLOBALS['TL_LANG']['schema_ai']['feedbackExample'] = 'Entwicklung soll eine Leistung bleiben. AI Label ist eine Contao-Erweiterung. Welche Beziehungen können wir abbilden, welche fehlen?';

$GLOBALS['TL_LANG']['schema_ai']['feedbackDisclosure'] = 'Sendet Feedback, gespeicherte öffentliche Quellen und Schema-Kontext an OpenAI (API-Kosten fallen an). Erstellt eine separate Prüfung und übernimmt keine Änderungen. Nur die gewünschte Version übernehmen. Ungespeicherte Textänderungen und Checkbox-Auswahlen werden nicht mitgesendet.';

$GLOBALS['TL_LANG']['schema_ai']['feedbackSend'] = 'Feedback senden und Vorschläge überarbeiten';

$GLOBALS['TL_LANG']['schema_ai']['feedbackWorking'] = 'Überarbeitete Vorschläge werden erstellt …';

$GLOBALS['TL_LANG']['schema_ai']['feedbackWait'] = 'Analyse abschließen, um die Vorschläge zu besprechen.';

$GLOBALS['TL_LANG']['schema_ai']['feedbackApplied'] = 'Einige Vorschläge wurden bereits übernommen. Für das aktualisierte Schema eine neue Analyse starten.';

$GLOBALS['TL_LANG']['schema_ai']['alternativeReview'] = 'Dies ist eine überarbeitete Auswahl. Es wurden keine Änderungen übernommen.';

$GLOBALS['TL_LANG']['schema_ai']['originalReview'] = 'Ursprüngliche Analyse';

$GLOBALS['TL_LANG']['schema_ai']['conversation'] = 'Bisheriges Gespräch';

$GLOBALS['TL_LANG']['schema_ai']['you'] = 'Sie';

$GLOBALS['TL_LANG']['schema_ai']['assistant'] = 'Schema-Helfer';

$GLOBALS['TL_LANG']['schema_ai']['controlsLoading'] = 'Analyse-Steuerung wird geladen. Falls diese Meldung bleibt, die Seite vor dem Start neu laden.';

$GLOBALS['TL_LANG']['schema_ai']['lastUpdated'] = 'Aktualisiert';

$GLOBALS['TL_LANG']['schema_ai']['viewingAnalysis'] = 'Geöffnet';

$GLOBALS['TL_LANG']['schema_ai']['openAnalysis'] = 'Analyse öffnen';

$GLOBALS['TL_LANG']['schema_ai']['multilingual'] = 'Weitere Sprachen dieser Website einbeziehen';
$GLOBALS['TL_LANG']['schema_ai']['multilingualHelp'] = 'Analysiert veröffentlichte Sprachwurzeln derselben Domain und übersetzt neue Einträge anhand verknüpfter Seiten. Bestehende Übersetzungen bleiben unverändert. Mehrsprachige Analysen beziehen alle Quellen ein, auch bei Auswahl von „nur geändert“.';

$GLOBALS['TL_LANG']['schema_ai']['localizing'] = 'Fehlende Spracheinträge werden übersetzt …';

$GLOBALS['TL_LANG']['schema_ai']['waitingBatch'] = 'Warten auf den laufenden Verarbeitungsschritt …';

$GLOBALS['TL_LANG']['schema_ai']['stages'] = 'Einrichtungsschritte';
$GLOBALS['TL_LANG']['schema_ai']['stage_foundation'] = '2 · Organisation';
$GLOBALS['TL_LANG']['schema_ai']['stage_foundation_help'] = 'Betreiber und Sprachversionen anlegen oder verbessern. Übernommene Entwürfe vor der Inhaltsanreicherung prüfen und veröffentlichen.';
$GLOBALS['TL_LANG']['schema_ai']['stage_configuration'] = '3 · Websites und Archive';
$GLOBALS['TL_LANG']['schema_ai']['stage_configuration_help'] = 'Website-Herausgeber und Archivvorgaben vor einzelnen Beiträgen prüfen. Keine KI-Anfrage erforderlich.';
$GLOBALS['TL_LANG']['schema_ai']['stage_content'] = '4 · Inhalte anreichern';
$GLOBALS['TL_LANG']['schema_ai']['stage_content_help'] = 'Neue Themen finden oder bestehende Leistungen, Personen, Produkte und Nachrichten auf Basis der geprüften Grundlagen verbessern.';
$GLOBALS['TL_LANG']['schema_ai']['reviewParents'] = 'Übergeordnete Einstellungen prüfen';
$GLOBALS['TL_LANG']['schema_ai']['parentReviewHelp'] = 'Einstellungen prüfen und gewünschte Änderungen auswählen. Archivtypen bleiben ohne Ihre Auswahl unverändert. Entwürfe können als Herausgeber zugewiesen werden, erscheinen aber erst nach Veröffentlichung im Schema. Diese Vorgaben gelten für bestehende und zukünftige Inhalte; es entstehen keine Archiv-Entitäten.';
$GLOBALS['TL_LANG']['schema_ai']['websiteImpact'] = 'Legt Herausgeber oder öffentlichen Website-Namen für diesen Sprachstartpunkt fest. Bestehende Website-Identitäten bleiben erhalten.';
$GLOBALS['TL_LANG']['schema_ai']['archiveImpact'] = 'Bestimmt Schema-Typ und Herausgeber aller Einträge dieses Archivs. BlogPosting für Blogs, NewsArticle für Nachrichten, Article für allgemeine redaktionelle Inhalte wie Fallstudien. Ohne Typänderung den Contao-Standard beibehalten. JobPosting benötigt zusätzliche Stellenfelder; Unterdrücken entfernt das Nachrichtenschema.';
$GLOBALS['TL_LANG']['schema_ai']['calendarDeferred'] = 'Kalender erkannt. Schema-Anreicherung und Vorgaben für Kalender-Termine sind noch nicht implementiert. Diese Prüfung ändert keine Kalender.';
$GLOBALS['TL_LANG']['schema_ai']['archiveGroup'] = 'Nachrichtenarchiv-Einstellungen';
$GLOBALS['TL_LANG']['schema_ai']['noPublisher'] = 'Kein Herausgeber zugewiesen';
$GLOBALS['TL_LANG']['schema_ai']['coreType'] = 'Contao-Standard (NewsArticle)';
$GLOBALS['TL_LANG']['schema_ai']['suppressType'] = 'Nachrichtenschema unterdrücken';
$GLOBALS['TL_LANG']['schema_ai']['draftState'] = 'unveröffentlichter Entwurf';
$GLOBALS['TL_LANG']['schema_ai']['reviewSetting'] = 'Prüfen';
$GLOBALS['TL_LANG']['schema_ai']['effect'] = 'Auswirkung auf die Website';
$GLOBALS['TL_LANG']['schema_ai']['reviewValue'] = 'Zu prüfende Einstellung';
$GLOBALS['TL_LANG']['schema_ai']['parentSummary'] = 'Konfigurationsprüfung · keine API-Nutzung · keine neuen Einträge';
$GLOBALS['TL_LANG']['schema_ai']['parentSelectionHelp'] = 'Website- und Archiveinstellungen prüfen. Gewünschte Felder ändern, auswählen und übernehmen. Bereits passende Einstellungen nicht auswählen.';
$GLOBALS['TL_LANG']['schema_ai']['parentApplyHelp'] = 'Ausgewählte Einstellungen gelten für bestehende und zukünftige Inhalte. Herausgeber-Entwürfe bleiben unveröffentlicht. Archivtypen wählen Sie redaktionell aus, nicht die KI.';
$GLOBALS['TL_LANG']['schema_ai']['foundationDraftWarning'] = 'Geprüfte Organisation und ihre Sprachversionen vor der Inhaltsanreicherung veröffentlichen. Bestehende Entwürfe sind gegen Duplikate reserviert, dienen aber nicht als aktive Inhalte.';
$GLOBALS['TL_LANG']['schema_ai']['calendarGroup'] = 'Kalendereinstellungen';
$GLOBALS['TL_LANG']['schema_ai']['enrichEvents'] = 'Event-Schema anreichern';
$GLOBALS['TL_LANG']['schema_ai']['calendarImpact'] = 'Kalendervorgaben gelten für alle Termine. Eigene Termin-Angaben haben Vorrang. Datum und Inhalte stammen weiterhin aus Contao; es entsteht keine Kalender-Entität.';
$GLOBALS['TL_LANG']['schema_ai']['coreEventType'] = 'Contao-Standard (Event)';

$GLOBALS['TL_LANG']['schema_ai']['authorGroup'] = 'Autorenzuordnung';

$GLOBALS['TL_LANG']['schema_ai']['authorGroup'] = 'Autorenzuordnung';

$GLOBALS['TL_LANG']['schema_ai']['auditTitle'] = 'Vorhandene strukturierte Daten';

$GLOBALS['TL_LANG']['schema_ai']['auditEntry'] = 'Vor der Einrichtung vorhandenes Markup prüfen oder die Migrationsprüfung wiederholen. Ohne API-Schlüssel oder KI-Kosten.';

$GLOBALS['TL_LANG']['schema_ai']['auditHelp'] = 'Liest öffentliches JSON-LD und vergleicht es mit der veröffentlichten Schema-Manager-Ausgabe. Bestehende Entitäten werden nicht geändert. Nur reine Schema-HTML-Elemente mit geprüftem Ersatz können deaktiviert werden. Unbekannte Quellen und Unterschiede müssen manuell geprüft werden.';

$GLOBALS['TL_LANG']['schema_ai']['draftHome'] = '„%s“ ist veröffentlicht, aber die Sprachversion/Startseite (%s) noch nicht. Ihre lokalisierten Angaben werden nicht ausgegeben.';

$GLOBALS['TL_LANG']['schema_ai']['preserveHelp'] = 'Alle auswählen markiert nur Ergänzungen und Vorschläge für leere Felder. Änderungen bestehender Werte müssen nach dem Vergleich einzeln ausgewählt werden.';

$GLOBALS['TL_LANG']['schema_ai']['auditPrepare'] = 'Prüfung vorbereiten';

$GLOBALS['TL_LANG']['schema_ai']['auditScan'] = 'Öffentliche Seiten prüfen';

$GLOBALS['TL_LANG']['schema_ai']['auditRunning'] = 'Seiten werden geprüft';

$GLOBALS['TL_LANG']['schema_ai']['auditNodes'] = 'Schema-Knoten';

$GLOBALS['TL_LANG']['schema_ai']['auditManaged'] = 'Schema-Manager-Identität';

$GLOBALS['TL_LANG']['schema_ai']['auditAdditional'] = 'Zusätzliches/Core-Markup – Herkunft prüfen';

$GLOBALS['TL_LANG']['schema_ai']['auditReplacement'] = 'Möglicher Ersatz; fehlende oder abweichende Eigenschaften:';

$GLOBALS['TL_LANG']['schema_ai']['auditCovered'] = 'Verglichene Eigenschaften bleiben erhalten.';

$GLOBALS['TL_LANG']['schema_ai']['auditUnmapped'] = 'Kein exaktes HTML-Inhaltselement als Quelle gefunden. Mögliche Quelle: Contao, Template, Erweiterung oder dynamische Ausgabe.';

$GLOBALS['TL_LANG']['schema_ai']['auditElement'] = 'HTML-Inhaltselement';

$GLOBALS['TL_LANG']['schema_ai']['auditEdit'] = 'Editor öffnen';

$GLOBALS['TL_LANG']['schema_ai']['auditDisabled'] = 'Durch diese Prüfung deaktiviert.';

$GLOBALS['TL_LANG']['schema_ai']['auditSelect'] = 'Dieses geprüfte alte Schema-Element deaktivieren';

$GLOBALS['TL_LANG']['schema_ai']['auditRetireHelp'] = 'Nichts wird automatisch ausgewählt. Seite und Ersatz werden beim Übernehmen erneut geprüft. Inhalte werden deaktiviert, nicht gelöscht und können im Editor wieder aktiviert werden.';

$GLOBALS['TL_LANG']['schema_ai']['auditRetire'] = 'Ausgewählte alte Elemente deaktivieren';

$GLOBALS['TL_LANG']['schema_ai']['auditRetired'] = '%d alte Elemente deaktiviert.';

$GLOBALS['TL_LANG']['schema_ai']['auditData'] = 'Vorhandene Daten und Ersatz ansehen';

$GLOBALS['TL_LANG']['schema_ai']['stage_import'] = '1. Vorhandenes Schema importieren';

$GLOBALS['TL_LANG']['schema_ai']['stage_import_help'] = 'Vorhandene Identitäten erhalten, bevor Neues entsteht.';

$GLOBALS['TL_LANG']['schema_ai']['importHelp'] = 'Hier beginnen, wenn die Website bereits manuelles JSON-LD enthält. Wiederholte Definitionen werden zusammengeführt, IDs erhalten, unterstützte Felder zugeordnet und zusätzliche Eigenschaften bewahrt. Automatische Contao-Seiten, Bilder und Artikel erscheinen nicht als Import-Entitäten. Ohne KI.';

$GLOBALS['TL_LANG']['schema_ai']['importPrepare'] = 'Importprüfung vorbereiten';

$GLOBALS['TL_LANG']['schema_ai']['importScan'] = 'Vorhandene Entitäten suchen';


$GLOBALS['TL_LANG']['schema_ai']['import_pending'] = 'Zur Prüfung';

$GLOBALS['TL_LANG']['schema_ai']['import_imported'] = 'Importierter Entwurf';

$GLOBALS['TL_LANG']['schema_ai']['import_published'] = 'Veröffentlicht';

$GLOBALS['TL_LANG']['schema_ai']['importSelect'] = 'Diese Entität auswählen';

$GLOBALS['TL_LANG']['schema_ai']['importMerge'] = 'Lücken in bestehender Entität ergänzen';

$GLOBALS['TL_LANG']['schema_ai']['importIds'] = 'Öffentliche IDs bleiben erhalten';

$GLOBALS['TL_LANG']['schema_ai']['importNoHome'] = 'Startseite prüfen';

$GLOBALS['TL_LANG']['schema_ai']['importRetained'] = 'Erhaltene IDs und zusätzliche Eigenschaften';

$GLOBALS['TL_LANG']['schema_ai']['importApply'] = 'Auswahl als Entwürfe importieren';

$GLOBALS['TL_LANG']['schema_ai']['importPublish'] = 'Ausgewählte Importe mit Sprachversionen veröffentlichen';

$GLOBALS['TL_LANG']['schema_ai']['importPublishHelp'] = 'Zuerst ausgewählte neue Einträge als Entwürfe importieren. Prüfen, danach importierte Einträge auswählen und gemeinsam mit ihren Sprachversionen veröffentlichen. Bestehende Angaben und Identitäten werden nicht stillschweigend ersetzt.';

$GLOBALS['TL_LANG']['schema_ai']['importApplied'] = '%d Einträge importiert. Entwürfe vor Veröffentlichung prüfen.';

$GLOBALS['TL_LANG']['schema_ai']['importPublished'] = '%d Importe mit Sprachversionen veröffentlicht.';




$GLOBALS['TL_LANG']['schema_ai']['importUnknownOrigin'] = 'Keine lokale HTML-Quelle gefunden; Template oder Erweiterung manuell prüfen.';

$GLOBALS['TL_LANG']['schema_ai']['importNext'] = 'Weiter: Grundlage ergänzen';

$GLOBALS['TL_LANG']['schema_ai']['import_conflict'] = 'Klärung erforderlich';

$GLOBALS['TL_LANG']['schema_ai']['importWebsiteIdentity'] = "Beim Veröffentlichen dieses Imports ersetzt die ursprüngliche ID aus dem handgeschriebenen Schema die konfigurierte Website-ID. Vom Schema Manager erzeugte Website-Verweise verwenden dann die ursprüngliche ID. Verweise in individuellem Code müssen separat geprüft werden. Der Entwurfsimport ändert noch keine öffentliche Ausgabe.";

$GLOBALS['TL_LANG']['schema_ai']['importCurrentIdentity'] = "Aktuell konfigurierte Website-ID";

$GLOBALS['TL_LANG']['schema_ai']['importOriginalIdentity'] = "Wiederherzustellende ursprüngliche ID";

$GLOBALS['TL_LANG']['schema_ai']['removeLinkAction'] = 'Defekten Verweis entfernen';
$GLOBALS['TL_LANG']['schema_ai']['storedRelationship'] = 'Gespeicherte Beziehung';







$GLOBALS['TL_LANG']['schema_ai']['importEmpty'] = 'Keine neuen Einträge zum Importieren. Beachten Sie die Scan-Hinweise und fahren Sie fort. Bisheriges manuelles Schema deaktivieren Sie bei Bedarf selbst im Inhaltselement.';
