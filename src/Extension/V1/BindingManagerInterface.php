<?php
declare(strict_types=1);
namespace VHUG\SchemaManagerBundle\Extension\V1;

interface BindingManagerInterface
{
    public function bind(BindRequest $request, WriteContext $context): EntityReference;
    public function resolve(SourceReference $source): ?EntityReference;
}
