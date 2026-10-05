<?php
declare(strict_types=1);
namespace VHUG\SchemaManagerBundle\EventListener;
use Contao\CoreBundle\DependencyInjection\Attribute\AsHook;
use Contao\Template;
use Symfony\Component\HttpFoundation\RequestStack;
#[AsHook('parseTemplate')]
final class EventCaptureListener
{
    public function __construct(private readonly RequestStack $requests){}
    public function __invoke(Template $template): void
    {
        $data=$template->getData();$callback=$data['getSchemaOrgData'] ?? null;
        // The calendar's schema callback is also present on custom event templates.
        if(!is_callable($callback)||!isset($data['id'],$data['startTime'],$data['endTime'])||!class_exists(\Contao\CalendarEventsModel::class))return;
        $schema=$callback();if(($schema['@type']??'')!=='Event')return;
        $request=$this->requests->getMainRequest();if(!$request)return;
        $items=$request->attributes->get('_schema_manager_events',[]);
        $items[(int)$data['id']]=['id'=>(int)$data['id'],'key'=>$schema['identifier']??('#/schema/events/'.$data['id'])];
        $request->attributes->set('_schema_manager_events',$items);
    }
}
