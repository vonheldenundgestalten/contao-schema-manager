<?php
declare(strict_types=1);
namespace SchemaManagerExample;
use VHUG\SchemaManagerBundle\Extension\V1 as Api;
/** Synthetic complex equipment; not project-specific and never auto-registered. */
final class ExampleProvider implements Api\TypeProviderInterface,Api\RelationshipProviderInterface,Api\EntityTypeMapperInterface
{
    public function types(): iterable
    {
        yield new Api\TypeDefinition('example.equipment','Example equipment',['ProductModel'],self::class,
            fields:[new Api\FieldDefinition('name',required:true),new Api\FieldDefinition('description',localized:true,widget:'textarea'),new Api\FieldDefinition('capacity','measurement',widget:'measurement'),new Api\FieldDefinition('notes',widget:'textarea')],
            relationships:['example.manufacturer','example.related'],properties:['name','description','additionalProperty']);
    }
    public function relationships(): iterable
    {
        yield new Api\RelationshipDefinition('example.manufacturer','Manufacturer',['example.equipment'],['Organization'],property:'manufacturer',multiple:false);
        yield new Api\RelationshipDefinition('example.related','Related equipment',['example.equipment'],['example.equipment'],allowCycles:false);
    }
    public function map(Api\ResolvedEntity $entity,Api\MappingContext $context): Api\NodeContribution
    {
        $properties=array_intersect_key($entity->fields,array_flip(['name','description']));
        if($measure=$entity->fields['capacity']??null)$properties['additionalProperty']=$context->measurement($measure['value'],$measure['unit'],$measure['basis']);
        return new Api\NodeContribution($properties);
    }
}
