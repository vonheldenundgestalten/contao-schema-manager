<?php
declare(strict_types=1);
namespace VHUG\SchemaManagerBundle\Extension\V1;

final readonly class SourceSnapshot
{
    public function __construct(
        public SourceReference $source,
        public string $revision,
        public string $typeKey,
        public array $globalFields,
        public array $localizedFields,
        public ?string $url = null,
        public array $relationships = [],
        public array $cacheTags = [],
        public ?int $nextVisibilityChange = null,
        public ?string $editUrl = null,
        public array $provenance = [],
        /** Persisted on the original record for automatic sources. */
        public ?string $publicIdentity = null,
    ) { if ($revision==="") throw new \InvalidArgumentException("Revision is required."); if ($url!==null && (!filter_var($url,FILTER_VALIDATE_URL) || !in_array(parse_url($url,PHP_URL_SCHEME),["http","https"],true))) throw new \InvalidArgumentException("Invalid source URL."); }
}
