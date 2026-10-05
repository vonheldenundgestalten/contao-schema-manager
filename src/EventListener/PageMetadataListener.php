<?php
declare(strict_types=1);
namespace VHUG\SchemaManagerBundle\EventListener;
use Contao\CoreBundle\Cache\CacheTagManager;
use Contao\CoreBundle\Event\JsonLdEvent;
use Contao\CoreBundle\Routing\ContentUrlGenerator;
use Contao\CoreBundle\Routing\ResponseContext\HtmlHeadBag\HtmlHeadBag;
use Contao\CoreBundle\Routing\ResponseContext\JsonLd\JsonLdManager;
use Contao\PageModel;
use Doctrine\DBAL\Connection;
use Spatie\SchemaOrg\WebPage;
use Spatie\SchemaOrg\WebSite;
use Spatie\SchemaOrg\BreadcrumbList;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[AsEventListener(priority: -200)]
final class PageMetadataListener
{
    public const PAGE_TYPES=['WebPage','AboutPage','ContactPage','CollectionPage','ProfilePage','ItemPage'];
    public function __construct(
        private readonly RequestStack $requests,
        private readonly ContentUrlGenerator $urls, private readonly Connection $connection,
        private readonly CacheTagManager $tags,
    ) {}
    public function __invoke(JsonLdEvent $event): void
    {
        $request=$this->requests->getMainRequest();
        $page=$request?->attributes->get('pageModel');
        $context=$event->getResponseContext();
        if(!$page instanceof PageModel || !$context->has(JsonLdManager::class)){return;}
        $page->loadDetails();
        $root=PageModel::findById($page->rootId);
        $manager=$context->get(JsonLdManager::class);
        $graph=$manager->getGraphForSchema(JsonLdManager::SCHEMA_ORG);
        $web=$graph->getOrCreate(WebPage::class);
        if($context->has(HtmlHeadBag::class)){
            $head=$context->get(HtmlHeadBag::class);
            if($head->getTitle() !== ''){$web->setProperty('name',$head->getTitle());}
            if($head->getMetaDescription() !== ''){$web->setProperty('description',$head->getMetaDescription());}
            $url=$head->getCanonicalUriForRequest($request);
        }else{
            $url=$this->urls->generate($page,['parameters'=>$request->attributes->get('parameters','')],UrlGeneratorInterface::ABSOLUTE_URL);
        }
        foreach($request->attributes->get('_schema_manager_news',[]) as $item){
            if(empty($item['reader']) || empty($item['record']['schemaIdentity'])){continue;}
            foreach([\Spatie\SchemaOrg\BlogPosting::class,\Spatie\SchemaOrg\Article::class,\Spatie\SchemaOrg\NewsArticle::class,\Spatie\SchemaOrg\JobPosting::class] as $class){
                $id=$item['record']['schemaIdentity'];
                if($graph->has($class,$id)){
                    $article=$graph->get($class,$id);
                    $article->setProperty('url',$url);
                    $article->setProperty('mainEntityOfPage',['@id'=>$url.'#webpage']);
                }
            }
        }
        $web->setProperty('@id',$url.'#webpage');
        $web->setProperty('url',$url);
        $web->setProperty('inLanguage',$page->language);
        $this->tags->tagWithModelClass(PageModel::class);
        $this->tags->tagWithModelClass(\Contao\ContentModel::class);
        if($root){$this->website($root,$web,$manager);}
        if($graph->has(BreadcrumbList::class)){
            $breadcrumb=$graph->get(BreadcrumbList::class);
            $data=$breadcrumb->toArray();
            if(!empty($data['itemListElement'])){
                $id=$data['@id'] ?? $url.'#breadcrumb';
                $breadcrumb->setProperty('@id',$id);
                $web->setProperty('breadcrumb',['@id'=>$id]);
            }
        }
        if($page->schemaImportedActive && $page->schemaImportedData){
            $merged=\VHUG\SchemaManagerBundle\Schema\ImportedSchema::merge($web->toArray(),$page->schemaImportedData);
            foreach($merged as $property=>$value)if($property!=='@context')$web->setProperty($property,$value);
        }
        // Preserve the core page's accumulated properties when choosing a subtype.
        $type=in_array($page->schemaPageType,self::PAGE_TYPES,true)?$page->schemaPageType:'WebPage';
        if($type!=='WebPage'){
            $node=$web->toArray();unset($node['@context']);
            $node['@type']=$type;
            $graph->hide(WebPage::class);
            $graph->set($manager->createSchemaOrgTypeFromArray($node));
        }
    }
    private function website(PageModel $root, WebPage $web, JsonLdManager $manager): void
    {
        $site=$root->schemaWebsiteRoot ? PageModel::findById($root->schemaWebsiteRoot) : $root;
        if(!$site || $site->type!=='root' || !$site->schemaWebsiteId){return;}
        $this->tags->tagWithModelInstance($site);
        $home=$site->schemaWebsiteHome ? PageModel::findById($site->schemaWebsiteHome) : PageModel::findFirstPublishedRegularByPid($site->id);
        if(!$home || $home->requireItem){return;}
        $home->loadDetails();$this->tags->tagWithModelInstance($home);
        if(!$home->published || $home->protected){return;}
        $homeUrl=$this->urls->generate($home,[],UrlGeneratorInterface::ABSOLUTE_URL);
        if(!$site->schemaWebsiteHome){
            $parts=parse_url($homeUrl);
            $homeUrl=$parts['scheme'].'://'.$parts['host'].(isset($parts['port'])?':'.$parts['port']:'').($this->requests->getMainRequest()?->getBasePath() ?? '').'/';
        }
        $languages=$this->connection->fetchFirstColumn("SELECT language FROM tl_page WHERE type='root' AND published='1' AND (id=? OR schemaWebsiteRoot=?)",[$site->id,$site->id]);
        $node=['@type'=>'WebSite','@id'=>$site->schemaWebsiteId,'name'=>$site->schemaSiteName ?: $site->title,'url'=>$homeUrl,
            'inLanguage'=>array_values(array_unique(array_filter($languages)))];
        if($site->schemaSiteAlternateName){$node['alternateName']=$site->schemaSiteAlternateName;}
        $publisher=$this->connection->fetchOne("SELECT entityId FROM tl_schema_entity WHERE id=? AND published='1'",[$site->schemaPublisher]);
        if($publisher){$node['publisher']=['@id'=>$publisher];}
        if($site->schemaImportedActive)$node=\VHUG\SchemaManagerBundle\Schema\ImportedSchema::merge($node,$site->schemaImportedData);
        // The editable root identity is authoritative, including after legacy import.
        $node['@id']=$site->schemaWebsiteId;
        $manager->getGraphForSchema(JsonLdManager::SCHEMA_ORG)->set($manager->createSchemaOrgTypeFromArray($node),$site->schemaWebsiteId);
        $web->setProperty('isPartOf',['@id'=>$site->schemaWebsiteId]);
    }
}
