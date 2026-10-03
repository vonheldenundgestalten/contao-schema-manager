<?php
declare(strict_types=1);
namespace VHUG\SchemaManagerBundle\EventListener;
use Contao\CoreBundle\Cache\CacheTagManager;
use Contao\CoreBundle\Event\JsonLdEvent;
use Contao\CoreBundle\Routing\ContentUrlGenerator;
use Contao\CoreBundle\Routing\ResponseContext\JsonLd\JsonLdManager;
use Contao\PageModel;
use Contao\StringUtil;
use Doctrine\DBAL\Connection;
use Spatie\SchemaOrg\WebPage;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use VHUG\SchemaManagerBundle\Model\EntityModel;
use VHUG\SchemaManagerBundle\Model\TranslationModel;
use VHUG\SchemaManagerBundle\Schema\EntityGraph;
use VHUG\SchemaManagerBundle\Schema\NewsGraph;

#[AsEventListener(priority: -100)]
final class JsonLdListener
{
    public function __construct(
        private readonly Connection $connection,
        private readonly RequestStack $requests,
        private readonly ContentUrlGenerator $urls,
        private readonly CacheTagManager $cacheTags,
        private readonly EntityGraph $entities,
        private readonly NewsGraph $news,
    ) {}
    public function __invoke(JsonLdEvent $event): void
    {
        $request = $this->requests->getMainRequest();
        $page = $request?->attributes->get('pageModel');
        $context = $event->getResponseContext();
        if (!$page instanceof PageModel || !$context->has(JsonLdManager::class)) { return; }
        $page->loadDetails();
        $language = $page->language;
        $this->cacheTags->tagWithModelClass(EntityModel::class);
        $this->cacheTags->tagWithModelClass(TranslationModel::class);
        $this->cacheTags->tagWithModelClass(PageModel::class);
        $manager = $context->get(JsonLdManager::class);
        $graph = $manager->getGraphForSchema(JsonLdManager::SCHEMA_ORG);
        $webPage = $graph->getOrCreate(WebPage::class);
        $emitted = [];
        $main = [];
        $about = [];
        $translations = $this->connection->fetchAllAssociative(
            'SELECT * FROM tl_schema_translation WHERE page = ? AND language = ? AND published = ? ORDER BY id',
            [(int) $page->id, $language, '1']
        );
        foreach ($translations as $translation) {
            if ($node = $this->entities->emit((int) $translation['pid'], $language, $manager, $emitted)) {
                if ($translation['isMainEntity']) { $main[$node['@id']] = ['@id' => $node['@id']]; }
            }
        }
        foreach (StringUtil::deserialize($page->schemaEntities, true) as $id) {
            if ($node = $this->entities->emit((int) $id, $language, $manager, $emitted)) {
                $about[$node['@id']] = ['@id' => $node['@id']];
            }
        }
        $root = PageModel::findById($page->rootId);
        if ($root?->schemaWebsiteRoot) { $root = PageModel::findById($root->schemaWebsiteRoot); }
        if ($root) { $this->cacheTags->tagWithModelInstance($root); }
        if ($root && ($publisher = $this->entities->emit((int) $root->schemaPublisher, $language, $manager, $emitted))) {
            $webPage->setProperty('publisher', ['@id' => $publisher['@id']]);

        }
        $newsSubjects = $this->news->apply($request->attributes->get('_schema_manager_news', []), $language, $manager, $emitted);
        foreach ($newsSubjects as $subject) { $main[$subject['@id']] = $subject; }
        foreach ($request->attributes->get('_schema_manager_news', []) as $item) {
            $expiry = (int) ($item['record']['schemaJobValidThrough'] ?? 0);
            if ($expiry <= time() || !$graph->has(\Spatie\SchemaOrg\JobPosting::class, $item['record']['schemaIdentity'] ?? '')) { continue; }
            $request->attributes->set('_schema_job_expires', min($expiry, $request->attributes->get('_schema_job_expires', PHP_INT_MAX)));
        }
        if ($emitted || $newsSubjects) {
            $url = $this->urls->generate($page, ['parameters' => $request->attributes->get('parameters', '')], UrlGeneratorInterface::ABSOLUTE_URL);
            // Reader URLs include the record alias, unlike the bare reader-page route.
            if ($newsSubjects) {
                foreach ($graph->toArray()['@graph'] ?? [] as $node) {
                    if (($node['@id'] ?? '') === $newsSubjects[0]['@id']) { $url = $node['url']; break; }
                }
            }
            $webPage->setProperty('@id', $url.'#webpage');
            $webPage->setProperty('url', $url);
            $webPage->setProperty('inLanguage', $language);
        }
        if ($main) { $webPage->setProperty('mainEntity', count($main) === 1 ? reset($main) : array_values($main)); }
        if ($about) { $webPage->setProperty('about', array_values($about)); }
    }
}
