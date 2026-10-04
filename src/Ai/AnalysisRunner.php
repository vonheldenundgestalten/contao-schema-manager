<?php
declare(strict_types=1);
namespace VHUG\SchemaManagerBundle\Ai;
use Contao\BackendUser;
use Doctrine\DBAL\Connection;
final class AnalysisRunner
{
    public function __construct(private readonly Connection $db,private readonly RunStore $store,private readonly SiteInventory $inventory,private readonly OpenAiProvider $provider,private readonly ProposalEngine $proposals,private readonly PublicTextFetcher $fetcher) {}
    public function start(BackendUser $user,int $root,string $mode,string $origin,bool $changed): int
    {
        if (!in_array($mode,['discover','improve'],true)) { throw new \InvalidArgumentException('Choose an analysis action.'); }
        $origin=rtrim(trim($origin),'/');
        if (!preg_match('~^https://[a-z0-9.-]+(?::[0-9]+)?$~iD',$origin) || strlen($origin)>180) { throw new \InvalidArgumentException('Enter the public HTTPS identity origin, without a path.'); }
        $inventory=$this->inventory->collect($root,$user);$previous=$this->store->previous((int)$user->id,$root);
        $queue=[];
        foreach ($inventory['sources'] as $key=>$source) { if (!$changed || ($previous['hashes'][$mode.':'.$key] ?? '')!==$source['hash']) { $queue[]=$key; } }
        return $this->store->create((int)$user->id,$root,['root'=>$root,'mode'=>$mode,'origin'=>$origin,'inventory'=>$inventory,'queue'=>$queue,'processed'=>[],
            'editorLanguage'=>$GLOBALS['TL_LANGUAGE'] ?? 'en','proposals'=>[],'mapped'=>[],'decisions'=>$previous['decisions'],'status'=>$queue?'ready':'complete','usage'=>['input_tokens'=>0,'output_tokens'=>0],'warnings'=>[],'createdAt'=>time()]);
    }
    /** A refinement is a separate proposal set. Never overwrite the source review. */
    public function refine(int $id,BackendUser $user,string $feedback): int
    {
        $feedback=trim($feedback);
        if ($feedback==='' || mb_strlen($feedback)>4000) { throw new \InvalidArgumentException('Enter feedback between 1 and 4000 characters.'); }
        $original=$this->store->get($id,(int)$user->id);
        if ($original['status']!=='complete') { throw new \RuntimeException('Finish this analysis before refining it.'); }
        foreach($original['proposals'] as $p){if($p['status']==='applied')throw new \RuntimeException('Some suggestions have been applied. Start a fresh analysis to refine the current schema.');}
        $run=$original;
        $run['refinementOf']=$id;$run['editorFeedback']=$feedback;$run['editorLanguage']=$GLOBALS['TL_LANGUAGE'] ?? 'en';
        $history=$original['conversation'] ?? [];
        if(!empty($original['explanation']))$history[]=['role'=>'assistant','text'=>$original['explanation']];
        $history[]=['role'=>'user','text'=>$feedback];$run['conversation']=array_slice($history,-10);
        $run['previousSuggestions']=array_values(array_map(static fn($p)=>array_intersect_key($p,array_flip(['action','target','field','value','source','quote','reason'])),array_filter($original['proposals'],static fn($p)=>$p['status']==='pending')));
        foreach($original['proposals'] as $p){if($p['status']==='rejected')$run['decisions'][$p['fingerprint']]='rejected';}
        $run['proposals']=[];$run['mapped']=[];$run['processed']=[];$run['warnings']=[];$run['explanation']='';
        $run['queue']=array_keys($run['inventory']['sources']);$run['status']='ready';$run['usage']=['input_tokens'=>0,'output_tokens'=>0];$run['createdAt']=time();
        unset($run['claim'],$run['workingAt'],$run['reviewNotes']);
        $newId=$this->store->create((int)$user->id,(int)$run['root'],$run);
        $this->step($newId,$user);
        return $newId;
    }
    public function step(int $id,BackendUser $user): array
    {
        $owner=(int)$user->id;
        $this->db->beginTransaction();
        try {
            $run=$this->store->get($id,$owner,true);
            if ($run['status']==='complete') { $this->db->commit();return ['done'=>true]; }
            if ($run['status']==='working' && time()-($run['workingAt'] ?? time())<180) { throw new \RuntimeException('This batch is already being processed.'); }
            if (!in_array($run['status'],['ready','paused','working'],true)) { throw new \RuntimeException('This run cannot continue.'); }
            $run['status']='working';$run['workingAt']=time();$claim=bin2hex(random_bytes(8));$run['claim']=$claim;$this->store->save($id,$owner,$run);$this->db->commit();
        } catch (\Throwable $e) { $this->db->rollBack();throw $e; }
        $batch=isset($run['refinementOf'])?$run['queue']:array_slice($run['queue'],0,3);$sources=array_intersect_key($run['inventory']['sources'],array_flip($batch));
        try {
            @set_time_limit(120);
            if(!isset($run['refinementOf'])){foreach($sources as $key=>$source){$sources[$key]=$this->fetcher->enrich($source);$run['inventory']['sources'][$key]=$sources[$key];}}
            $result=$this->provider->analyze($this->proposals->context($run,$sources));
            $this->proposals->ingest($run,$result['suggestions'],$sources);
            if(!empty($result['explanation'])){$run['explanation']=trim(($run['explanation'] ?? '')."\n\n".$result['explanation']);}
            foreach (['input_tokens','output_tokens'] as $field) { $run['usage'][$field]+=(int)($result['usage'][$field] ?? 0); }
            if ($result['warning']) { $run['warnings'][]=$result['warning'];$run['status']='paused'; }
            else {
                foreach($sources as $key=>$source){$run['processed'][$run['mode'].':'.$key]=$source['hash'];}
                $run['queue']=array_slice($run['queue'],count($batch));$run['status']=$run['queue']?'ready':'complete';
            }
        } catch (\Throwable $e) { $run['status']='paused';$run['warnings'][]=$e instanceof \RuntimeException?$e->getMessage():'Analysis failed; no schema changes were applied.'; }
        $this->db->beginTransaction();
        try {
            $latest=$this->store->get($id,$owner,true);
            if (($latest['claim'] ?? null)!==$claim) { throw new \RuntimeException('Run state changed; reload.'); }
            $this->store->save($id,$owner,$run);$this->db->commit();
        } catch (\Throwable $e) { $this->db->rollBack();throw $e; }
        return ['done'=>$run['status']==='complete','paused'=>$run['status']==='paused','remaining'=>count($run['queue']),'total'=>count($run['inventory']['sources']),'suggestions'=>count($run['proposals']),'message'=>end($run['warnings']) ?: ''];
    }
}
