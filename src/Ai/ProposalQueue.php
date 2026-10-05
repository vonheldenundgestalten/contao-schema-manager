<?php
declare(strict_types=1);
namespace VHUG\SchemaManagerBundle\Ai;

/** Plans prerequisites before any writes; rejected/invalid suggestions are never revived. */
final class ProposalQueue
{
    public static function label(array $p,array $run): string
    {
        $target=explode('@',$p['target'],2)[0];$row=$run['inventory']['records'][$target]??[];
        $name=$row['name']??$row['headline']??$row['title']??$target;$type=$row['entityType']??$row['_mode']??'';
        foreach($run['proposals'] as $candidate)if($candidate['action']==='create'&&$candidate['target']===$target){$name=$candidate['value'];$type=$candidate['field'];break;}
        $page=str_contains($p['target'],'@')?(int)substr(strrchr($p['target'],'@'),1):($p['action']==='home'?(int)$p['value']:0);
        $locale=$page?' / '.($run['inventory']['pages'][$page]['language']??'').' / '.($run['inventory']['pages'][$page]['title']??('page #'.$page)):'';
        return trim($type.' "'.$name.'"').$locale.' — '.$p['action'].' '.$p['field'];
    }
    private static function missingHome(string $key,array $run): string
    {
        if(!preg_match('/^(.+)@([0-9]+)$/D',$key,$match))return 'The required entity has not been created or linked. Review its creation suggestion.';
        $target=$match[1];$page=(int)$match[2];$language=$run['inventory']['pages'][$page]['language']??'';
        $title=$run['inventory']['pages'][$page]['title']??('page #'.$page);
        $prefix='The '.strtoupper($language).' language record for "'.$title.'" is not available. ';
        $same=[];$other=[];
        foreach($run['proposals'] as $p){
            if($p['action']!=='home'||$p['target']!==$target)continue;
            if((int)$p['value']===$page)$same[]=$p;
            elseif(($run['inventory']['pages'][(int)$p['value']]['language']??null)===$language&&!in_array($p['status'],['invalid','rejected'],true))$other[]=$p;
        }
        if($other){$titles=array_map(static fn($p)=>$run['inventory']['pages'][(int)$p['value']]['title']??('page #'.$p['value']),$other);return $prefix.'This entity already has a language-home suggestion for "'.implode('", "',$titles).'". Review which page should represent it; this description was proposed for a different page.';}
        foreach($same as $p)if($p['status']==='rejected')return $prefix.'Its language-home creation was rejected. It will not be restored automatically.';
        foreach($same as $p)if($p['status']==='invalid')return $prefix.'Its language-home suggestion could not be used: '.($p['error']??'invalid suggestion');
        if($same)return $prefix.'Its creation could not be resolved uniquely. Review the language-home suggestions for this entity.';
        return $prefix.'The AI proposed a description but omitted the language-home creation. Add this language record to the entity, then run improvement analysis to fill it. This description remains pending; other applicable suggestions can still be applied.';
    }
    public static function plan(array $run,array $selected): array
    {
        $providers=[];$ready=array_fill_keys(array_keys($run['inventory']['records']),true);
        foreach($run['mapped']??[] as $key=>$id)if($id)$ready[$key]=true;
        foreach($run['inventory']['records'] as $key=>$row)if(str_starts_with($key,'translation:'))$ready['entity:'.$row['pid'].'@'.$row['page']]=true;
        foreach($run['proposals'] as $i=>$p){
            if($p['status']!=='pending')continue;
            $key=match($p['action']){'create'=>$p['target'],'home'=>$p['target'].'@'.$p['value'],default=>null};
            if($key!==null)$providers[$key][]=$i;
        }
        $requirements=static function(array $p):array{
            $keys=$p['action']==='create'?[]:[$p['target']];
            if($p['action']==='add')$keys[]=$p['value'];
            return array_unique($keys);
        };
        $pending=[];$visit=function(int $i)use(&$visit,&$pending,$run,$ready,$providers,$requirements):void{
            if(isset($pending[$i]))return;
            $p=$run['proposals'][$i]??null;
            if(!$p||$p['status']!=='pending')throw new \RuntimeException('Selected suggestion #'.($i+1).' is no longer pending. Reload the review.');
            $pending[$i]=true;
            foreach($requirements($p) as $key)if(!isset($ready[$key])&&count($providers[$key]??[])===1)$visit($providers[$key][0]);
        };
        foreach(array_unique(array_map('intval',$selected)) as $i)$visit($i);
        $order=[];
        do{
            $progress=false;
            foreach(array_keys($pending) as $i){
                $p=$run['proposals'][$i];$missing=array_filter($requirements($p),static fn($key)=>!isset($ready[$key]));
                if($missing)continue;
                $order[]=$i;unset($pending[$i]);$progress=true;
                if($p['action']==='create')$ready[$p['target']]=true;
                if($p['action']==='home')$ready[$p['target'].'@'.$p['value']]=true;
            }
        }while($progress&&$pending);
        $blocked=[];
        foreach(array_keys($pending) as $i){
            $missing=array_values(array_filter($requirements($run['proposals'][$i]),static fn($key)=>!isset($ready[$key])));
            $blocked[$i]=self::label($run['proposals'][$i],$run).': '.implode(' ',array_map(static fn($key)=>self::missingHome($key,$run),$missing));
        }
        return ['order'=>$order,'blocked'=>$blocked];
    }
}
