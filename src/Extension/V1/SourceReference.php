<?php
declare(strict_types=1);
namespace VHUG\SchemaManagerBundle\Extension\V1;

final readonly class SourceReference
{
    public function __construct(
        public string $adapterKey,
        public string $installationScope,
        public string $subjectKey,
    )
    {
        foreach ([$adapterKey,$installationScope,$subjectKey] as $value) {
            if ($value === '' || strlen($value)>255 || preg_match('/[\x00-\x1f]/',$value)) throw new \InvalidArgumentException('Invalid source reference.');
        }
    }
    public function key(): string { return hash('sha256',json_encode([$this->adapterKey,$this->installationScope,$this->subjectKey],JSON_THROW_ON_ERROR)); }
}
