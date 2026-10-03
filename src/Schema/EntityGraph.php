<?php
declare(strict_types=1);
namespace VHUG\SchemaManagerBundle\Schema;
use Contao\CoreBundle\Cache\CacheTagManager;
use Contao\CoreBundle\Routing\ContentUrlGenerator;
use Contao\CoreBundle\Routing\ResponseContext\JsonLd\JsonLdManager;
use Contao\ContentModel;
use Contao\ArticleModel;
use Contao\StringUtil;
use Contao\CoreBundle\String\HtmlDecoder;
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
        private readonly PriceParser $prices,
        private readonly HtmlDecoder $decoder,
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
        $organization = $this->record((int) $entity['organization']);
        $priceText = null;
        if ($translation && !empty($translation['sourceContent'])) {
            $source = ContentModel::findById($translation['sourceContent']);
            $article = $source && $source->ptable === 'tl_article' ? ArticleModel::findById($source->pid) : null;
            if ($article) { $this->tags->tagWithModelInstance($article); }
            if ($source) { $this->tags->tagWithModelInstance($source); }
            if ($source && $source->type === 'pricing' && $article && (int) $article->pid === (int) $translation['page']
                && $article->published && !$article->protected
                && (!$article->start || $article->start <= time()) && (!$article->stop || $article->stop > time())
                && !$source->invisible && !$source->protected
                && (!$source->start || $source->start <= time()) && (!$source->stop || $source->stop > time())) {
                $rows = StringUtil::deserialize($source->pricing, true);
                $item = $rows[$translation['sourceRow']] ?? null;
                if (is_array($item)) {
                    $translation['name'] = $this->decoder->htmlToPlainText($item['headline'] ?? '');
                    $translation['description'] = $this->decoder->htmlToPlainText($item['text'] ?? '');
                    $priceText = (string) ($item['price'] ?? '');
                } else {
                    unset($emitted[$entity['entityId']]);
                    return null; // Never publish a stale offer when its source disappeared.
                }
            } else {
                unset($emitted[$entity['entityId']]);
                return null;
            }
        }
        $node = $this->mapper->map($entity, $translation, $url, $organization);
        foreach (['vatID', 'taxID'] as $field) {
            if (in_array($entity['entityType'], ['Organization','LocalBusiness'], true) && !empty($entity[$field])) {
                $node[$field] = $entity[$field];
            }
        }
        if (!empty($entity['sameAs'])) {
            $links = preg_split('/\R/', trim($entity['sameAs']));
            $node['sameAs'] = array_values(array_filter($links, static fn ($url) => preg_match('~^https?://~', $url)));
        }
        if (!empty($entity['image']) && ($file = FilesModel::findByUuid($entity['image']))) {
            $image = rtrim($entity['identityBase'], '/').'/'.ltrim($file->path, '/');
            $node[in_array($entity['entityType'], ['Organization','LocalBusiness'], true) ? 'logo' : 'image'] = $image;
        }
        if ($url && !empty($translation['isMainEntity'])) { $node['mainEntityOfPage'] = ['@id' => $url.'#webpage']; }
        if ($entity['entityType'] === 'Service' && $priceText !== null) {
            $offer = ['@type' => 'Offer', '@id' => $entity['entityId'].'/offer', 'itemOffered' => ['@id' => $entity['entityId']], 'name' => $node['name'], 'description' => trim(str_replace('*', '', $priceText))];
            if ($url) { $offer['url'] = $url; }
            if ($organization) { $offer['seller'] = ['@id' => $organization['entityId']]; }
            if ($price = $this->prices->parse($priceText)) { $offer['priceSpecification'] = $price; }
            $node['offers'] = $offer;
        }
        // A company keeps its full description on its localized home. Supporting
        // references stay identifiable without repeating all legal/contact facts.
        // Decide before publishing the node, so later graph listeners can enrich it.
        // Backend entity previews have no frontend page and remain complete.
        $page = $this->requests->getMainRequest()?->attributes->get('pageModel');
        if (in_array($entity['entityType'], ['Organization', 'LocalBusiness'], true)
            && $page instanceof PageModel
            && (!$translation || (int) $translation['page'] !== (int) $page->id)) {
            $node = array_intersect_key($node, array_flip(['@type', '@id', 'name', 'url', 'logo']));
        }
        $manager->getGraphForSchema(JsonLdManager::SCHEMA_ORG)
            ->set($manager->createSchemaOrgTypeFromArray($node), $entity['entityId']);
        $emitted[$entity['entityId']] = $node;
        if ($organization) { $this->emit((int) $organization['id'], $language, $manager, $emitted); }
        return $node;
    }
}
