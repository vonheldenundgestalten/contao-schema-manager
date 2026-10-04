<?php
declare(strict_types=1);
namespace VHUG\SchemaManagerBundle\Backend;
use Contao\BackendTemplate;
use Contao\BackendUser;
use Contao\StringUtil;
use Contao\System;
use Doctrine\DBAL\Connection;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use VHUG\SchemaManagerBundle\Schema\RelationshipMap;

final class RelationshipMapModule
{
    public function __construct(private readonly Connection $connection, private readonly RelationshipMap $map, private readonly ContentMapSource $content, private readonly \VHUG\SchemaManagerBundle\Schema\ContentRelationshipMap $contentMap) {}
    public function generate(): string
    {
        $user = BackendUser::getInstance();
        if (!$user->isAdmin && !$user->hasAccess('schema_manager', 'modules')) { throw new AccessDeniedException(); }
        System::loadLanguageFile('schema_graph');
        $rows = $this->connection->fetchAllAssociative('SELECT * FROM tl_schema_entity ORDER BY name,id');
        foreach ($rows as &$row) {
            foreach (['memberOf','workLocation','locations','subservices','knowledgeTopics'] as $field) { $row[$field] = StringUtil::deserialize($row[$field] ?? null, true); }
        }
        unset($row);
        // Content source applies Contao page/archive permissions before exposing content nodes.
        $homes = $this->connection->fetchAllAssociative('SELECT pid,language,page,published,name,isMainEntity FROM tl_schema_translation ORDER BY language,id');
        $GLOBALS['TL_CSS'][] = 'bundles/schemamanager/relationship-map.css';
        $GLOBALS['TL_JAVASCRIPT'][] = 'bundles/schemamanager/vendor/cytoscape/cytoscape.min.js';
        foreach (['layout-base','cose-base','cytoscape-fcose'] as $library) { $GLOBALS['TL_JAVASCRIPT'][] = 'bundles/schemamanager/vendor/'.$library.'/'.$library.'.js'; }
        $GLOBALS['TL_JAVASCRIPT'][] = 'bundles/schemamanager/relationship-map.js';
        $template = new BackendTemplate('be_schema_relationships');
        $template->labels = $GLOBALS['TL_LANG']['schema_graph'];
        $content = $this->content->load($user);
        $elements = $this->contentMap->extend($this->map->build($rows,$homes), $content['pages'], $content['news'], $homes);
        $template->payload = json_encode(['elements'=>$elements, 'labels'=>$template->labels], JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_THROW_ON_ERROR);
        return $template->parse();
    }
}
