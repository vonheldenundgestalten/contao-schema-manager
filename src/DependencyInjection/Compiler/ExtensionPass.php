<?php
declare(strict_types=1);
namespace VHUG\SchemaManagerBundle\DependencyInjection\Compiler;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use VHUG\SchemaManagerBundle\Extension\Internal\Registry;
use VHUG\SchemaManagerBundle\Extension\V1 as Api;
/** Definition providers are declarative/stateless; adapters can use application services. */
final class ExtensionPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $collect=static function(string $tag)use($container):array {
            $items=[];
            foreach($container->findTaggedServiceIds($tag) as $id=>$tags) {
                $class=$container->getParameterBag()->resolveValue($container->findDefinition($id)->getClass());
                $reflection=new \ReflectionClass($class);
                if($reflection->getConstructor()?->getNumberOfRequiredParameters()>0)throw new \LogicException('Definition providers must be declarative services without required constructor arguments: '.$id);
                $items[]=$reflection->newInstance();
            }
            return $items;
        };
        $services=static function(string $tag,string $interface)use($container):array {
            $items=[];
            foreach($container->findTaggedServiceIds($tag) as $id=>$tags){
                $class=$container->getParameterBag()->resolveValue($container->findDefinition($id)->getClass());
                if(!is_a($class,$interface,true))throw new \LogicException('Invalid extension service '.$id);
                $items[$id]=(new \ReflectionClass($class))->newInstanceWithoutConstructor();
            }
            return $items;
        };
        new Registry($collect('schema_manager.type_provider'),$collect('schema_manager.relationship_provider'),$services('schema_manager.source_adapter',Api\SourceAdapterInterface::class),
            $services('schema_manager.type_mapper',Api\EntityTypeMapperInterface::class),$services('schema_manager.type_validator',Api\EntityTypeValidatorInterface::class));
    }
}
