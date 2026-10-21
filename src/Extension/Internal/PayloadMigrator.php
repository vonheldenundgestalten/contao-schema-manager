<?php
declare(strict_types=1);
namespace VHUG\SchemaManagerBundle\Extension\Internal;
use Doctrine\DBAL\Connection;
use VHUG\SchemaManagerBundle\Extension\V1 as Api;
final class PayloadMigrator
{
    public function __construct(private readonly Connection $connection,private readonly Registry $registry,private readonly Store $store,private readonly FieldValidator $fields,private readonly Api\SourceInvalidatorInterface $invalidator,private readonly iterable $migrations=[]) {}
    public function migrate(int $id,bool $apply=false): array
    {
        $row=$this->store->record($id);$type=$row?$this->registry->type($row['entityType']):null;
        if(!$type)throw new \InvalidArgumentException('Extension type unavailable.');
        $version=(int)$row['schemaExtensionVersion'];$original=$version;
        $global=Store::decode($row['schemaExtensionData']??null);$localized=[];
        foreach($this->connection->fetchAllAssociative('SELECT * FROM tl_schema_translation WHERE pid=?',[$id]) as $translation)$localized[(int)$translation['id']]=['language'=>$translation['language'],'values'=>Store::decode($translation['schemaExtensionData']??null)];
        while($version<$type->schemaVersion) {
            $matches=[];foreach($this->migrations as $migration)if($migration->typeKey()===$type->key && $migration->fromVersion()===$version)$matches[]=$migration;
            if(count($matches)!==1 || $matches[0]->toVersion()!==$version+1)throw new \InvalidArgumentException('Missing/ambiguous consecutive migration for '.$type->key.' version '.$version);
            $migration=$matches[0];$global=$migration->global($global);
            foreach($localized as &$translation)$translation['values']=$migration->localized($translation['values'],$translation['language']);unset($translation);
            $version++;
        }
        if($version!==$type->schemaVersion)throw new \InvalidArgumentException('Cannot downgrade supplementary data.');
        $partial=new Api\TypeDefinition($type->key,$type->label,$type->schemaTypes,$type->mapper,array_map(static fn($f)=>new Api\FieldDefinition($f->key,$f->type,$f->localized,$f->multiple,false,$f->constraints,$f->default,$f->widget,$f->ai),$type->fields));
        if($localized)foreach($localized as $translation)$this->fields->validate($partial,$global,$translation['values']);
        else $this->fields->validate($partial,$global,[]);
        $plan=['entity'=>$id,'from'=>$original,'to'=>$version,'global'=>$global,'localized'=>$localized,'applied'=>false];
        if(!$apply || $original===$version)return $plan;
        $this->connection->transactional(function()use($id,$row,$version,$global,$localized){
            if($this->connection->executeStatement('UPDATE tl_schema_entity SET schemaExtensionData=?,schemaExtensionVersion=?,schemaExtensionRevision=schemaExtensionRevision+1 WHERE id=? AND schemaExtensionRevision=? AND schemaExtensionVersion=?',[Store::encode($global),$version,$id,(int)$row['schemaExtensionRevision'],(int)$row['schemaExtensionVersion']])!==1)throw new \InvalidArgumentException('Entity changed during migration.');
            foreach($localized as $key=>$translation)$this->connection->update('tl_schema_translation',['schemaExtensionData'=>Store::encode($translation['values'])],['id'=>$key]);
        });
        $this->invalidator->adapterChanged('schema_manager.migration');$plan['applied']=true;
        return $plan;
    }
}
