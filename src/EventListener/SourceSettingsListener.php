<?php
declare(strict_types=1);
namespace VHUG\SchemaManagerBundle\EventListener;
use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\DataContainer;
use Contao\CoreBundle\DataContainer\PaletteManipulator;
use Doctrine\DBAL\Connection;

final class SourceSettingsListener
{
    public function __construct(private readonly Connection $connection) {}
    public function people(): array
    {
        return $this->connection->fetchAllKeyValue("SELECT id,name FROM tl_schema_entity WHERE entityType='Person' ORDER BY name");
    }
    public function entities(): array
    {
        return $this->connection->fetchAllKeyValue('SELECT id,name FROM tl_schema_entity ORDER BY name');
    }
    #[AsCallback(table:'tl_news',target:'config.onload')]
    public function newsPalette(DataContainer $dc): void
    {
        $archive = $this->connection->fetchAssociative(
            'SELECT a.* FROM tl_news_archive a JOIN tl_news n ON n.pid=a.id WHERE n.id=?', [$dc->id]
        );
        if (!$archive || !in_array($archive['schemaType'], ['Article','NewsArticle','BlogPosting','JobPosting'], true)) { return; }
        foreach (array_keys($GLOBALS['TL_DCA']['tl_news']['palettes']) as $palette) {
            if ($palette === '__selector__' || str_contains($GLOBALS['TL_DCA']['tl_news']['palettes'][$palette], 'schemaIdentity')) { continue; }
            PaletteManipulator::create()->addLegend('schema_legend','title_legend',PaletteManipulator::POSITION_AFTER)
                ->addField($archive['schemaType'] === 'JobPosting' ? array_merge(['schemaJobEmployer', 'schemaJobValidThrough'], array_keys(\VHUG\SchemaManagerBundle\Schema\JobFields::defaults()), ['schemaIdentity']) : ['schemaAuthor','schemaAbout','schemaMentions','schemaDateModified','schemaIdentity'],'schema_legend',PaletteManipulator::POSITION_APPEND)
                ->applyToPalette($palette,'tl_news');
        }
    }
    #[AsCallback(table:'tl_news',target:'config.onsubmit')]
    public function newsIdentity(DataContainer $dc): void { $this->ensureNewsIdentity((int) $dc->id); }
    public function ensureNewsIdentity(int $id): void
    {
        $row = $this->connection->fetchAssociative(
            'SELECT n.schemaIdentity,a.schemaType,e.identityBase FROM tl_news n JOIN tl_news_archive a ON a.id=n.pid JOIN tl_schema_entity e ON e.id=a.schemaPublisher WHERE n.id=?',
            [$id]
        );
        if ($row && !$row['schemaIdentity']) {
            $this->connection->update('tl_news',['schemaIdentity'=>$row['identityBase'].'/#'.($row['schemaType'] === 'JobPosting' ? 'job' : 'article').'-'.bin2hex(random_bytes(16))],['id'=>$id]);
        }
    }
    #[AsCallback(table:'tl_news_archive',target:'config.onsubmit')]
    public function archiveSaved(DataContainer $dc): void
    {
        foreach ($this->connection->fetchFirstColumn('SELECT id FROM tl_news WHERE pid=?',[$dc->id]) as $id) { $this->ensureNewsIdentity((int) $id); }
    }
    #[AsCallback(table:'tl_page',target:'config.onsubmit')]
    public function websiteIdentity(DataContainer $dc): void
    {
        $row = $this->connection->fetchAssociative('SELECT p.type,p.schemaWebsiteId,e.identityBase FROM tl_page p JOIN tl_schema_entity e ON e.id=p.schemaPublisher WHERE p.id=?',[$dc->id]);
        if ($row && $row['type']==='root' && !$row['schemaWebsiteId']) {
            $this->connection->update('tl_page',['schemaWebsiteId'=>$row['identityBase'].'/#website-'.bin2hex(random_bytes(16))],['id'=>$dc->id]);
        }
    }
    #[AsCallback(table:'tl_news',target:'fields.schemaIdentity.save')]
    public function keepNewsIdentity(mixed $value, DataContainer $dc): string
    {
        return (string) $this->connection->fetchOne('SELECT schemaIdentity FROM tl_news WHERE id=?',[$dc->id]);
    }
    #[AsCallback(table:'tl_page',target:'fields.schemaWebsiteId.save')]
    public function keepWebsiteIdentity(mixed $value, DataContainer $dc): string
    {
        $row=$this->connection->fetchAssociative('SELECT schemaWebsiteId,schemaWebsiteRoot FROM tl_page WHERE id=?',[$dc->id]);
        $value=trim((string)$value);
        if(!empty($row['schemaWebsiteRoot'])){
            if($value!==($row['schemaWebsiteId']??''))throw new \InvalidArgumentException('Edit the website ID on the shared website root #'.$row['schemaWebsiteRoot'].'.');
            return $value;
        }
        if($value===''){
            if(!empty($row['schemaWebsiteId']))throw new \InvalidArgumentException('Enter the original website ID instead of clearing an existing identity.');
            return '';
        }
        if(strlen($value)>255)throw new \InvalidArgumentException('Website IDs may contain at most 255 characters.');
        return (new BusinessDetailsListener($this->connection))->url($value);
    }
}
