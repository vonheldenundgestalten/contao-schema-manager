<?php
declare(strict_types=1);
namespace VHUG\SchemaManagerBundle\EventListener;
use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\DataContainer;
use Contao\PageModel;
use Doctrine\DBAL\Connection;

final class PageSettingsListener
{
    public function __construct(private readonly Connection $connection){}
    public function roots(DataContainer $dc): array
    {
        return $this->connection->fetchAllKeyValue("SELECT id,title FROM tl_page WHERE type='root' AND schemaWebsiteRoot=0 AND id<>? ORDER BY title",[$dc->id]);
    }
    #[AsCallback(table:'tl_page',target:'fields.schemaWebsiteRoot.save')]
    public function websiteRoot(mixed $value, DataContainer $dc): int
    {
        if($value && !array_key_exists((int)$value,$this->roots($dc))){throw new \InvalidArgumentException('Select an independent website root.');}
        if($value && $this->connection->fetchOne('SELECT id FROM tl_page WHERE schemaWebsiteRoot=?',[$dc->id])){
            throw new \InvalidArgumentException('This root is already used as the shared website. Move those references first.');
        }
        return (int)$value;
    }
    #[AsCallback(table:'tl_page',target:'fields.schemaWebsiteHome.save')]
    public function websiteHome(mixed $value, DataContainer $dc): int
    {
        if(!$value){return 0;}
        $page=PageModel::findById($value);
        if(!$page || $page->type!=='regular' || $page->requireItem){throw new \InvalidArgumentException('Select a regular homepage without a reader parameter.');}
        $page->loadDetails();
        if((int)$page->rootId!==(int)$dc->id){throw new \InvalidArgumentException('Select a homepage in this website root.');}
        return (int)$value;
    }
    #[AsCallback(table:'tl_content',target:'config.onload')]
    public function contentPalette(): void
    {
        foreach($GLOBALS['TL_DCA']['tl_content']['palettes'] as $name=>&$palette){
            if(!is_string($palette) || str_contains($palette,'schemaPrimaryImage')){continue;}
            if(str_contains($palette,'singleSRC') || str_contains($palette,'addImage')){
                $palette.=';{schema_image_legend:hide},schemaPrimaryImage';
            }
        }
    }
}
