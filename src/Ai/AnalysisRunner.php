<?php
declare(strict_types=1);
namespace VHUG\SchemaManagerBundle\Ai;
use Contao\BackendUser;
use Doctrine\DBAL\Connection;
final class AnalysisRunner
{
    public function __construct(private readonly Connection $db,private readonly RunStore $store,private readonly SiteInventory $inventory,private readonly OpenAiProvider $provider,private readonly ProposalEngine $proposals,private readonly PublicTextFetcher $fetcher,private readonly SchemaAudit $audit) {}
    public function start(BackendUser $user,int $root,string $mode,string $origin,bool $changed,bool $multilingual=true,string $stage='content'): int
    {
        if(!in_array($stage,['foundation','content','audit'],true))throw new \InvalidArgumentException('Choose a setup stage.');
        if (!in_array($mode,['discover','improve'],true)) { throw new \InvalidArgumentException('Choose an analysis action.'); }
        if($stage==='audit'){$mode='improve';$origin='https://schema-audit.invalid';$changed=false;}
        $origin=rtrim(trim($origin),'/');
        if (!preg_match('~^https://[a-z0-9.-]+(?::[0-9]+)?$~iD',$origin) || strlen($origin)>180) { throw new \InvalidArgumentException('Enter the public HTTPS identity origin, without a path.'); }
        $inventory=$this->inventory->collect($root,$user,$multilingual);$previous=$this->store->previous((int)$user->id,$root);
        if($stage==='foundation'){$inventory['sources']=array_filter($inventory['sources'],static fn($s)=>str_starts_with($s['id'],'page:'));$changed=false;}
        $queue=[];
        // Root grouping is part of the scan scope; a new multilingual scan must not
        // skip all the primary-language context because a single-language run exists.
        if($multilingual && count($inventory['roots'])>1)$changed=false;
        foreach ($inventory['sources'] as $key=>$source) { if (!$changed || ($previous['hashes'][($stage==='foundation'?'foundation:':'').$mode.':'.$key] ?? '')!==$source['hash']) { $queue[]=$key; } }
        return $this->store->create((int)$user->id,$root,['audit'=>$stage==='audit','auditResults'=>[],'root'=>$root,'stage'=>$stage,'mode'=>$mode,'origin'=>$origin,'inventory'=>$inventory,'queue'=>$queue,'processed'=>[],
            'localizationQueue'=>[],'localizationPrepared'=>false,'editorLanguage'=>$GLOBALS['TL_LANGUAGE'] ?? 'en','proposals'=>[],'mapped'=>[],'decisions'=>$previous['decisions'],'status'=>$queue?'ready':'complete','usage'=>['input_tokens'=>0,'output_tokens'=>0],'warnings'=>[],'createdAt'=>time()]);
    }
    /** A refinement is a separate proposal set. Never overwrite the source review. */
    public function refine(int $id,BackendUser $user,string $feedback): int
    {
        $feedback=trim($feedback);
        if ($feedback==='' || mb_strlen($feedback)>4000) { throw new \InvalidArgumentException('Enter feedback between 1 and 4000 characters.'); }
        $original=$this->store->get($id,(int)$user->id);
        if(!empty($original['configuration'])||!empty($original['audit']))throw new \RuntimeException('Prepare a new parent review to change configuration.');
        if ($original['status']!=='complete') { throw new \RuntimeException('Finish this analysis before refining it.'); }
        foreach($original['proposals'] as $p){if($p['status']==='applied')throw new \RuntimeException('Some suggestions have been applied. Start a fresh analysis to refine the current schema.');}
        $run=$original;
        $run['refinementOf']=$id;$run['editorFeedback']=$feedback;$run['editorLanguage']=$GLOBALS['TL_LANGUAGE'] ?? 'en';
        $history=$original['conversation'] ?? [];
        if(!empty($original['explanation']))$history[]=['role'=>'assistant','text'=>$original['explanation']];
        $history[]=['role'=>'user','text'=>$feedback];$run['conversation']=array_slice($history,-10);
        $run['previousSuggestions']=array_values(array_map(static fn($p)=>array_intersect_key($p,array_flip(['action','target','field','value','source','quote','reason'])),array_filter($original['proposals'],static fn($p)=>$p['status']==='pending')));
        foreach($original['proposals'] as $p){if($p['status']==='rejected')$run['decisions'][$p['fingerprint']]='rejected';}
        $run['localizationQueue']=[];$run['localizationPrepared']=false;
        $run['proposals']=[];$run['mapped']=[];$run['processed']=[];$run['warnings']=[];$run['explanation']='';
        $run['queue']=array_keys($run['inventory']['sources']);$run['status']='ready';$run['usage']=['input_tokens'=>0,'output_tokens'=>0];$run['createdAt']=time();
        unset($run['claim'],$run['workingAt'],$run['reviewNotes']);
        $newId=$this->store->create((int)$user->id,(int)$run['root'],$run);
        $this->step($newId,$user);
        return $newId;
    }
    public function step(int $id,BackendUser $user,?string $expectedCursor=null): array
    {
        $owner=(int)$user->id;
        $this->db->beginTransaction();
        try {
            $run=$this->store->get($id,$owner,true);
            if ($run['status']==='complete') { $this->db->commit();return self::progress($run); }
            if ($run['status']==='working' && time()-($run['workingAt'] ?? time())<180) { $this->db->commit();return self::progress($run); }
            // A late duplicate must not start the next batch after the first completes.
            if ($expectedCursor!==null && !hash_equals(self::cursor($run),$expectedCursor)) { $this->db->commit();return self::progress($run); }
            if (!in_array($run['status'],['ready','paused','working'],true)) { throw new \RuntimeException('This run cannot continue.'); }
            $run['status']='working';$run['workingAt']=time();$claim=bin2hex(random_bytes(8));$run['claim']=$claim;$this->store->save($id,$owner,$run);$this->db->commit();
        } catch (\Throwable $e) { $this->db->rollBack();throw $e; }
        $localizing=empty($run['queue']) && !empty($run['localizationQueue']);
        $task=$localizing?$run['localizationQueue'][0]:null;
        $batch=(isset($run['refinementOf'])||($run['stage']??'')==='foundation')?$run['queue']:array_slice($run['queue'],0,3);
        if($localizing){$sources=array_filter($run['inventory']['sources'],static fn($source)=>in_array($source['page'],$task['pages'],true));}
        else {
            // Include the remaining members of each page's language family in this batch.
            $families=[];foreach($batch as $key){if(!isset($run['inventory']['sources'][$key]))continue;$page=$run['inventory']['sources'][$key]['page'];$families[]=$run['inventory']['pages'][$page]['languageFamily'] ?? $page;}
            foreach($run['queue'] as $key){if(!isset($run['inventory']['sources'][$key]))continue;$page=$run['inventory']['sources'][$key]['page'];if(empty($run['audit'])&&in_array($run['inventory']['pages'][$page]['languageFamily'] ?? $page,$families,true)&&!in_array($key,$batch,true))$batch[]=$key;}
            $sources=array_intersect_key($run['inventory']['sources'],array_flip($batch));
        }
        try {
            @set_time_limit(120);
            if(!empty($run['audit'])){
                $fresh=$this->inventory->collect((int)$run['root'],$user,!empty($run['inventory']['multilingual']));
                foreach($batch as $key){
                    if(isset($fresh['sources'][$key]))$run['auditResults'][$key]=$this->audit->inspect($fresh['sources'][$key]);
                    $run['processed'][$key]='audited';
                }
                $run['queue']=array_values(array_diff($run['queue'],$batch));$run['status']=$run['queue']?'ready':'complete';
            }else{
            // Re-read publication state and text: prepared snapshots and cached HTML
            // must never resurrect content that an editor has since disabled.
            $fresh=$this->inventory->collect((int)$run['root'],$user,!empty($run['inventory']['multilingual']));
            if(($run['stage']??'')==='foundation')$fresh['sources']=array_filter($fresh['sources'],static fn($s)=>str_starts_with($s['id'],'page:'));
            $sources=array_intersect_key($fresh['sources'],$sources);
            $run['sourceTotal'] ??= count($run['inventory']['sources']);
            $run['inventory']=$fresh;
            foreach($run['proposals'] as &$proposal){if($proposal['status']==='pending'&&!isset($fresh['sources'][$proposal['source']])){$proposal['status']='invalid';$proposal['reason']='The source is no longer active.';}}unset($proposal);
            if(isset($run['previousSuggestions']))$run['previousSuggestions']=array_values(array_filter($run['previousSuggestions'],static fn($p)=>isset($fresh['sources'][$p['source']])));
            if(!$sources){$result=['suggestions'=>[],'usage'=>[],'warning'=>null,'explanation'=>''];}
            else {
                $context=$this->proposals->context($run,$sources);
                if(($run['stage']??'')==='foundation'&&!$localizing){
                    $sources=array_filter($fresh['sources'],static fn($s)=>str_starts_with($s['id'],'page:'));
                    foreach($sources as &$source)$source['text']=mb_substr($source['text'],0,2500);unset($source);
                    $context=$this->proposals->context($run,$sources);
                }
                if($localizing){$context['localizationTask']=$task;$context['mode']='localize';}
                $result=$this->provider->analyze($context);
            }
            if($localizing)$run['_localizationTask']=$task;
            $this->proposals->ingest($run,$result['suggestions'],$sources);
            unset($run['_localizationTask']);
            if(!empty($result['explanation'])){$run['explanation']=trim(($run['explanation'] ?? '')."\n\n".$result['explanation']);}
            foreach (['input_tokens','output_tokens'] as $field) { $run['usage'][$field]+=(int)($result['usage'][$field] ?? 0); }
            if ($result['warning']) { $run['warnings'][]=$result['warning'];$run['status']='paused'; }
            else {
                foreach($sources as $key=>$source){$run['processed'][(($run['stage']??'')==='foundation'?'foundation:':'').$run['mode'].':'.$key]=$source['hash'];}
                if($localizing){
                    foreach($task['missingPages'] as $pageId){
                        $complete=false;foreach($run['proposals'] as $proposal){if($proposal['status']==='pending'&&$proposal['action']==='set'&&$proposal['target']===$task['target'].'@'.$pageId&&$proposal['field']==='description'){$complete=true;break;}}
                        if(!$complete)$run['warnings'][]='Translation needs manual review: '.$task['target'].' / '.($run['inventory']['pages'][$pageId]['language'] ?? '').'. No supported description was returned.';
                    }
                    array_shift($run['localizationQueue']);
                }
                else{$run['queue']=array_values(array_diff($run['queue'],$batch));}
                if(!$run['queue'] && empty($run['localizationPrepared'])){
                    $run['localizationQueue']=$this->proposals->localizationTasks($run);$run['localizationPrepared']=true;
                    $run['localizationTotal']=count($run['localizationQueue']);
                }
                $run['status']=($run['queue']||!empty($run['localizationQueue']))?'ready':'complete';
            }
            }
        } catch (\Throwable $e) { unset($run['_localizationTask']);$run['status']='paused';$run['warnings'][]=$e instanceof \RuntimeException?$e->getMessage():'Analysis failed; no schema changes were applied.'; }
        $this->db->beginTransaction();
        try {
            $latest=$this->store->get($id,$owner,true);
            if (($latest['claim'] ?? null)!==$claim) { throw new \RuntimeException('Run state changed; reload.'); }
            $this->store->save($id,$owner,$run);$this->db->commit();
        } catch (\Throwable $e) { $this->db->rollBack();throw $e; }
        return self::progress($run);
    }
    public function status(int $id,BackendUser $user): array
    {
        return self::progress($this->store->get($id,(int)$user->id));
    }
    public static function cursor(array $run): string
    {
        return hash('sha256',json_encode([$run['queue'],$run['localizationQueue'] ?? [],$run['processed'] ?? []],JSON_THROW_ON_ERROR));
    }
    private static function progress(array $run): array
    {
        $expired=$run['status']==='working' && time()-($run['workingAt'] ?? time())>=180;
        return [
            'cursor'=>self::cursor($run),'busy'=>$run['status']==='working'&&!$expired,
            'phase'=>!$run['queue']&&!empty($run['localizationQueue'])?'localize':'analyze',
            'done'=>$run['status']==='complete','paused'=>$run['status']==='paused'||$expired,
            'remaining'=>count($run['queue'])+count($run['localizationQueue'] ?? []),
            'total'=>($run['sourceTotal'] ?? count($run['inventory']['sources']))+($run['localizationTotal'] ?? 0),
            'suggestions'=>count($run['proposals']),
            'message'=>$expired?'The previous batch did not finish. Continuing retries it and may incur additional API usage.':($run['status']==='paused'?(end($run['warnings']) ?: 'Analysis paused.'):''),
        ];
    }
}
