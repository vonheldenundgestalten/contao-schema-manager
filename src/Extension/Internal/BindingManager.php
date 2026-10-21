<?php
declare(strict_types=1);
namespace VHUG\SchemaManagerBundle\Extension\Internal;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use VHUG\SchemaManagerBundle\Extension\V1 as Api;
final class BindingManager implements Api\BindingManagerInterface
{
    public function __construct(private readonly Connection $connection,private readonly Registry $registry,private readonly Api\SourceInvalidatorInterface $invalidator,private readonly SiteScope $scopes=new SiteScope()) {}
    public function resolve(Api\SourceReference $source): ?Api\EntityReference
    {
        $id=$this->connection->fetchOne('SELECT entity FROM tl_schema_extension_binding WHERE sourceHash=?',[$source->key()]);
        return $id?new Api\EntityReference((int)$id):null;
    }
    public function bind(Api\BindRequest $request,Api\WriteContext $context): Api\EntityReference
    {
        $context=$this->scopes->write($context);
        $adapter=$this->registry->adapter($request->source->adapterKey);
        if (!$adapter || !in_array($request->typeKey,$adapter->describe()->typeKeys,true)) throw new \InvalidArgumentException('Unavailable source/type.');
        $type=$this->registry->type($request->typeKey);
        if ($type && !$type->sourceBindable) throw new \InvalidArgumentException('Type cannot be source bound.');
        $snapshot=$adapter->resolve($request->source,$context->read)->snapshot;
        if (!$snapshot || $snapshot->source->key()!==$request->source->key() || $snapshot->revision!==$request->expectedRevision || $snapshot->typeKey!==$request->typeKey) throw new \InvalidArgumentException('Source unavailable, incompatible or changed; refresh before binding.');
        if ($request->source->installationScope!==$context->read->site) throw new \InvalidArgumentException('Source outside write scope.');
        try {
            $entity=$this->connection->transactional(function() use($request,$context,$snapshot,$type) {
                if ($existing=$this->resolve($request->source)) {
                    if ($request->existing && $request->existing->id!==$existing->id) throw new \InvalidArgumentException('Source already bound.');
                    return $existing;
                }
                if ($request->existing) {
                    $row=$this->connection->fetchAssociative('SELECT * FROM tl_schema_entity WHERE id=?',[$request->existing->id]);
                    if (!$row || $row['entityType']!==$request->typeKey || $row['identityBase']!==$context->identityOrigin) throw new \InvalidArgumentException('Existing identity is incompatible.');
                    if($this->connection->fetchOne("SELECT id FROM tl_schema_translation WHERE pid=? AND schemaImportedData IS NOT NULL AND schemaImportedData<>'' AND schemaImportedData<>'{}' LIMIT 1",[$request->existing->id]))throw new \InvalidArgumentException('Review and retire imported JSON-LD before switching this identity to source authority.');
                    $id=$request->existing->id;
                } else {
                    $this->connection->insert('tl_schema_entity',['tstamp'=>time(),'name'=>(string)($snapshot->globalFields['name']??$snapshot->localizedFields['name']??$request->source->subjectKey),'entityType'=>$request->typeKey,'identityBase'=>$context->identityOrigin,'entityId'=>$context->identityOrigin.'/#entity-'.bin2hex(random_bytes(16)),'schemaExtensionVersion'=>$type?->schemaVersion??0,'published'=>'']);
                    $id=(int)$this->connection->lastInsertId();
                }
                $this->connection->insert('tl_schema_extension_binding',['tstamp'=>time(),'sourceHash'=>$request->source->key(),'adapterKey'=>$request->source->adapterKey,'installationScope'=>$request->source->installationScope,'subjectKey'=>$request->source->subjectKey,'entity'=>$id]);
                return new Api\EntityReference($id);
            });
        } catch (UniqueConstraintViolationException $e) {
            $entity=$this->resolve($request->source);
            if (!$entity || ($request->existing && $request->existing->id!==$entity->id)) throw new \InvalidArgumentException('Identity already has an authoritative source.',0,$e);
        }
        $this->invalidator->changed($request->source);
        return $entity;
    }
}
