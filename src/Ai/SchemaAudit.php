<?php
declare(strict_types=1);
namespace VHUG\SchemaManagerBundle\Ai;
use Doctrine\DBAL\Connection;
use Contao\BackendUser;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpClient\NoPrivateNetworkHttpClient;
class SchemaAudit
{
    public function __construct(private readonly Connection $db,private readonly SiteInventory $inventory){}
    public function warnings(): array
    {
        return $this->db->fetchAllAssociative("SELECT t.id,t.language,e.name FROM tl_schema_translation t JOIN tl_schema_entity e ON e.id=t.pid WHERE e.published='1' AND t.published<> '1' ORDER BY e.name,t.language");
    }
    public function fetch(string $url): string
    {
        $p=parse_url($url);if(!$p||!in_array($p['scheme']??'',['http','https'],true)||isset($p['user'])||isset($p['pass']))throw new \RuntimeException('Unsupported public URL.');
        $client=new NoPrivateNetworkHttpClient(HttpClient::create(['max_redirects'=>0,'timeout'=>4,'max_duration'=>6]));
        $response=$client->request('GET',$url,['headers'=>['Accept'=>'text/html','Cache-Control'=>'no-cache','User-Agent'=>'Contao-Schema-Manager/Audit']]);
        if($response->getStatusCode()!==200||!str_contains($response->getHeaders(false)['content-type'][0]??'','text/html')){$response->cancel();throw new \RuntimeException('Public HTML unavailable (authentication, redirect or HTTP error). No retirement can be approved.');}
        $html='';foreach($client->stream($response) as $chunk){$html.=$chunk->getContent();if(strlen($html)>2000000){$response->cancel();throw new \RuntimeException('Page exceeds the audit size limit.');}}
        return $html;
    }
    public function inspect(array $source): array
    {
        $result=['url'=>$source['url'],'title'=>html_entity_decode($source['title'],ENT_QUOTES|ENT_HTML5,'UTF-8'),'language'=>$source['language']??'','errors'=>[],'nodes'=>[],'elements'=>[]];
        try{$rendered=SchemaMarkup::parse($this->fetch($source['url']));$result['errors']=$rendered['errors'];}
        catch(\Throwable $e){$result['errors'][]=$e instanceof \RuntimeException?$e->getMessage():'Public HTML could not be read.';$rendered=['blocks'=>[],'nodes'=>[]];}
        $managed=[];
        foreach($this->db->fetchFirstColumn("SELECT entityId FROM tl_schema_entity WHERE entityId<>'' AND published='1'") as $id)$managed[$id]=true;
        foreach($this->db->fetchFirstColumn("SELECT schemaWebsiteId FROM tl_page WHERE schemaWebsiteId<>''") as $id)$managed[$id]=true;
        $managerNodes=array_values(array_filter($rendered['nodes'],static fn($n)=>isset($managed[$n['@id']??''])));
        foreach($rendered['nodes'] as $node){
            if(!in_array($node['@type']??'', ['Organization','LocalBusiness','Person','Service','Product','Event','WebSite','WebPage','BlogPosting','Article','NewsArticle'],true))continue;
            $matches=array_values(array_filter($managerNodes,static fn($n)=>SchemaMarkup::sameThing($node,$n)));
            $isManaged=isset($managed[$node['@id']??'']);
            $result['nodes'][]=['label'=>SchemaMarkup::label($node),'id'=>$node['@id']??'','data'=>$node,'replacementData'=>count($matches)===1?$matches[0]:null,'managed'=>$isManaged,'replacement'=>count($matches)===1?$matches[0]['@id']:null,'differences'=>!$isManaged&&count($matches)===1?SchemaMarkup::missing($node,$matches[0]):[]];
        }
        // Exact block equality identifies content origins without guessing from similar text.
        $table=str_starts_with($source['id'],'news:')?'tl_news':(str_starts_with($source['id'],'event:')?'tl_calendar_events':'tl_article');
        $parents=$table==='tl_article'?$this->db->fetchFirstColumn('SELECT id FROM tl_article WHERE pid=?',[$source['page']]):[(int)substr(strstr($source['id'],':'),1)];
        foreach($this->db->fetchAllAssociative("SELECT id,pid,ptable,html,invisible FROM tl_content WHERE type='html' AND invisible='' AND html LIKE '%application/ld+json%' ORDER BY id") as $row){
            if($row['ptable']!==$table||!in_array((int)$row['pid'],array_map('intval',$parents),true))continue;
            $parsed=SchemaMarkup::parse($row['html']);if(!$parsed['blocks'])continue;
            $present=true;foreach($parsed['blocks'] as $block)if(!in_array($block,$rendered['blocks'],true))$present=false;
            if(!$present)continue;
            $reasons=[];if(!SchemaMarkup::scriptOnly($row['html']))$reasons[]='This element also contains other markup; edit it manually.';
            if($result['errors'])$reasons[]='The page has JSON-LD errors.';
            // Top-level entities are checked recursively, so nested data cannot disappear unnoticed.
            $top=[];foreach($parsed['blocks'] as $block){foreach($block['@graph']??(array_is_list($block)?$block:[$block]) as $n)if(is_array($n))$top[]=$n;}
            foreach($top as $node){
                $matches=array_values(array_filter($managerNodes,static fn($n)=>SchemaMarkup::sameThing($node,$n)&&$n!==$node));
                if(count($matches)!==1){$reasons[]='No unique published replacement: '.SchemaMarkup::label($node);continue;}
                if(!empty($node['@id'])&&$node['@id']!==($matches[0]['@id']??''))$reasons[]='Different identity: review references to '.$node['@id'].' before retiring this definition.';
                $missing=SchemaMarkup::missing($node,$matches[0]);if($missing)$reasons[]=SchemaMarkup::label($node).': review '.implode(', ',$missing);
            }
            $result['elements'][]=['id'=>(int)$row['id'],'module'=>$table==='tl_news'?'news':($table==='tl_calendar_events'?'calendar':'article'),'hash'=>hash('sha256',$row['html']),'ready'=>!$reasons,'reasons'=>$reasons,'status'=>'pending'];
        }
        return $result;
    }
    public function retire(array &$run,array $selected,BackendUser $user): int
    {
        if(!$user->isAdmin||empty($run['audit'])||$run['status']!=='complete')throw new \RuntimeException('Complete an administrator audit first.');
        $count=0;$handled=[];
        foreach(array_unique($selected) as $selection){
            [$key,$id]=array_pad(explode('|',(string)$selection,2),2,'');$id=(int)$id;if(isset($handled[$id]))continue;$handled[$id]=true;
            $source=$run['inventory']['sources'][$key]??null;$saved=null;
            foreach($run['auditResults'][$key]['elements']??[] as $item)if($item['id']===$id)$saved=$item;
            if(!$source||!$saved||!$saved['ready']||$saved['status']!=='pending')throw new \RuntimeException('Select a reviewed, ready content element.');
            $fresh=$this->inventory->collect((int)$run['root'],$user,!empty($run['inventory']['multilingual']));
            if(!isset($fresh['sources'][$key]))throw new \RuntimeException('Page is no longer eligible. Run a fresh audit.');
            $check=$this->inspect($fresh['sources'][$key]);$ready=false;foreach($check['elements'] as $item)if($item['id']===$id&&$item['ready']&&$item['hash']===$saved['hash'])$ready=true;
            if(!$ready)throw new \RuntimeException('Markup or replacement changed. Run a fresh audit.');
            $row=$this->db->fetchAssociative('SELECT * FROM tl_content WHERE id=? FOR UPDATE',[$id]);
            if(!$row||$row['type']!=='html'||$row['invisible']||hash('sha256',$row['html'])!==$saved['hash'])throw new \RuntimeException('Content element changed. Nothing was disabled.');
            $v=new \Contao\Versions('tl_content',$id);$v->setUserId((int)$user->id);$v->setUsername($user->username);$v->setEditUrl('do='.($row['ptable']==='tl_news'?'news':($row['ptable']==='tl_calendar_events'?'calendar':'article')).'&table=tl_content&act=edit&id='.$id);$v->initialize();
            $this->db->update('tl_content',['invisible'=>'1','tstamp'=>time()],['id'=>$id]);$v->create();
            foreach($run['auditResults'] as &$result)foreach($result['elements'] as &$item)if($item['id']===$id)$item['status']='disabled';unset($result,$item);
            ++$count;
        }
        if(!$count)throw new \RuntimeException('Select at least one verified legacy element.');
        return $count;
    }
}
