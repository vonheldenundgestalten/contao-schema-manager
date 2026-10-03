<?php
declare(strict_types=1);
namespace VHUG\SchemaManagerBundle\EventListener;
use Contao\CoreBundle\DependencyInjection\Attribute\AsHook;
use Contao\ContentModel;
use Symfony\Component\HttpFoundation\RequestStack;

/** Project heroes opt in with this checkbox; capture only elements actually rendered. */
#[AsHook('getContentElement')]
final class HeroImageListener
{
    public function __construct(private readonly RequestStack $requests) {}
    public function __invoke(ContentModel $model, string $buffer, mixed $element=null): string
    {
        $request=$this->requests->getMainRequest();
        if ($request && trim($buffer) !== '' && $model->schemaPrimaryImage && $model->singleSRC
            && ($model->type === 'image' || $model->addImage)
            && !$model->invisible && !$model->protected && !$request->attributes->has('_schema_manager_hero')) {
            $request->attributes->set('_schema_manager_hero',['uuid'=>$model->singleSRC,'alt'=>$model->overwriteMeta ? $model->alt : '']);
        }
        return $buffer;
    }
}
