<?php
declare(strict_types=1);
namespace VHUG\SchemaManagerBundle\EventListener;
use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\CoreBundle\Routing\ResponseContext\ResponseContext;
use Contao\CoreBundle\Routing\ResponseContext\JsonLd\JsonLdManager;
use Contao\DataContainer;
use Doctrine\DBAL\Connection;
use VHUG\SchemaManagerBundle\Schema\EntityGraph;

final class PreviewListener
{
    public function __construct(private readonly Connection $connection, private readonly EntityGraph $entities) {}
    #[AsCallback(table:'tl_schema_translation',target:'fields.schemaPreview.load')]
    public function preview(mixed $value, DataContainer $dc): string
    {
        $row=$this->connection->fetchAssociative('SELECT * FROM tl_schema_translation WHERE id=?',[$dc->id]);
        if (!$row || !$row['language']) { return ''; }
        $manager=new JsonLdManager(new ResponseContext());
        $emitted=[];
        $this->entities->emit((int)$row['pid'],$row['language'],$manager,$emitted);
        return json_encode($manager->getGraphForSchema(JsonLdManager::SCHEMA_ORG)->toArray(),JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR);
    }
}
