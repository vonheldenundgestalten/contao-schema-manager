<?php
declare(strict_types=1);
namespace VHUG\SchemaManagerBundle\EventListener;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
#[AsEventListener(event: KernelEvents::RESPONSE, priority: -512)]
final class JobExpiryListener
{
    public function __invoke(ResponseEvent $event): void
    {
        if (!$event->isMainRequest() || !$expiry = $event->getRequest()->attributes->get('_schema_job_expires')) { return; }
        $headers = $event->getResponse()->headers;
        $ttl = max(0, (int) $expiry - time());
        // Clamp the final response without making a private response public.
        foreach (['max-age', 's-maxage'] as $directive) {
            $current = $headers->getCacheControlDirective($directive);
            $headers->addCacheControlDirective($directive, (string) min($ttl, $current === null ? PHP_INT_MAX : (int) $current));
        }
    }
}
