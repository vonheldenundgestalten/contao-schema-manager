<?php
declare(strict_types=1);
namespace VHUG\SchemaManagerBundle\EventListener;
use Contao\CoreBundle\DependencyInjection\Attribute\AsHook;
use Contao\CoreBundle\Routing\ResponseContext\ResponseContextAccessor;
use Contao\CoreBundle\Routing\ResponseContext\HtmlHeadBag\HtmlHeadBag;
use Contao\CoreBundle\String\HtmlAttributes;
use Contao\PageModel;
use Symfony\Component\HttpFoundation\RequestStack;
use VHUG\SchemaManagerBundle\Metadata\PageImageResolver;

#[AsHook('generatePage', priority: -100)]
final class SocialMetadataListener
{
    public function __construct(private readonly ResponseContextAccessor $contexts, private readonly RequestStack $requests, private readonly PageImageResolver $images) {}
    #[\Symfony\Component\EventDispatcher\Attribute\AsEventListener]
    public function onLayout(\Contao\CoreBundle\Event\LayoutEvent $event): void
    {
        $template=$event->getTemplate();
        if(!$template->has('response_context')){return;}
        $template->set('response_context',new \VHUG\SchemaManagerBundle\Metadata\SocialResponseContext(
            $template->get('response_context'),fn()=>$this($event->getPage())
        ));
    }
    public function __invoke(PageModel $page): void
    {
        $page->loadDetails();
        $root=PageModel::findById($page->rootId);
        $context=$this->contexts->getResponseContext();
        $request=$this->requests->getMainRequest();
        if (!$root?->schemaManageSocial || !$request || !$context?->has(HtmlHeadBag::class)) { return; }
        $head=$context->get(HtmlHeadBag::class);
        $reader=false;
        foreach($request->attributes->get('_schema_manager_news',[]) as $item){$reader=$reader || !empty($item['reader']);}
        $tags=['og:title'=>$head->getTitle(),'og:description'=>$head->getMetaDescription(),
            'og:url'=>$head->getCanonicalUriForRequest($request),'og:type'=>$reader?'article':'website',
            'og:site_name'=>$root->schemaSiteName];
        $twitter=['twitter:card'=>'summary','twitter:title'=>$head->getTitle(),'twitter:description'=>$head->getMetaDescription()];
        // Explicit ownership: remove previous bag entries, including images when disabled.
        foreach(['og:image','og:image:width','og:image:height','og:image:alt','og:image:type','og:image:secure_url'] as $key){$head->removeMetaTag('property',$key);}
        foreach(['twitter:image','twitter:image:alt'] as $key){$head->removeMetaTag('name',$key);}
        if($image=$this->images->resolve($page,$root)){
            $tags+=['og:image'=>$image['url'],'og:image:width'=>$image['width'],'og:image:height'=>$image['height'],'og:image:alt'=>$image['alt']];
            $twitter+=['twitter:image'=>$image['url'],'twitter:image:alt'=>$image['alt']];
            $twitter['twitter:card']='summary_large_image';
        }
        foreach(['property'=>$tags,'name'=>$twitter] as $attribute=>$items){
            foreach($items as $key=>$value){
                $head->removeMetaTag($attribute,$key);
                if($value !== '' && $value !== null){$head->addMetaTag(new HtmlAttributes([$attribute=>$key,'content'=>(string)$value]));}
            }
        }
    }
}
