<?php
declare(strict_types=1);
namespace VHUG\SchemaManagerBundle\Extension\Internal;
use Doctrine\DBAL\Connection;
use VHUG\SchemaManagerBundle\Extension\V1 as Api;
final class Store
{
    public function __construct(private readonly Connection $connection) {}
    public function binding(int $id): ?Api\SourceReference
    {
        $row=$this->connection->fetchAssociative('SELECT * FROM tl_schema_extension_binding WHERE entity=?',[$id]);
        return $row?new Api\SourceReference($row['adapterKey'],$row['installationScope'],$row['subjectKey']):null;
    }
    public function relations(int $id,string $language): array
    {
        $result=[];
        foreach ($this->connection->fetchAllAssociative('SELECT * FROM tl_schema_extension_relation WHERE entity=? AND (language=? OR language=?) ORDER BY relationKey,target',[$id,'',$language]) as $row) $result[$row['relationKey']][]=new Api\RelationshipValue(new Api\EntityReference((int)$row['target']),$row['language']?:null,$row['evidence']);
        return $result;
    }
    public static function decode(?string $json): array
    {
        if (!$json) return [];
        $values=json_decode($json,true,32,JSON_THROW_ON_ERROR);
        if (!is_array($values)) throw new \InvalidArgumentException('Invalid supplementary payload.');
        $decode=function($value)use(&$decode){if(!is_array($value))return $value;if(array_keys($value)===['__schemaEntity'])return new Api\EntityReference((int)$value['__schemaEntity']);return array_map($decode,$value);};
        return $decode($values);
    }
    public static function encode(array $values): string
    {
        $encode=function($value)use(&$encode){if($value instanceof Api\EntityReference)return ['__schemaEntity'=>$value->id];if(is_object($value))throw new \InvalidArgumentException('Unsupported supplementary object.');return is_array($value)?array_map($encode,$value):$value;};
        return json_encode($encode($values),JSON_THROW_ON_ERROR);
    }
    public function record(int $id): ?array { return $this->connection->fetchAssociative('SELECT * FROM tl_schema_entity WHERE id=?',[$id])?:null; }
    public function translation(int $id,string $language): ?array { return $this->connection->fetchAssociative('SELECT * FROM tl_schema_translation WHERE pid=? AND language=? ORDER BY id',[$id,$language])?:null; }
}
