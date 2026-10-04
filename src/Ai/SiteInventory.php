<?php
declare(strict_types=1);
namespace VHUG\SchemaManagerBundle\Ai;
use Contao\BackendUser;
use Contao\PageModel;
use Doctrine\DBAL\Connection;
use VHUG\SchemaManagerBundle\Backend\ContentMapSource;
final class SiteInventory
{
    public function __construct(private readonly Connection $db,private readonly ContentMapSource $content) {}
    public function roots(): array
    {
        $roots=[];foreach($this->db->fetchAllAssociative("SELECT * FROM tl_page WHERE type='root' ORDER BY sorting,id") as $row){if($this->visible($row))$roots[(int)$row['id']]=$row['title'];}return $roots;
    }
    public static function text(string $html): string
    {
        $html=preg_replace('~<(script|style|nav|header|footer)\b[^>]*>.*?</\1>~is',' ',$html);
        return trim(preg_replace('/\s+/u',' ',html_entity_decode(strip_tags(str_replace(['</p>','</div>','<br>','</li>'], ' ', $html)),ENT_QUOTES|ENT_HTML5,'UTF-8')));
    }
    private function visible(array $row,bool $invert=false): bool
    {
        $now=(int) floor(time()/60)*60; return ($invert?empty($row['invisible']):!empty($row['published'])) && empty($row['protected']) && (empty($row['start'])||(int)$row['start']<=$now) && (empty($row['stop'])||(int)$row['stop']>$now);
    }
    public function collect(int $root,BackendUser $user,bool $multilingual=false): array
    {
        if (!isset($this->roots()[$root])) { throw new \InvalidArgumentException('Select a website root.'); }
        $roots=[$root];
        if($multilingual){
            $selected=$this->db->fetchAssociative('SELECT * FROM tl_page WHERE id=?',[$root]);
            foreach($this->db->fetchAllAssociative("SELECT * FROM tl_page WHERE type='root' ORDER BY sorting,id") as $candidate){
                if((int)$candidate['id']!==$root && $this->visible($candidate) && strtolower(trim($candidate['dns']))===strtolower(trim($selected['dns'])) && $candidate['language']!==$selected['language'])$roots[]=(int)$candidate['id'];
            }
        }
        $data=$this->content->load($user);$allPages=[];foreach($data['pages'] as $page)$allPages[(int)$page['id']]=$page; $pages=[]; $sources=[]; $records=[];
        foreach ($data['pages'] as $p) {
            if (!in_array((int)$p['_root'],$roots,true) || $p['type']!=='regular' || !$this->visible($p) || preg_match('/(?:^|[,\s])noindex(?:$|[,\s])/i',$p['robots'] ?? '')) { continue; }
            $parent=(int)($p['pid'] ?? 0);$seen=[];$valid=true;
            // Contao's PublishedFilter checks the page and root, not intermediate
            // navigation containers. Protection, unlike publication, is inherited.
            while ($parent && !isset($seen[$parent])) {
                $seen[$parent]=true;$ancestor=$this->db->fetchAssociative('SELECT * FROM tl_page WHERE id=?',[$parent]);
                if(!$ancestor || !empty($ancestor['protected'])){$valid=false;break;}
                if($ancestor['type']==='root'){$valid=$this->visible($ancestor);$parent=0;break;}
                $parent=(int)$ancestor['pid'];
            }
            if (!$valid || $parent) { continue; }
            $pages[$p['id']]=$p;
            if (!empty($p['requireItem'])) { continue; }
            $text=$p['title'].' '.($p['description'] ?? '');
            foreach ($this->db->fetchAllAssociative('SELECT * FROM tl_article WHERE pid=? ORDER BY sorting',[$p['id']]) as $article) {
                if (!$this->visible($article)) { continue; }
                $text.=' '.$article['title'].' '.$this->elements('tl_article',(int)$article['id']);
            }
            $key='page:'.$p['id'];$sources[$key]=$this->source($key,$p['title'],$p['language'],$p['url'],$text,(int)$p['id']);
            $records[$key]=$p;
        }
        $archives=$data['news']?$this->db->fetchAllAssociativeIndexed('SELECT id,protected FROM tl_news_archive'):[];
        foreach ($data['news'] as $post) {
            $reader=$pages[$post['_reader']] ?? null;
            if (!$reader || !isset($archives[$post['pid']]) || !empty($archives[$post['pid']]['protected']) || !$this->visible($post) || !in_array($post['source'] ?? '',['','default'],true)) { continue; }
            $authorText='';
            // Only authors attached to eligible published news are candidates. Never include account contacts or login data.
            if($user->isAdmin && in_array($post['_mode'],['','Article','NewsArticle','BlogPosting'],true) && (int)$post['author']>0 && trim((string)($post['mapAuthorName']??''))!==''){
                $authorKey='author:'.$post['author'];
                $records[$authorKey]=['id'=>(int)$post['author'],'name'=>$post['mapAuthorName'],'schemaPerson'=>(int)($post['mapAuthor']??0)];
                $authorText=' Contao editorial author: '.$post['mapAuthorName'].'.';
                $post['_authorTarget']=$authorKey;
            }
            $key='news:'.$post['id'];$sources[$key]=$this->source($key,$post['headline'],$reader['language'],$post['url'],$post['headline'].$authorText.' '.($post['teaser'] ?? '').' '.$this->elements('tl_news',(int)$post['id']),(int)$reader['id']);
            if (in_array($post['_mode'],['','Article','NewsArticle','BlogPosting'],true)) { $post['_mode']=$post['_mode'] ?: 'NewsArticle';$records[$key]=$post; }
        }
        foreach($data['events'] ?? [] as $event){
            $reader=$pages[$event['_reader']]??null;
            if(!$reader||!$this->visible($event)||!empty($event['_protected'])||!in_array($event['source']??'', ['','default'],true)||preg_match('/(?:^|[,\s])noindex(?:$|[,\s])/i',$event['robots']??''))continue;
            $key='event:'.$event['id'];$sources[$key]=$this->source($key,$event['title'],$reader['language'],$event['url'],$event['title'].' '.($event['teaser']??'').' '.$this->elements('tl_calendar_events',(int)$event['id']),(int)$reader['id']);
            if($event['_mode']!=='suppress')$records[$key]=$event;
        }
        foreach ($this->db->fetchAllAssociative('SELECT * FROM tl_schema_entity ORDER BY id') as $row) { if($this->visible($row))$records['entity:'.$row['id']]=$row; }
        foreach ($this->db->fetchAllAssociative('SELECT * FROM tl_schema_translation ORDER BY id') as $row) {
            if(!$this->visible($row) || !isset($records['entity:'.$row['pid']]) || !isset($pages[$row['page']]))continue;
            // Only active homes on eligible public pages enter the analysis.
            $records['translation:'.$row['id']]=$row;
        }
        // Keep only schema-editable text/relations and routing metadata. Binary UUIDs,
        // backend configuration and unrelated custom fields must not enter prompts/history.
        foreach ($records as $key=>&$record) {
            $kind=strstr($key,':',true);$table=match($kind){'entity'=>'tl_schema_entity','translation'=>'tl_schema_translation','news'=>'tl_news','event'=>'tl_calendar_events','author'=>'tl_user',default=>'tl_page'};
            $type=$record['entityType'] ?? ($kind==='translation'?($records['entity:'.$record['pid']]['entityType'] ?? ''):'');
            $allowed=array_merge(FieldPolicy::fields($table,$type),array_keys(FieldPolicy::links($table,$type)),['id','pid','name','title','headline','entityType','entityId','identityBase','page','language','published','_mode','_organizer','_authorTarget','_author']);
            $record=array_intersect_key($record,array_flip($allowed));
        }
        unset($record);
        foreach ($pages as &$p) { $p=array_intersect_key($p,array_flip(['id','pid','title','language','published','protected','start','stop','requireItem','robots','url','_root','languageMain'])); } unset($p);
        if (count($sources)>200) { throw new \RuntimeException('This root has more than 200 sources. Use a smaller site root for this first version.'); }
        $pages=array_filter($pages,static fn($p)=>empty($p['requireItem']));
        foreach($pages as $id=>&$page){
            $family=(int)$id;$seen=[];
            while(!isset($seen[$family]) && !empty($allPages[$family]['languageMain'])){$seen[$family]=true;$family=(int)$allPages[$family]['languageMain'];}
            $page['languageFamily']=$family;
        }unset($page);
        // Keep linked page translations adjacent, so discovery sees them together.
        uasort($sources,static fn($a,$b)=>($pages[$a['page']]['languageFamily'] ?? $a['page'])<=>($pages[$b['page']]['languageFamily'] ?? $b['page']));
        // Identity reservations are a duplicate-prevention list, never editable records or evidence.
        $identities=$this->db->fetchAllAssociative('SELECT id,entityType,name,legalName FROM tl_schema_entity ORDER BY id');
        return ['identities'=>$identities,'sources'=>$sources,'records'=>$records,'pages'=>$pages,'roots'=>$roots,'multilingual'=>$multilingual];
    }
    private function elements(string $table,int $id): string
    {
        $text='';
        foreach ($this->db->fetchAllAssociative('SELECT * FROM tl_content WHERE ptable=? AND pid=? ORDER BY sorting',[$table,$id]) as $row) {
            if (!$this->visible($row,true)) { continue; }
            foreach (['headline','text','html','listitems','caption','linkTitle','title'] as $field) {
                $value=\Contao\StringUtil::deserialize($row[$field] ?? '',true);
                array_walk_recursive($value,static function($v)use(&$text){if(is_scalar($v))$text.=' '.(string)$v;});
            }
        }
        return $text;
    }
    private function source(string $key,string $title,string $language,string $url,string $text,int $page): array
    {
        $text=mb_substr(self::text($text),0,18000);
        return ['id'=>$key,'title'=>$title,'language'=>$language,'url'=>$url,'page'=>$page,'text'=>$text,'hash'=>hash('sha256',$text),'coverage'=>'Contao text fields; custom module output may be incomplete.'];
    }
}
