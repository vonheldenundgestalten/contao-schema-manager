<?php
declare(strict_types=1);
namespace VHUG\SchemaManagerBundle\Extension\Internal;
use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\DataContainer;
final class EditorListener
{
    public function __construct(private readonly Registry $registry,private readonly Store $store,private readonly \Doctrine\DBAL\Connection $connection) {}
    #[AsCallback(table:'tl_schema_entity',target:'config.onload')]
    public function configure(DataContainer $dc): void
    {
        foreach($this->registry->types() as $key=>$type) {
            if($type->manual || ($dc->id && ($this->store->record((int)$dc->id)['entityType']??null)===$key))$GLOBALS['TL_DCA']['tl_schema_entity']['fields']['entityType']['options'][]=$key;
            $GLOBALS['TL_DCA']['tl_schema_entity']['fields']['entityType']['reference'][$key]=$GLOBALS['TL_LANG']['MSC'][$type->label]??$type->label;
            $GLOBALS['TL_DCA']['tl_schema_entity']['palettes'][$key]='{identity_legend},name,entityType,identityBase,entityId;{extension_legend},schemaExtensionEditor;{publish_legend},published';
        }
        $row=$dc->id?$this->store->record((int)$dc->id):null;
        if($row){
            $key=$row['entityType'];
            if(str_contains($key,'.') && !$this->registry->type($key)){
                $GLOBALS['TL_DCA']['tl_schema_entity']['fields']['entityType']['options'][]=$key;
                $GLOBALS['TL_DCA']['tl_schema_entity']['fields']['entityType']['reference'][$key]=$key.' (provider unavailable)';
                $GLOBALS['TL_DCA']['tl_schema_entity']['palettes'][$key]='{identity_legend},name,entityType,identityBase,entityId;{extension_legend},schemaExtensionEditor;{publish_legend},published';
            }
            foreach($this->registry->relationships() as $definition)if(in_array($key,$definition->sourceTypes,true) && !str_contains($GLOBALS['TL_DCA']['tl_schema_entity']['palettes'][$key]??'','schemaExtensionEditor'))$GLOBALS['TL_DCA']['tl_schema_entity']['palettes'][$key].=';{extension_legend},schemaExtensionEditor';
        }
        if(!$dc->id || !$source=$this->store->binding((int)$dc->id))return;
        $definition=$this->registry->adapter($source->adapterKey)?->describe();
        foreach($definition?->ownedGlobalFields??['name','entityType'] as $field) if(isset($GLOBALS['TL_DCA']['tl_schema_entity']['fields'][$field])) {
            $GLOBALS['TL_DCA']['tl_schema_entity']['fields'][$field]['eval']['readonly']=true;
            $GLOBALS['TL_DCA']['tl_schema_entity']['fields'][$field]['save_callback'][]=function($value,DataContainer $dc)use($field){return $this->store->record((int)$dc->id)[$field]??null;};
        }
        $GLOBALS['TL_DCA']['tl_schema_entity']['fields']['entityType']['eval']['readonly']=true;
    }
    #[AsCallback(table:'tl_schema_translation',target:'config.onload',priority:-100)]
    public function localized(DataContainer $dc): void
    {
        if(!$dc->id)return;
        $translation=$this->connection->fetchAssociative('SELECT * FROM tl_schema_translation WHERE id=?',[$dc->id]);
        if(!$translation)return;$row=$this->store->record((int)$translation['pid']);
        if($row && str_contains($row['entityType'],'.'))$GLOBALS['TL_DCA']['tl_schema_translation']['palettes']['default']='{home_legend},page,language,isMainEntity;{extension_legend},schemaExtensionEditor;{publish_legend},published;{preview_legend},schemaPreview';
        if(!$source=$this->store->binding((int)$translation['pid']))return;
        $definition=$this->registry->adapter($source->adapterKey)?->describe();
        foreach($definition?->ownedLocalizedFields??['name','description'] as $field)if(isset($GLOBALS['TL_DCA']['tl_schema_translation']['fields'][$field])) {
            $GLOBALS['TL_DCA']['tl_schema_translation']['fields'][$field]['eval']['readonly']=true;
            $GLOBALS['TL_DCA']['tl_schema_translation']['fields'][$field]['save_callback'][]=function($value,DataContainer $dc)use($field){return $this->connection->fetchOne('SELECT '.$this->connection->quoteIdentifier($field).' FROM tl_schema_translation WHERE id=?',[$dc->id]);};
        }
    }
    #[AsCallback(table:'tl_schema_entity',target:'fields.entityType.save')]
    public function type(mixed $value,DataContainer $dc): mixed
    {
        $row=$this->store->record((int)$dc->id);
        if($row && $row['entityType']!==$value && ((int)($row['schemaExtensionVersion']??0)>0 || $this->store->binding((int)$dc->id)))throw new \InvalidArgumentException('Extension type changes require an explicit migration.');
        return $value;
    }
    #[AsCallback(table:'tl_schema_entity',target:'config.onsubmit')]
    public function version(DataContainer $dc): void
    {
        $row=$this->store->record((int)$dc->id);$type=$row?$this->registry->type($row['entityType']):null;
        if($type && !(int)$row['schemaExtensionVersion'])$this->connection->update('tl_schema_entity',['schemaExtensionVersion'=>$type->schemaVersion],['id'=>$dc->id]);
    }
    #[AsCallback(table:'tl_schema_entity',target:'fields.schemaExtensionEditor.input_field')]
    #[AsCallback(table:'tl_schema_translation',target:'fields.schemaExtensionEditor.input_field')]
    public function link(DataContainer $dc): string
    {
        $id=$dc->table==='tl_schema_translation'?(int)$this->connection->fetchOne('SELECT pid FROM tl_schema_translation WHERE id=?',[$dc->id]):(int)$dc->id;
        return '<div class="widget clr"><a href="'.htmlspecialchars(html_entity_decode(\Contao\Backend::addToUrl('key=extensions&entity='.$id.'&act=&table='),ENT_QUOTES),ENT_QUOTES).'">'.htmlspecialchars($GLOBALS['TL_LANG']['MSC']['schemaExtensionEdit']??'Edit extension data').'</a></div>';
    }
}
