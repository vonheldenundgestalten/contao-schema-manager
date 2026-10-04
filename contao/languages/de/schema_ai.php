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
$GLOBALS['TL_LANG']['schema_ai']['progress'] = 'Ein kleines Quellenpaket wird analysiert…';
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
