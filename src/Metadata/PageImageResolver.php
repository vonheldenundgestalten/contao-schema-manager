<?php
declare(strict_types=1);
namespace VHUG\SchemaManagerBundle\Metadata;
use Contao\CoreBundle\Cache\CacheTagManager;
use Contao\CoreBundle\Image\Studio\Studio;
use Contao\CoreBundle\String\HtmlDecoder;
use Contao\FilesModel;
use Contao\PageModel;
use Contao\StringUtil;
use Contao\Image\ResizeConfiguration;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\UrlHelper;

final class PageImageResolver
{
    public function __construct(
        private readonly Studio $studio, private readonly RequestStack $requests,
        private readonly UrlHelper $urls, private readonly CacheTagManager $tags,
        private readonly HtmlDecoder $decoder, private readonly ImageSelection $selection,
        private readonly LoggerInterface $logger,
    ) {}
    public function resolve(PageModel $page, ?PageModel $root): ?array
    {
        $request=$this->requests->getMainRequest();
        if (!$request) { return null; }
        $key='_schema_image_result_'.$page->id;
        if ($request->attributes->has($key)) { return $request->attributes->get($key); }
        $sources=[];
        if ($page->schemaPageImage) { $sources['page']=['uuid'=>$page->schemaPageImage,'alt'=>$page->schemaImageAlt]; }
        // Optional terminal42/contao-pageimage adapter. Only explicit images on this page:
        // inherited banner selection belongs to that extension's module configuration.
        $pageImages=StringUtil::deserialize($page->pageImage,true);
        $order=StringUtil::deserialize($page->pageImageOrder,true);
        $pageImages=array_values(array_unique([...array_filter($order,static fn($id)=>in_array($id,$pageImages,true)),...$pageImages]));
        if ($pageImages) { $sources['pageimage']=['uuid'=>$pageImages[0],'alt'=>$page->pageImageOverwriteMeta ? $page->pageImageAlt : '']; }
        foreach ($request->attributes->get('_schema_manager_news',[]) as $item) {
            if (!empty($item['reader']) && !empty($item['record']['addImage']) && !empty($item['record']['singleSRC'])) {
                $sources['news']=['uuid'=>$item['record']['singleSRC'],'alt'=>!empty($item['record']['overwriteMeta']) ? ($item['record']['alt'] ?? '') : ''];
                break;
            }
        }
        if ($hero=$request->attributes->get('_schema_manager_hero')) { $sources['hero']=$hero; }
        if ($root?->schemaFallbackImage) { $sources['fallback']=['uuid'=>$root->schemaFallbackImage,'alt'=>$root->schemaFallbackAlt]; }
        $result=null;
        foreach ($this->selection->candidates((string)$page->schemaImageMode,$sources) as $candidate) {
            try {
                $file=FilesModel::findByUuid($candidate['uuid']);
                if (!$file || !in_array(strtolower(pathinfo($file->path,PATHINFO_EXTENSION)),['jpg','jpeg','png','webp'],true)) { continue; }
                $this->tags->tagWithModelInstance($file);
                $figure=$this->studio->createFigureBuilder()->from($file)->setLocale($page->language)->build();
                if (!$figure) { continue; }
                $schema=$figure->getSchemaOrgData();
                $id=$schema['identifier'] ?? $schema['contentUrl'];
                unset($schema['identifier']);
                $schema['@id']=$id;
                $schema['contentUrl']=$this->urls->getAbsoluteUrl($schema['contentUrl']);
                $alt=trim((string)($candidate['alt'] ?? ''));
                if (!$alt && $figure->hasMetadata()) { $alt=(string)$figure->getMetadata()->getAlt(); }
                $alt=$this->decoder->inputEncodedToPlainText($alt,true);
                $size=$root?->schemaSocialCrop === 'crop' ? [1200,630,ResizeConfiguration::MODE_CROP] : [1200,1200,ResizeConfiguration::MODE_BOX];
                $social=$this->studio->createFigureBuilder()->from($file)->setLocale($page->language)->setSize($size)->build();
                if (!$social) { continue; }
                $social->getImage()->createIfDeferred();
                $img=$social->getImage()->getImg();
                $result=['source'=>$candidate['source'],'schema'=>$schema,'url'=>$this->urls->getAbsoluteUrl($img['src']),
                    'width'=>(int)$img['width'],'height'=>(int)$img['height'],'alt'=>$alt];
                break;
            } catch (\Exception $exception) {
                $this->logger->warning('Could not build representative page image.', ['page'=>$page->id,'source'=>$candidate['source'],'exception'=>$exception]);
            }
        }
        $request->attributes->set($key,$result);
        return $result;
    }
}
