<?php
declare(strict_types=1);
namespace VHUG\SchemaManagerBundle;
use Symfony\Component\HttpKernel\Bundle\Bundle;
final class SchemaManagerBundle extends Bundle
{
    public function build(\Symfony\Component\DependencyInjection\ContainerBuilder $container): void
    {
        parent::build($container);
        $container->addCompilerPass(new \VHUG\SchemaManagerBundle\DependencyInjection\Compiler\ExtensionPass());
        foreach ([
            \VHUG\SchemaManagerBundle\Extension\V1\SourceAdapterInterface::class=>'schema_manager.source_adapter',
            \VHUG\SchemaManagerBundle\Extension\V1\TypeProviderInterface::class=>'schema_manager.type_provider',
            \VHUG\SchemaManagerBundle\Extension\V1\RelationshipProviderInterface::class=>'schema_manager.relationship_provider',
            \VHUG\SchemaManagerBundle\Extension\V1\PayloadMigrationInterface::class=>'schema_manager.payload_migration',
            \VHUG\SchemaManagerBundle\Extension\V1\EntityTypeMapperInterface::class=>'schema_manager.type_mapper',
            \VHUG\SchemaManagerBundle\Extension\V1\EntityTypeValidatorInterface::class=>'schema_manager.type_validator',
        ] as $interface=>$tag) $container->registerForAutoconfiguration($interface)->addTag($tag);
    }
    public function getPath(): string { return dirname(__DIR__); }
}
