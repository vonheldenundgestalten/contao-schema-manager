<?php
declare(strict_types=1);
namespace VHUG\SchemaManagerBundle\Metadata;

/** Keep modern layouts lazy: the deferred head runs after content has supplied its image. */
final class SocialResponseContext
{
    private bool $prepared=false;
    public function __construct(private readonly object $inner, private readonly \Closure $prepare) {}
    public function __get(string $name): mixed
    {
        if($name==='head' && !$this->prepared){$this->prepared=true;($this->prepare)();}
        return $this->inner->$name;
    }
}
