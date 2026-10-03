<?php
declare(strict_types=1);
// Run from the installed Contao application's root; all fixture writes roll back.
require getcwd().'/vendor/autoload.php';
$kernel = Contao\ManagerBundle\HttpKernel\ContaoKernel::fromInput(getcwd(), new Symfony\Component\Console\Input\ArgvInput());
if (getenv('SCHEMA_TEST_DB_TCP') === '1') {
    foreach (['_SERVER', '_ENV'] as $scope) {
        if (isset($GLOBALS[$scope]['DATABASE_URL'])) {
            $GLOBALS[$scope]['DATABASE_URL'] = str_replace('@localhost', '@127.0.0.1', $GLOBALS[$scope]['DATABASE_URL']);
        }
    }
    if (isset($_SERVER['DATABASE_URL'])) { putenv('DATABASE_URL='.$_SERVER['DATABASE_URL']); }
}
$kernel->boot();
$c = $kernel->getContainer();
$c->get('contao.framework')->initialize();
$db = $c->get('database_connection');
$pages = [];
foreach ($db->fetchFirstColumn("SELECT id FROM tl_page WHERE type = 'regular' AND published = '1' ORDER BY id") as $id) {
    $page = Contao\PageModel::findById($id); $page->loadDetails();
    if (!$page->protected && in_array($page->language, ['de', 'en'], true)) { $pages[$page->language] ??= $page; }
}
if (count($pages) !== 2) { throw new RuntimeException('Integration fixture needs published DE and EN pages.'); }
$check = static function (bool $ok, string $message): void { if (!$ok) { throw new RuntimeException($message); } };
$entities = new VHUG\SchemaManagerBundle\Schema\EntityGraph(
    $db, $c->get('contao.routing.content_url_generator'), $c->get('contao.cache.tag_manager'),
    new VHUG\SchemaManagerBundle\Schema\EntityMapper(),
    $c->get('request_stack')
);
$news = new VHUG\SchemaManagerBundle\Schema\NewsGraph($db, $entities, $c->get('contao.routing.content_url_generator'), $c->get('contao.string.html_decoder'), $c->get('contao.cache.tag_manager'));
$listener = new VHUG\SchemaManagerBundle\EventListener\JsonLdListener(
    $db, $c->get('request_stack'), $c->get('contao.routing.content_url_generator'),
    $c->get('contao.cache.tag_manager'), $entities, $news
);
$db->beginTransaction();
try {
    $add = static function (string $table, array $data) use ($db): int {
        $db->insert($table, $data + ['tstamp' => time()]);
        return (int) $db->lastInsertId();
    };
    $token = bin2hex(random_bytes(6));
    $orgId = 'https://example.org/#integration-org-'.$token;
    $personId = 'https://example.org/#integration-person-'.$token;
    $productId = 'https://example.org/#integration-product-'.$token;
    $org = $add('tl_schema_entity', ['entityType' => 'Organization', 'name' => 'Fixture Company', 'legalName' => 'Fixture GmbH', 'identityBase' => 'https://example.org', 'entityId' => $orgId, 'published' => '1']);
    $person = $add('tl_schema_entity', ['entityType' => 'Person', 'name' => 'Fixture Person', 'identityBase' => 'https://example.org', 'entityId' => $personId, 'organization' => $org, 'published' => '1']);
    $product = $add('tl_schema_entity', ['entityType' => 'Product', 'name' => 'Manual product', 'sku' => 'TEST-1', 'identityBase' => 'https://example.org', 'entityId' => $productId, 'organization' => $org, 'published' => '1']);
    foreach ($pages as $locale => $page) {
        foreach ([$org, $person, $product] as $entity) {
            $add('tl_schema_translation', ['pid' => $entity, 'page' => (int) $page->id, 'language' => $locale, 'description' => 'Description '.$locale, 'offerMode' => $entity === $product ? 'exact' : '', 'offerPrice' => '19.90', 'offerCurrency' => 'EUR', 'published' => '1', 'isMainEntity' => $entity === $person ? '1' : '']);
        }
    }
    $render = static function ($page) use ($c, $listener): array {
        $request = Symfony\Component\HttpFoundation\Request::create((getenv('SCHEMA_TEST_ORIGIN') ?: 'https://example.test/'));
        $request->attributes->set('pageModel', $page);
        $c->get('request_stack')->push($request);
        try {
            $context = new Contao\CoreBundle\Routing\ResponseContext\ResponseContext();
            $manager = new Contao\CoreBundle\Routing\ResponseContext\JsonLd\JsonLdManager($context);
            $context->add($manager);
            $graph = $manager->getGraphForSchema($manager::SCHEMA_ORG);
            $graph->add((new Spatie\SchemaOrg\WebPage())->name('Core page metadata'));
            $event = new Contao\CoreBundle\Event\JsonLdEvent();
            $event->setResponseContext($context);
            $listener($event);
            return $graph->toArray()['@graph'];
        } finally { $c->get('request_stack')->pop(); }
    };
    $byId = static function (array $nodes, string $id): array {
        foreach ($nodes as $node) { if (($node['@id'] ?? '') === $id) { return $node; } }
        return [];
    };
    $contact = $add('tl_schema_contact', ['pid'=>$org, 'contactType'=>'sales', 'telephone'=>'+49 123', 'availableLanguage'=>'de, en', 'areaServed'=>serialize(['DE','AT']), 'published'=>'1']);
    $parentUri='https://example.org/#service-'.$token;
    $childUri='https://example.org/#subservice-'.$token;
    $child=$add('tl_schema_entity',['name'=>'Child service','entityType'=>'Service','entityId'=>$childUri,'identityBase'=>'https://example.org','published'=>'1']);
    $parent=$add('tl_schema_entity',['name'=>'Parent service','entityType'=>'Service','entityId'=>$parentUri,'identityBase'=>'https://example.org','areaServed'=>serialize(['DE']),'subservices'=>serialize([$child]),'published'=>'1']);
    foreach ($pages as $locale=>$page) {
        foreach ([$parent,$child] as $service) {
            $add('tl_schema_translation',['pid'=>$service,'page'=>(int)$page->id,'language'=>$locale,'name'=>'Service '.$locale,'serviceType'=>'Consulting '.$locale,'audienceType'=>'Audience '.$locale,'catalogName'=>'Catalogue '.$locale,'published'=>'1']);
        }
    }
    $de = $render($pages['de']); $en = $render($pages['en']);
    $service=$byId($en,$parentUri);
    $check($service['hasOfferCatalog']['itemListElement'][0]['itemOffered']===['@id'=>$childUri] && $service['hasOfferCatalog']['name']==='Catalogue en','Localized catalogue references reusable service');
    $check($service['areaServed']===['DE'] && $service['audience']['audienceType']==='Audience en','Service audience and territory');
    $check(count(array_filter($en,static fn($n)=>($n['@id']??'')===$childUri))===1,'Referenced service emitted once');
    $contactNode=$byId($en,$orgId)['contactPoint'][0];
    $check($contactNode['availableLanguage']===['de','en'] && $contactNode['areaServed']===['DE','AT'],'Structured contact point survives serialization');
    $db->update('tl_schema_contact',['published'=>''],['id'=>$contact]);
    $check(!isset($byId($render($pages['en']),$orgId)['contactPoint']),'Unpublished contact omitted');
    $db->update('tl_schema_translation',['published'=>''],['pid'=>$child,'language'=>'en']);
    $check(!isset($byId($render($pages['en']),$parentUri)['hasOfferCatalog']),'Unpublished translated service not linked');
    $validation=new VHUG\SchemaManagerBundle\EventListener\EntityDetailsListener($db);
    $dc=new class($child) extends Contao\DataContainer { public function __construct(int $id) { $this->intId=$id; } public function getPalette() { return ""; } protected function save($value) {} };
    try { $validation->subservices(serialize([$parent]),$dc); throw new LogicException('Cycle accepted'); } catch (InvalidArgumentException $expected) {}
    $contactDc=clone $dc; $contactDc->id=$contact;
    $check($validation->publishContact('1',$contactDc)==='1','Contact with phone can publish');
    $db->update('tl_schema_contact',['telephone'=>'','email'=>''],['id'=>$contact]);
    try { $validation->publishContact('1',$contactDc); throw new LogicException('Empty contact published'); } catch (InvalidArgumentException $expected) {}
    $check($validation->languages('de, en, de')==='de, en','Contact language normalization');
    try { $validation->languages('not a language'); throw new LogicException('Invalid language accepted'); } catch (InvalidArgumentException $expected) {}
    $check($validation->foundingDate('1998')==='1998' && $validation->foundingDate('2024-02-29')==='2024-02-29','Founding precision preserved');
    try { $validation->foundingDate('2025-02-29'); throw new LogicException('Invalid date accepted'); } catch (InvalidArgumentException $expected) {}
    $manualProduct = $byId($en, $productId);
    $check($manualProduct['@type'] === 'Product' && $manualProduct['sku'] === 'TEST-1' && $manualProduct['offers']['itemOffered']['@id'] === $productId && $manualProduct['offers']['priceSpecification']['price'] === '19.90', 'Manual Product and Offer survive real Contao graph serialization');
    $pde = $byId($de, $personId); $pen = $byId($en, $personId);
    $check($pde['@id'] === $pen['@id'], 'One identity across languages');
    $check($pde['description'] === 'Description de' && $pen['description'] === 'Description en', 'Localized descriptions');
    $check($pde['url'] !== $pen['url'], 'Contao generates different localized home URLs');
    $check($pde['worksFor'] === ['@id' => $orgId], 'Relationship references organization');
    $check(count(array_filter($de, static fn ($n) => ($n['@id'] ?? '') === $orgId)) === 1, 'Organization is not duplicated');
    $web = array_values(array_filter($de, static fn ($n) => $n['@type'] === 'WebPage'))[0];
    $check($web['name'] === 'Core page metadata' && in_array(['@id' => $personId], isset($web['mainEntity']['@id']) ? [$web['mainEntity']] : $web['mainEntity'], true), 'Core WebPage preserved and enriched');
    $check($byId($en, $orgId)['legalName'] === 'Fixture GmbH' && $byId($de, $orgId)['description'] === 'Description de', 'Full organization on both localized homes');
    $other = null;
    foreach ($db->fetchFirstColumn("SELECT id FROM tl_page WHERE type='regular' AND published='1' AND requireItem='' ORDER BY id") as $candidateId) {
        $candidate = Contao\PageModel::findById($candidateId); $candidate->loadDetails();
        if ($candidate->language === 'en' && !$candidate->protected && (int) $candidate->id !== (int) $pages['en']->id) { $other = clone $candidate; break; }
    }
    if (!$other) { throw new RuntimeException('Need another public EN page for supporting organization checks.'); }
    $other->schemaEntities = serialize([$person, $org]); // Provider is encountered before explicit reference.
    $compact = $byId($render($other), $orgId);
    $check(array_keys($compact) === ['@type', '@id', 'name', 'url'] && $compact['name'] === 'Fixture Company', 'Supporting organization has compact identity fields');
    $check($compact['url'] === $byId($en, $orgId)['url'], 'Compact node points to localized home');
    $other->schemaEntities = serialize([$org, $person]);
    $check($byId($render($other), $orgId) === $compact, 'Reference encounter order does not change output');
    $db->update('tl_schema_entity', ['entityType' => 'LocalBusiness'], ['id' => $org]);
    $check($byId($render($other), $orgId)['@type'] === 'LocalBusiness' && isset($byId($render($pages['en']), $orgId)['legalName']), 'LocalBusiness type retained and home stays full');
    $db->update('tl_schema_translation', ['published' => ''], ['pid' => $org, 'language' => 'en']);
    $missingHome = $byId($render($other), $orgId);
    $check(!isset($missingHome['url']) && !isset($missingHome['description']) && $missingHome['name'] === 'Fixture Company', 'No other-language fallback without a published home');
    $previewManager = new Contao\CoreBundle\Routing\ResponseContext\JsonLd\JsonLdManager(new Contao\CoreBundle\Routing\ResponseContext\ResponseContext());
    $previewEmitted = [];
    $check(isset($entities->emit($org, 'de', $previewManager, $previewEmitted)['legalName']), 'Standalone entity preview retains complete facts');
    $db->update('tl_schema_entity', ['published' => ''], ['id' => $org]);
    $nodes = $render($pages['de']);
    $check(!$byId($nodes, $orgId) && !isset($byId($nodes, $personId)['worksFor']), 'Unpublished related entity omitted');
    $db->update('tl_schema_translation', ['published' => ''], ['pid' => $person, 'language' => 'en']);
    $check(!$byId($render($pages['en']), $personId), 'Unpublished translation omitted');
    echo "PASS: entity graphs, contact publication, localized catalogues, cycle/date validation, manual Product and compact organization checks.\n";
} finally {
    $db->rollBack();
    echo "All integration fixture records rolled back.\n";
}
