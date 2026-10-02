<?php
declare(strict_types=1);
namespace VHUG\SchemaManagerBundle\Metadata;

/** Selection is shared by schema and social output; never guess from arbitrary images. */
final class ImageSelection
{
    public function candidates(string $mode, array $sources): array
    {
        if ($mode === 'none') { return []; }
        $order = $mode === 'override'
            ? ['page','pageimage','news','hero','fallback']
            : ['news','hero','page','pageimage','fallback'];
        return array_values(array_filter(array_map(static fn($key) => empty($sources[$key]) ? null : ['source'=>$key]+$sources[$key], $order)));
    }
}
