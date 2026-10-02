<?php
declare(strict_types=1);
namespace VHUG\SchemaManagerBundle\EventListener;
use Contao\CoreBundle\DependencyInjection\Attribute\AsHook;
use Contao\FrontendTemplate;
use Contao\Module;
use Contao\ModuleNewsReader;
use Symfony\Component\HttpFoundation\RequestStack;

#[AsHook('parseArticles')]
final class NewsCaptureListener
{
    public function __construct(private readonly RequestStack $requests) {}
    public function __invoke(FrontendTemplate $template, array $record, Module $module): void
    {
        $request = $this->requests->getMainRequest();
        if (!$request) { return; }
        $items = $request->attributes->get('_schema_manager_news', []);
        $detail = $module instanceof ModuleNewsReader || !$template->hasReader;
        // A teaser later on the same page must not replace the reader's richer contribution.
        if (!isset($items[$record['id']]) || $detail) {
            $items[$record['id']] = ['record' => $record, 'template' => $template, 'detail' => $detail];
        }
        $request->attributes->set('_schema_manager_news', $items);
    }
}
