<?php
declare(strict_types=1);
namespace VHUG\SchemaManagerBundle;
use Symfony\Component\HttpKernel\Bundle\Bundle;
final class SchemaManagerBundle extends Bundle
{
    public function getPath(): string { return dirname(__DIR__); }
}
