<?php
declare(strict_types=1);
namespace VHUG\SchemaManagerBundle\Extension\Internal;
use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;
use Contao\CoreBundle\Cache\CacheTagManager;
final class RegistryWarmer implements CacheWarmerInterface
{
    public function __construct(private readonly Registry $registry,private readonly CacheTagManager $tags) {}
    public function isOptional(): bool { return false; }
    public function warmUp(string $cacheDir,?string $buildDir=null): array
    {
        $this->registry->types();
        $this->tags->invalidateTags([SourceInvalidator::TAG]);
        return [];
    }
}
