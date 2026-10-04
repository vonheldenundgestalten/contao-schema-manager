<?php
declare(strict_types=1);
namespace VHUG\SchemaManagerBundle\Ai;
use Doctrine\DBAL\Connection;
final class RunStore
{
    public function __construct(private readonly Connection $db) {}
    public function create(int $owner,int $root,array $data): int
    {
        $this->db->insert('tl_schema_ai_run',['tstamp'=>time(),'owner'=>$owner,'root'=>$root,'data'=>json_encode($data,JSON_THROW_ON_ERROR)]);
        return (int)$this->db->lastInsertId();
    }
    public function get(int $id,int $owner,bool $lock=false): array
    {
        $row=$this->db->fetchAssociative('SELECT * FROM tl_schema_ai_run WHERE id=? AND owner=?'.($lock?' FOR UPDATE':''),[$id,$owner]);
        if (!$row) { throw new \RuntimeException('Analysis run not found for this user.'); }
        return json_decode($row['data'],true,128,JSON_THROW_ON_ERROR);
    }
    public function save(int $id,int $owner,array $data): void { $this->db->update('tl_schema_ai_run',['data'=>json_encode($data,JSON_THROW_ON_ERROR),'tstamp'=>time()],['id'=>$id,'owner'=>$owner]); }
    public function previous(int $owner,int $root): array
    {
        $hashes=[];$decisions=[];
        foreach ($this->db->fetchFirstColumn('SELECT data FROM tl_schema_ai_run WHERE owner=? AND root=? ORDER BY id DESC LIMIT 30',[$owner,$root]) as $json) {
            $run=json_decode($json,true,128,JSON_THROW_ON_ERROR);
            $hashes+=($run['processed'] ?? []);
            foreach ($run['proposals'] ?? [] as $p) { if (in_array($p['status'],['applied','rejected'],true)) { $decisions[$p['fingerprint']]=$p['status']; } }
        }
        return ['hashes'=>$hashes,'decisions'=>$decisions];
    }
    public function recent(int $owner): array { return $this->db->fetchAllAssociative('SELECT id,tstamp,root FROM tl_schema_ai_run WHERE owner=? ORDER BY id DESC LIMIT 10',[$owner]); }
}
