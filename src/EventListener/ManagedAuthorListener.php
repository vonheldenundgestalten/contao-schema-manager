<?php

declare(strict_types=1);
namespace VHUG\SchemaManagerBundle\EventListener;

use Contao\CoreBundle\Event\JsonLdEvent;
use Contao\CoreBundle\Routing\ResponseContext\JsonLd\JsonLdManager;
use Doctrine\DBAL\Connection;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

/** Remove native name-only authors; explicit managed Person references survive. */
#[AsEventListener(priority: -400)]
final class ManagedAuthorListener
{
    public function __construct(private readonly Connection $connection) {}

    public function __invoke(JsonLdEvent $event): void
    {
        $context = $event->getResponseContext();
        if (!$context->has(JsonLdManager::class)) return;
        $manager = $context->get(JsonLdManager::class);
        $graph = $manager->getGraphForSchema(JsonLdManager::SCHEMA_ORG);
        $nodes = $graph->toArray()['@graph'] ?? [];
        $present = array_fill_keys(array_filter(array_column($nodes, '@id')), true);
        $allowed = array_intersect_key(array_fill_keys($this->connection->fetchFirstColumn("SELECT entityId FROM tl_schema_entity WHERE entityType='Person' AND published='1' AND entityId IS NOT NULL"), true), $present);
        // Mutate the existing objects: graph storage keys need not equal @id
        // (the native WebPage uses the graph's default key).
        foreach ($graph->getNodes() as $types) foreach ($types as $object) {
            $node = $object->toArray();
            if (!array_key_exists('author', $node)) continue;
            $authors = self::references($node['author'], $allowed);
            unset($object['author']);
            if ($authors) $object->setProperty('author', count($authors) === 1 ? $authors[0] : $authors);
        }
    }

    /** @param array<string, bool> $allowed */
    public static function references(mixed $value, array $allowed): array
    {
        $items = is_array($value) && array_is_list($value) ? $value : [$value];
        $result = [];
        foreach ($items as $item) {
            if (is_array($item) && is_string($item['@id'] ?? null) && isset($allowed[$item['@id']])) $result[$item['@id']] = ['@id'=>$item['@id']];
        }
        return array_values($result);
    }
}
