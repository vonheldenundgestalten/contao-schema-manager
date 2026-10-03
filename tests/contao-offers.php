<?php
declare(strict_types=1);
require getcwd().'/vendor/autoload.php';
$kernel=Contao\ManagerBundle\HttpKernel\ContaoKernel::fromInput(getcwd(),new Symfony\Component\Console\Input\ArgvInput());
if(getenv('SCHEMA_TEST_DB_TCP')==='1'){
 foreach(['_SERVER','_ENV'] as $scope){if(isset($GLOBALS[$scope]['DATABASE_URL'])){$GLOBALS[$scope]['DATABASE_URL']=str_replace('@localhost','@127.0.0.1',$GLOBALS[$scope]['DATABASE_URL']);}}
 if(isset($_SERVER['DATABASE_URL'])){putenv('DATABASE_URL='.$_SERVER['DATABASE_URL']);}
}
$kernel->boot();$c=$kernel->getContainer();$c->get('contao.framework')->initialize();$db=$c->get('database_connection');

// Prove the native Twig/PHP contribution path before introducing an adapter API.
$request = Symfony\Component\HttpFoundation\Request::create('https://example.test/hosting');
$context = new Contao\CoreBundle\Routing\ResponseContext\ResponseContext();
$manager = new Contao\CoreBundle\Routing\ResponseContext\JsonLd\JsonLdManager($context);
$context->add($manager);
$request->attributes->set(Contao\CoreBundle\Routing\ResponseContext\ResponseContext::REQUEST_ATTRIBUTE_NAME, $context);
$stack = $c->get('request_stack');
$stack->push($request);
try {
    $graph = $manager->getGraphForSchema($manager::SCHEMA_ORG);
    $serviceId = 'https://example.test/#service-hosting';
    $graph->set((new Spatie\SchemaOrg\Service())->setProperty('@id', $serviceId)->name('Managed hosting'), $serviceId);
    $offer = ['@type' => 'Offer', '@id' => $serviceId.'/offer-monthly', 'identifier' => $serviceId.'/offer-monthly',
        'price' => '10.00', 'priceCurrency' => 'EUR', 'itemOffered' => ['@id' => $serviceId]];
    $twig = $c->get('twig')->createTemplate('{% do add_schema_org(offer) %}');
    $twig->render(['offer' => $offer]);
    $yearly = $offer;
    $yearly['@id'] = $yearly['identifier'] = $serviceId.'/offer-yearly';
    $yearly['price'] = '100.00';
    (new Contao\FrontendTemplate('unused'))->addSchemaOrg($yearly);
    $nodes = $graph->toArray()['@graph'];
    if (count($nodes) !== 3 || $graph->get(Spatie\SchemaOrg\Service::class, $serviceId)->toArray()['name'] !== 'Managed hosting') {
        throw new RuntimeException('Native offers must coexist without replacing their service.');
    }
    foreach ([$offer, $yearly] as $expected) {
        $actual = $graph->get(Spatie\SchemaOrg\Offer::class, $expected['@id'])->toArray();
        if ($actual['itemOffered'] !== ['@id' => $serviceId] || $actual['price'] !== $expected['price']) {
            throw new RuntimeException('Native offer must retain its link and exact price.');
        }
    }
    $offer['price'] = '12.00';
    $twig->render(['offer' => $offer]);
    if (count($graph->toArray()['@graph']) !== 3 || $graph->get(Spatie\SchemaOrg\Offer::class, $offer['@id'])->toArray()['price'] !== '12.00') {
        throw new RuntimeException('Re-emission with the same identifier must update the same offer.');
    }
    echo "PASS: native Twig and PHP offers coexist, reference a shared service, and update by stable identifier; no database writes.\n";
} finally {
    $stack->pop();
}
