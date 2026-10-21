<?php
declare(strict_types=1);
namespace VHUG\SchemaManagerBundle\Extension\V1;

final readonly class SourceResolution
{
    public function __construct(
        public string $status,
        public ?SourceSnapshot $snapshot = null,
        public ?string $diagnostic = null,
        public ?int $nextVisibilityChange = null,
        public array $cacheTags = [],
    )
    {
        if (!in_array($status,['available','unpublished','missing','forbidden','translation_missing','unavailable'],true)
            || (($status==='available') !== ($snapshot!==null))) throw new \InvalidArgumentException('Invalid source resolution.');
    }
}
