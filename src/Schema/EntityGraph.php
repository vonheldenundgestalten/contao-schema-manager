<?php
declare(strict_types=1);
namespace VHUG\SchemaManagerBundle\Schema;
use Contao\CoreBundle\Cache\CacheTagManager;
use Contao\CoreBundle\Routing\ContentUrlGenerator;
use Contao\CoreBundle\Routing\ResponseContext\JsonLd\JsonLdManager;
use Contao\FilesModel;
use Contao\PageModel;
use Doctrine\DBAL\Connection;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\HttpFoundation\RequestStack;

final class EntityGraph
{
    public function __construct(
        private readonly Connection $connection,
        private readonly ContentUrlGenerator $urls,
        private readonly CacheTagManager $tags,
        private readonly EntityMapper $mapper,
        private readonly RequestStack $requests,
    ) {}

    public function record(int $id): ?array
    {
        $row = $this->connection->fetchAssociative('SELECT * FROM tl_schema_entity WHERE id = ? AND published = ?', [$id, '1']);
        return $row && !empty($row['entityId']) ? $row : null;
    }

    public function emit(int $id, string $language, JsonLdManager $manager, array &$emitted): ?array
    {
        if (!$entity = $this->record($id)) { return null; }
        if (isset($emitted[$entity['entityId']])) { return $emitted[$entity['entityId']]; }
        // Reserve before following relationships, including defensive cycle handling.
        $emitted[$entity['entityId']] = ['@id' => $entity['entityId']];
        $translation = $this->connection->fetchAssociative(
            'SELECT * FROM tl_schema_translation WHERE pid = ? AND language = ? AND published = ? ORDER BY id',
            [$id, $language, '1']
        ) ?: null;
        $url = null;
        if ($translation) {
            $home = PageModel::findById($translation['page']);
            if ($home) {
                $home->loadDetails();
                $this->tags->tagWithModelInstance($home);
            }
            if ($home && $home->published && !$home->protected && !$home->requireItem && $home->language === $language
                && (!$home->start || $home->start <= time()) && (!$home->stop || $home->stop > time())) {
                $url = $this->urls->generate($home, [], UrlGeneratorInterface::ABSOLUTE_URL);
            } else { $translation = null; }
        }
        $isOrganization = in_array($entity['entityType'], ['Organization', 'LocalBusiness'], true);
        if ($isOrganization && !$url && !empty($entity['externalUrl'])) { $url = $entity['externalUrl']; }
        $page = $this->requests->getMainRequest()?->attributes->get('pageModel');
        $full = !$isOrganization || !($page instanceof PageModel) || ($translation && (int) $translation['page'] === (int) $page->id);
        $organization = $this->record((int) $entity['organization']);
        $node = $this->mapper->map($entity, $translation, $url, $organization);
        $this->tags->tagWithModelClass(\VHUG\SchemaManagerBundle\Model\ContactModel::class);
        if ($entity['entityType'] === 'Service' || ($isOrganization && $full)) {
            $countries = \Contao\StringUtil::deserialize($entity['areaServed'] ?? null, true);
            if ($countries) { $node['areaServed'] = array_values($countries); }
            $offers = [];
            foreach (array_unique(\Contao\StringUtil::deserialize($entity['subservices'] ?? null, true)) as $childId) {
                $childRecord = $this->record((int) $childId);
                if (!$childRecord || $childRecord['entityType'] !== 'Service') { continue; }
                $child = $this->emit((int) $childId, $language, $manager, $emitted);
                if (empty($child['url'])) { continue; }
                $offers[] = ['@type' => 'Offer', 'itemOffered' => ['@id' => $child['@id']]];
            }
            if ($offers) {
                $node['hasOfferCatalog'] = ['@type' => 'OfferCatalog', '@id' => $entity['entityId'].'/catalog',
                    'name' => ($translation['catalogName'] ?? '') ?: $node['name'], 'itemListElement' => $offers];
            }
        }
        if (in_array($entity['entityType'], ['Organization', 'LocalBusiness'], true)) {
            foreach ($this->connection->fetchAllAssociative('SELECT * FROM tl_schema_contact WHERE pid=? AND published=? ORDER BY id', [$id, '1']) as $contact) {
                if (!$contact['telephone'] && !$contact['email']) { continue; }
                $point = ['@type' => 'ContactPoint', '@id' => $entity['entityId'].'/contact-'.$contact['id'], 'contactType' => $contact['contactType']];
                foreach (['telephone', 'email'] as $field) { if ($contact[$field]) { $point[$field] = $contact[$field]; } }
                $languages = array_values(array_filter(array_map('trim', explode(',', $contact['availableLanguage']))));
                if ($languages) { $point['availableLanguage'] = $languages; }
                $countries = \Contao\StringUtil::deserialize($contact['areaServed'], true);
                if ($countries) { $point['areaServed'] = array_values($countries); }
                $node['contactPoint'][] = $point;
            }
        }
        if ($full) {
            $relations = [];
            if ($isOrganization || $entity['entityType'] === 'Person') {
                $relations['memberOf'] = [\Contao\StringUtil::deserialize($entity['memberOf'] ?? null, true), ['Organization', 'LocalBusiness']];
            }
            if ($entity['entityType'] === 'Person') {
                $relations['workLocation'] = [\Contao\StringUtil::deserialize($entity['workLocation'] ?? null, true), ['LocalBusiness']];
            }
            if ($isOrganization) {
                $offices = $this->connection->fetchFirstColumn("SELECT id FROM tl_schema_entity WHERE organization=? AND entityType='LocalBusiness' AND published='1'", [$id]);
                $relations['location'] = [array_merge($offices, \Contao\StringUtil::deserialize($entity['locations'] ?? null, true)), ['LocalBusiness']];
                $relations['subOrganization'] = [$this->connection->fetchFirstColumn("SELECT id FROM tl_schema_entity WHERE organization=? AND entityType='Organization' AND published='1'", [$id]), ['Organization']];
            }
            foreach ($relations as $property => [$ids, $types]) {
                $refs = [];
                foreach (array_unique($ids) as $relatedId) {
                    if ((int) $relatedId === $id) { continue; }
                    $related = $this->record((int) $relatedId);
                    if (!$related || !in_array($related['entityType'], $types, true)) { continue; }
                    if ($ref = $this->emit((int) $relatedId, $language, $manager, $emitted)) { $refs[] = ['@id' => $ref['@id']]; }
                }
                if ($refs) { $node[$property] = $refs; }
            }
        }
        foreach (['vatID', 'taxID'] as $field) {
            if (in_array($entity['entityType'], ['Organization','LocalBusiness'], true) && !empty($entity[$field])) {
                $node[$field] = $entity[$field];
            }
        }
        if (!empty($entity['sameAs'])) {
            $links = preg_split('/\R/', trim($entity['sameAs']));
            $node['sameAs'] = array_values(array_filter($links, static fn ($url) => preg_match('~^https?://~', $url)));
        }
        if ((!$isOrganization || empty($entity['externalUrl']) || $translation) && !empty($entity['image']) && ($file = FilesModel::findByUuid($entity['image']))) {
            $image = rtrim($entity['identityBase'], '/').'/'.ltrim($file->path, '/');
            $node[in_array($entity['entityType'], ['Organization','LocalBusiness'], true) ? 'logo' : 'image'] = $image;
        }
        if ($url && !empty($translation['isMainEntity'])) { $node['mainEntityOfPage'] = ['@id' => $url.'#webpage']; }
        // A company keeps its full description on its localized home. Supporting
        // references stay identifiable without repeating all legal/contact facts.
        // Decide before publishing the node, so later graph listeners can enrich it.
        // Backend entity previews have no frontend page and remain complete.
        if (!$full) {
            $keep = ['@type', '@id', 'name', 'url', 'logo'];
            if ($entity['entityType'] === 'LocalBusiness' && !empty($page->schemaLocationOverview)) {
                $keep = array_merge($keep, ['address', 'telephone', 'email', 'parentOrganization']);
            }
            $node = array_intersect_key($node, array_flip($keep));
        }
        $manager->getGraphForSchema(JsonLdManager::SCHEMA_ORG)
            ->set($manager->createSchemaOrgTypeFromArray($node), $entity['entityId']);
        $emitted[$entity['entityId']] = $node;
        if ($organization) { $this->emit((int) $organization['id'], $language, $manager, $emitted); }
        return $node;
    }
}
