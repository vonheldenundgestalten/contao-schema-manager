<?php
declare(strict_types=1);
$GLOBALS['TL_LANG']['schema_graph'] = [
    'title'=>'Beziehungen der Entitäten',
    'intro'=>'Entdecken Sie die Verbindungen zwischen Ihren Entitäten. Wählen Sie eine Entität für Beziehungen und sprachabhängige Hauptseiten.',
    'back'=>'Zurück zu den Entitäten',
    'search'=>'Entitäten finden',
    'searchHint'=>'Name, Typ oder Standort',
    'select'=>'Entität auswählen',
    'fit'=>'Alles anzeigen',
    'reset'=>'Ansicht zurücksetzen',
    'isolated'=>'Unverbundene Entitäten',
    'canvas'=>'Interaktiver Beziehungsgraph. Über die Entitätsauswahl sind alle Entitäten und Beziehungen per Tastatur erreichbar.',
    'help'=>'Entitäten lassen sich verschieben. Zoomen und verschieben Sie die Ansicht. Eine Auswahl beschriftet die ein- und ausgehenden Beziehungen.',
    'note'=>'Redaktionelle Ansicht gespeicherter Beziehungen, einschließlich Entwürfen. Gestrichelt: unveröffentlicht. Orange: keine Beziehungen zu Entitäten. Rot: fehlender Datensatz. Pfeile zeigen vom Subjekt zur verbundenen Entität. Veröffentlichungsregeln, Sprache und Seitenkontext beeinflussen die tatsächliche JSON-LD-Ausgabe. Hauptseiten zählen nicht als fachliche Beziehungen.',
    'noJs'=>'Aktivieren Sie JavaScript für die Beziehungsansicht. Die Entitätsliste bleibt verfügbar.',
    'entities'=>'Entitäten',
    'relations'=>'Beziehungen',
    'groups'=>'verbundene Gruppen',
    'published'=>'Veröffentlicht',
    'draft'=>'Unveröffentlicht',
    'missing'=>'Referenzierter Datensatz fehlt',
    'unconnected'=>'Keine Beziehungen zu anderen verwalteten Entitäten. Das kann beabsichtigt sein. Prüfen Sie, ob eine Firma, ein Anbieter oder eine andere Verbindung fehlt.',
    'edit'=>'Entität bearbeiten',
    'homes'=>'Sprachabhängige Hauptseiten',
    'page'=>'Seite',
];

$GLOBALS['TL_LANG']['schema_graph']['intro'] = 'Websites, Seiten, Beiträge und verwaltete Entitäten gemeinsam erkunden. Wählen Sie einen Knoten, um seinen Beziehungen zu folgen.';

$GLOBALS['TL_LANG']['schema_graph']['note'] = 'Redaktionelle Ansicht zugänglicher Datensätze, einschließlich Entwürfen. Gestrichelt: unveröffentlicht. Orange: keine Beziehungen. Rot: fehlender Datensatz. Pfeile zeigen die Richtung einer Beziehung. Veröffentlichungs-, Sprach- und Routingregeln gelten weiterhin. Beiträge ohne Leistungsverknüpfung werden separat geprüft: Autor und Herausgeber zählen nicht als Themen.';

$GLOBALS['TL_LANG']['schema_graph']['editNews'] = 'Nachricht bearbeiten';

$GLOBALS['TL_LANG']['schema_graph']['editPage'] = 'Seiteneinstellungen bearbeiten';

$GLOBALS['TL_LANG']['schema_graph']['withoutService'] = 'Beiträge ohne Leistungsverknüpfung';

$GLOBALS['TL_LANG']['schema_graph']['noService'] = 'Keine direkte about/mentions-Verknüpfung zu einer Service-Entität. Prüfen Sie, ob der Beitrag eine Leistung behandelt. Nicht jeder Beitrag benötigt diese Verknüpfung.';

$GLOBALS['TL_LANG']['schema_graph']['missingIdentity'] = 'Noch keine permanente Schema-Identität konfiguriert.';

$GLOBALS['TL_LANG']['schema_graph']['suppressed'] = 'Die Archiveinstellung unterdrückt die Schema-Ausgabe.';

$GLOBALS['TL_LANG']['schema_graph']['expired'] = 'Die Bewerbungsfrist ist abgelaufen; die JobPosting-Ausgabe ist unterdrückt.';

$GLOBALS['TL_LANG']['schema_graph']['unmappedAuthor'] = 'Core-Autor ohne gemeinsame Person-Identität. Verknüpfen Sie den Backend-Autor oder wählen Sie eine Person in der Nachricht.';

$GLOBALS['TL_LANG']['schema_graph']['language'] = 'Sprache';

$GLOBALS['TL_LANG']['schema_graph']['allLanguages'] = 'Alle Sprachen';

$GLOBALS['TL_LANG']['schema_graph']['layout'] = 'Anordnung';

$GLOBALS['TL_LANG']['schema_graph']['byRelationships'] = 'Nach Beziehungen';

$GLOBALS['TL_LANG']['schema_graph']['byType'] = 'Nach Typ gruppiert';

$GLOBALS['TL_LANG']['schema_graph']['scope'] = 'Noindex-Seiten sind ausgeblendet. Zähler und Beziehungen beziehen sich auf die gewählte Sprache.';
