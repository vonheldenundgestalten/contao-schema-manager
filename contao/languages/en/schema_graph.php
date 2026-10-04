<?php
declare(strict_types=1);
$GLOBALS['TL_LANG']['schema_graph'] = [
    'title'=>'Entity relationships',
    'intro'=>'Explore the connections between your managed entities. Select an entity to see its relationships and localized homes.',
    'back'=>'Back to entities',
    'search'=>'Find entities',
    'searchHint'=>'Name, type or location',
    'select'=>'Select an entity',
    'fit'=>'Fit all',
    'reset'=>'Reset view',
    'isolated'=>'Unconnected entities',
    'canvas'=>'Interactive entity graph. Use the entity selector for keyboard access to all entities and relationships.',
    'help'=>'Drag entities to arrange them. Zoom and pan to explore. Selecting an entity labels its incoming and outgoing relationships.',
    'note'=>'Editorial map of saved relationships, including drafts. Dashed borders: unpublished. Amber borders: no entity relationships. Red nodes: missing records. Arrows point from subject to related entity. Publication rules, language and page context can change the actual frontend JSON-LD. Page homes do not count as business relationships.',
    'noJs'=>'Enable JavaScript to explore the relationship map. The entity list remains available.',
    'entities'=>'entities',
    'relations'=>'Relationships',
    'groups'=>'connected groups',
    'published'=>'Published',
    'draft'=>'Unpublished',
    'missing'=>'Referenced record is missing',
    'unconnected'=>'No relationships to other managed entities. This may be intentional; review whether a company, provider or other connection is missing.',
    'edit'=>'Edit entity',
    'homes'=>'Localized homes',
    'page'=>'Page',
];

$GLOBALS['TL_LANG']['schema_graph']['intro'] = 'Explore your websites, pages, posts and managed entities together. Select a node to follow its relationships.';

$GLOBALS['TL_LANG']['schema_graph']['note'] = 'Editorial map of accessible saved records, including drafts. Dashed: unpublished. Orange: no relationships. Red: missing record. Arrows show the direction of each relationship. Frontend publication, language and routing rules still apply. Posts without service links is a separate check: author/publisher links do not count as subjects.';

$GLOBALS['TL_LANG']['schema_graph']['editNews'] = 'Edit news item';

$GLOBALS['TL_LANG']['schema_graph']['editPage'] = 'Edit page settings';

$GLOBALS['TL_LANG']['schema_graph']['withoutService'] = 'Posts without service links';

$GLOBALS['TL_LANG']['schema_graph']['noService'] = 'No direct about/mentions link to a Service. Review whether this post covers a service; a link is not required for every post.';

$GLOBALS['TL_LANG']['schema_graph']['missingIdentity'] = 'No permanent schema identity configured yet.';

$GLOBALS['TL_LANG']['schema_graph']['suppressed'] = 'Schema output is suppressed by the archive setting.';

$GLOBALS['TL_LANG']['schema_graph']['expired'] = 'The job deadline has passed; JobPosting output is suppressed.';

$GLOBALS['TL_LANG']['schema_graph']['unmappedAuthor'] = 'Core author without a shared Person identity. Link the backend author or select a Person in the News item to connect this author to managed people.';

$GLOBALS['TL_LANG']['schema_graph']['language'] = 'Language';

$GLOBALS['TL_LANG']['schema_graph']['allLanguages'] = 'All languages';

$GLOBALS['TL_LANG']['schema_graph']['layout'] = 'Layout';

$GLOBALS['TL_LANG']['schema_graph']['byRelationships'] = 'By relationships';

$GLOBALS['TL_LANG']['schema_graph']['byType'] = 'Grouped by type';

$GLOBALS['TL_LANG']['schema_graph']['scope'] = 'Noindex pages and bare “Require an item” containers are excluded. Actual detail pages remain visible. Counts and relationships reflect the selected language.';

$GLOBALS['TL_LANG']['schema_graph']['editEvent'] = 'Edit event';
