<?php
declare(strict_types=1);
namespace VHUG\SchemaManagerBundle\Extension\Internal;
use Contao\CoreBundle\Cache\CacheTagManager;
use VHUG\SchemaManagerBundle\Extension\V1 as Api;
final class SourceInvalidator implements Api\SourceInvalidatorInterface
{
    public const TAG='schema_manager.extension.v1';
    public function __construct(private readonly CacheTagManager $tags) {}
    public function changed(Api\SourceReference $source): void { $this->tags->invalidateTags([self::TAG,'schema_manager.source.'.$source->key()]); }
    public function adapterChanged(string $adapterKey): void { Registry::key($adapterKey);$this->tags->invalidateTags([self::TAG]); }
}
