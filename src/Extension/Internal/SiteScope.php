<?php
declare(strict_types=1);
namespace VHUG\SchemaManagerBundle\Extension\Internal;
use VHUG\SchemaManagerBundle\Extension\V1 as Api;
/** Explicit application configuration only; no inferred domain/language equivalence. */
final readonly class SiteScope
{
    public function __construct(private array $approvedRoots=[])
    {
        foreach($approvedRoots as $root=>$scope)if(!preg_match('/^[1-9][0-9]*$/D',(string)$root) || !is_string($scope) || $scope==='' || strlen($scope)>255 || preg_match('/[\x00-\x20]/',$scope))throw new \InvalidArgumentException('Invalid approved source-scope mapping.');
    }
    public function read(Api\ReadContext $context): Api\ReadContext
    {
        $root=$context->root??$context->site;
        $site=$this->approvedRoots[$root]??$context->site;
        return new Api\ReadContext($context->purpose,$site,$context->language,$context->actorId,$context->authorized,$context->now,$root);
    }
    public function write(Api\WriteContext $context): Api\WriteContext
    {
        return new Api\WriteContext($this->read($context->read),$context->identityOrigin,$context->expectedSourceRevision);
    }
}
