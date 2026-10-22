<?php

declare(strict_types=1);
namespace VHUG\SchemaManagerBundle\Extension\Internal;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\DataContainer;
use Contao\Input;
use Doctrine\DBAL\Connection;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/** Source-derived reference rows remain usable by relationships, never by entity editors. */
final class AutomaticSourceListener
{
    public function __construct(private readonly Registry $registry, private readonly Store $store, private readonly Connection $connection) {}

    #[AsCallback(table: 'tl_schema_entity', target: 'config.onload', priority: 1000)]
    #[AsCallback(table: 'tl_schema_translation', target: 'config.onload', priority: 1000)]
    #[AsCallback(table: 'tl_schema_contact', target: 'config.onload', priority: 1000)]
    public function configure(DataContainer $dc): void
    {
        $keys=[];
        foreach ($this->registry->adapters() as $key=>$adapter) if ($adapter->describe()->automatic) $keys[]=$key;
        if (!$keys) return;
        $quoted=implode(',',array_map($this->connection->quote(...),$keys));
        $GLOBALS['TL_DCA']['tl_schema_entity']['list']['sorting']['filter'][]=
            'id NOT IN (SELECT entity FROM tl_schema_extension_binding WHERE adapterKey IN ('.$quoted.'))';
        $id=(int)($dc->id ?: Input::get('tid'));
        if ($dc->table!=='tl_schema_entity') {
            $id=$id?(int)$this->connection->fetchOne('SELECT pid FROM '.$dc->table.' WHERE id=?',[$id]):(int)Input::get('pid');
        }
        if ($id && ($source=$this->store->binding($id)) && in_array($source->adapterKey,$keys,true)) {
            throw new AccessDeniedException('This schema is generated automatically. Edit the original source record.');
        }
    }
}
