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
    #[AsCallback(table: 'tl_schema_translation', target: 'fields.schemaPreview.input_field')]
    public function render(DataContainer $dc): string
    {
        $label = $GLOBALS['TL_LANG']['tl_schema_translation']['schemaPreview'];
        $escape = static fn (string $text): string => htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        // Natural block height also works when the containing legend starts collapsed.
        return '<div class="widget clr">'
            .'<h3 id="schema_preview_label">'.$escape($label[0]).'</h3>'
            .'<pre id="ctrl_schemaPreview" aria-labelledby="schema_preview_label" style="white-space:pre-wrap;overflow-wrap:anywhere;height:auto;max-height:none;line-height:1.5;padding:12px;border:1px solid currentColor;border-radius:4px;box-sizing:border-box;">'
            .$escape($this->preview(null, $dc)).'</pre>'
            .'<p class="tl_help">'.$escape($label[1]).'</p></div>';
    }

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
