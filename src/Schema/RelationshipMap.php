<?php
declare(strict_types=1);
namespace VHUG\SchemaManagerBundle\Schema;

/** Editorial relationships, including drafts. Deliberately not a frontend JSON-LD export. */
final class RelationshipMap
{
    public function build(array $entities, array $translations): array
    {
        $nodes = []; $edges = [];
        foreach ($entities as $row) {
            $id = 'entity-'.$row['id'];
            $detail = $row['entityType'] === 'LocalBusiness'
                ? implode(', ', array_filter([$row['streetAddress'] ?? '', trim(($row['postalCode'] ?? '').' '.($row['addressLocality'] ?? ''))])) : '';
            $nodes[$id] = ['data' => ['id'=>$id, 'record'=>(int)$row['id'], 'name'=>$row['name'], 'type'=>$row['entityType'],
                'detail'=>$detail, 'identity'=>$row['entityId'] ?? '', 'published'=>$row['published'] === '1', 'missing'=>false, 'homes'=>[]]];
        }
        foreach ($translations as $home) {
            $key = 'entity-'.$home['pid'];
            if (isset($nodes[$key])) {
                $nodes[$key]['data']['homes'][] = ['language'=>$home['language'], 'page'=>(int)$home['page'], 'published'=>$home['published'] === '1', 'name'=>$home['name'] ?? ''];
            }
        }
        $connect = static function (int $from, int $to, string $property) use (&$nodes, &$edges): void {
            if (!$to) { return; }
            $source = 'entity-'.$from; $target = 'entity-'.$to;
            if (!isset($nodes[$target])) {
                $nodes[$target] = ['data'=>['id'=>$target, 'record'=>$to, 'name'=>'#'.$to, 'type'=>'Missing', 'detail'=>'', 'identity'=>'', 'published'=>false, 'missing'=>true, 'homes'=>[]]];
            }
            $key = $source.'|'.$property.'|'.$target;
            $edges[$key] = ['data'=>['id'=>'relation-'.count($edges), 'source'=>$source, 'target'=>$target, 'label'=>$property]];
        };
        foreach ($entities as $row) {
            $id = (int)$row['id']; $type = $row['entityType']; $org = in_array($type, ['Organization','LocalBusiness'], true);
            if (!empty($row['organization'])) {
                $property = match ($type) { 'Person'=>'worksFor', 'Service'=>'provider', 'Event'=>'organizer', 'Product'=>'offers.seller', default=>'parentOrganization' };
                $connect($id, (int)$row['organization'], $property);
                if ($org && isset($nodes['entity-'.$row['organization']]) && !$nodes['entity-'.$row['organization']]['data']['missing']) {
                    $connect((int)$row['organization'], $id, $type === 'LocalBusiness' ? 'location' : 'subOrganization');
                }
            }
            foreach (['memberOf'=>[$org || $type === 'Person','memberOf'], 'workLocation'=>[$type === 'Person','workLocation'],
                'locations'=>[$org,'location'], 'subservices'=>[$org || $type === 'Service','hasOfferCatalog.itemOffered']] as $field=>[$enabled,$property]) {
                if ($enabled) { foreach (array_unique($row[$field] ?? []) as $target) { $connect($id, (int)$target, $property); } }
            }
        }
        // Deterministic edge identities even when an explicit office duplicates an inferred one.
        $edges = array_values($edges);
        foreach ($edges as $index=>&$edge) { $edge['data']['id'] = 'relation-'.$index; }
        unset($edge);
        return ['nodes'=>array_values($nodes), 'edges'=>$edges];
    }
}
