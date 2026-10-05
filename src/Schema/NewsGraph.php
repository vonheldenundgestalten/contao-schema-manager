<?php
declare(strict_types=1);
namespace VHUG\SchemaManagerBundle\Schema;
use Contao\CoreBundle\Routing\ResponseContext\JsonLd\JsonLdManager;
use Contao\CoreBundle\Routing\ContentUrlGenerator;
use Contao\CoreBundle\String\HtmlDecoder;
use Contao\News;
use Contao\NewsModel;
use Contao\NewsArchiveModel;
use Contao\CoreBundle\Cache\CacheTagManager;
use Doctrine\DBAL\Connection;
use Spatie\SchemaOrg\NewsArticle;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class NewsGraph
{
    public function __construct(
        private readonly Connection $connection,
        private readonly EntityGraph $entities,
        private readonly ContentUrlGenerator $urls,
        private readonly HtmlDecoder $decoder,
        private readonly CacheTagManager $tags,
    ) {}

    /** @return array<array{'@id': string}> Reader subjects for WebPage.mainEntity. */
    public function apply(array $items, string $language, JsonLdManager $manager, array &$emitted): array
    {
        $subjects = [];
        $graph = $manager->getGraphForSchema(JsonLdManager::SCHEMA_ORG);
        foreach ($items as $item) {
            $record = $item['record'];
            $archive = $this->connection->fetchAssociative('SELECT * FROM tl_news_archive WHERE id = ?', [$record['pid']]);
            $mode = $archive['schemaType'] ?? '';
            $core=$mode==='';
            if($core){
                // Default archives may enrich core news without changing its type or identity.
                if(empty($record['schemaAuthor']) && empty($record['schemaAbout']) && empty($record['schemaMentions']) && empty($record['schemaDateModified']) && empty($archive['schemaPublisher']))continue;
                $mode='NewsArticle';
            }
            $this->tags->tagWithModelClass(NewsArchiveModel::class);
            $this->tags->tagWithModelClass(NewsModel::class);
            $this->tags->tagWithModelClass(\Contao\UserModel::class);
            $key = '#/schema/news/'.$record['id'];
            if ($mode === 'suppress') { $graph->hide(NewsArticle::class, $key); continue; }
            if ($mode === 'JobPosting') {
                $graph->hide(NewsArticle::class, $key);
                if (empty($item['reader']) || !$record['published'] || (!empty($record['start']) && $record['start'] > time())
                    || (!empty($record['stop']) && $record['stop'] <= time())) { continue; }
                $employerId = (int) (($record['schemaJobEmployer'] ?? 0) ?: ($archive['schemaPublisher'] ?? 0));
                $employerRecord = $this->entities->record($employerId);
                if (!$employerRecord || !in_array($employerRecord['entityType'], ['Organization', 'LocalBusiness'], true)) { continue; }
                $model = NewsModel::findById($record['id']);
                if (!$model) { continue; }
                $template = $item['template'];
                $description = $this->decoder->htmlToPlainText((string) ($template->hasText ? $template->text : ($record['teaser'] ?? '')));
                $node = (new JobMapper())->map($record, $archive, ['@id' => $employerRecord['entityId']],
                    $this->urls->generate($model, [], UrlGeneratorInterface::ABSOLUTE_URL), $language,
                    $this->decoder->htmlToPlainText($record['headline']), $description);
                if (!$node) { continue; }
                $this->entities->emit($employerId, $language, $manager, $emitted);
                $graph->set($manager->createSchemaOrgTypeFromArray($node), $record['schemaIdentity']);
                $subjects[] = ['@id' => $record['schemaIdentity']];
                continue;
            }
            if (!in_array($mode, ['BlogPosting','Article','NewsArticle'], true)) { continue; }
            if (!$item['detail'] && !$graph->has(NewsArticle::class, $key)) { continue; }
            $model = NewsModel::findById($record['id']);
            if (!$model || !$record['published'] || ($record['start'] && $record['start'] > time())
                || ($record['stop'] && $record['stop'] <= time())) { continue; }
            if (!$core && empty($record['schemaIdentity'])) { continue; } // IDs are persisted by editorial save/backfill.
            $node = $graph->has(NewsArticle::class, $key) ? $graph->get(NewsArticle::class, $key)->toArray() : News::getSchemaOrgData($model);
            unset($node['@context']);
            if(!$core){unset($node['identifier']);$node['@type']=$mode;$node['@id']=$record['schemaIdentity'];}
            else {$node['@id'] ??= $key;}
            $node['inLanguage'] = $language;
            $node['url'] = $this->urls->generate($model, [], UrlGeneratorInterface::ABSOLUTE_URL);
            $node['mainEntityOfPage'] = ['@id' => $node['url'].'#webpage'];
            $node['headline'] = $this->decoder->htmlToPlainText($record['headline']);
            $template = $item['template'];
            if ($template->hasText) { $node['articleBody'] = $this->decoder->htmlToPlainText((string) $template->text); }
            if ($template->figure) { $node['image'] = $template->figure->getSchemaOrgData(); }
            if ($publisher = $this->entities->emit((int) ($archive['schemaPublisher'] ?? 0), $language, $manager, $emitted)) {
                $node['publisher'] = ['@id' => $publisher['@id']];
            }
            $authorId = (int) ($record['schemaAuthor'] ?? 0);
            if (!$authorId) {
                $authorId = (int) $this->connection->fetchOne('SELECT schemaPerson FROM tl_user WHERE id = ?', [$record['author']]);
            }
            if ($author = $this->entities->emit($authorId, $language, $manager, $emitted)) {
                $node['author'] = ['@id' => $author['@id']];
            }
            foreach (['schemaAbout'=>'about', 'schemaMentions'=>'mentions'] as $field=>$property) {
                $refs = [];
                foreach (array_unique(\Contao\StringUtil::deserialize($record[$field] ?? null, true)) as $relatedId) {
                    if ($related = $this->entities->emit((int)$relatedId, $language, $manager, $emitted)) { $refs[] = ['@id'=>$related['@id']]; }
                }
                if ($refs) {
                    // Retain contributions made by core/templates/other listeners.
                    $existing = $node[$property] ?? [];
                    if (!is_array($existing) || !array_is_list($existing)) { $existing = $existing ? [$existing] : []; }
                    $node[$property] = array_values(array_unique(array_merge($existing, $refs), SORT_REGULAR));
                }
            }
            // Modification dates are explicit: generic tstamp changes for administrative edits too.
            if (!empty($record['schemaDateModified'])) { $node['dateModified'] = date(DATE_ATOM, (int) $record['schemaDateModified']); }
            if (!empty($record['languageMain'])) {
                $original = $this->connection->fetchOne('SELECT schemaIdentity FROM tl_news WHERE id = ? AND published = ?', [$record['languageMain'], '1']);
                if ($original) { $node['translationOfWork'] = ['@id' => $original]; }
            }
            if(!$core)$graph->hide(NewsArticle::class, $key);
            $graph->set($manager->createSchemaOrgTypeFromArray($node), $core?$key:$record['schemaIdentity']);
            if ($item['detail']) { $subjects[] = ['@id' => $node['@id']]; }
        }
        return $subjects;
    }
}
