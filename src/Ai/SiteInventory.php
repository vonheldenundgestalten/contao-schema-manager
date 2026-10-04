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
    public function roots(): array { return $this->db->fetchAllKeyValue("SELECT id,title FROM tl_page WHERE type='root' ORDER BY sorting,id"); }
    public static function text(string $html): string
    {
        $html=preg_replace('~<(script|style|nav|header|footer)\b[^>]*>.*?</\1>~is',' ',$html);
        return trim(preg_replace('/\s+/u',' ',html_entity_decode(strip_tags(str_replace(['</p>','</div>','<br>','</li>'], ' ', $html)),ENT_QUOTES|ENT_HTML5,'UTF-8')));
    }
    private function visible(array $row,bool $invert=false): bool
    {
        $now=time(); return ($invert?empty($row['invisible']):!empty($row['published'])) && empty($row['protected']) && (empty($row['start'])||(int)$row['start']<=$now) && (empty($row['stop'])||(int)$row['stop']>$now);
    }
    public function collect(int $root,BackendUser $user): array
    {
        if (!isset($this->roots()[$root])) { throw new \InvalidArgumentException('Select a website root.'); }
        $data=$this->content->load($user); $pages=[]; $sources=[]; $records=[];
        foreach ($data['pages'] as $p) {
            if ((int)$p['_root']!==$root || $p['type']!=='regular' || !$this->visible($p) || preg_match('/(?:^|[,\s])noindex(?:$|[,\s])/i',$p['robots'] ?? '')) { continue; }
            $parent=(int)($p['pid'] ?? 0);$seen=[];$valid=true;
            while ($parent && !isset($seen[$parent])) { $seen[$parent]=true; $ancestor=$this->db->fetchAssociative('SELECT * FROM tl_page WHERE id=?',[$parent]); if (!$ancestor || !$this->visible($ancestor)) { $valid=false;break; } $parent=(int)$ancestor['pid']; }
            if (!$valid) { continue; }
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
        foreach ($data['news'] as $post) {
            $reader=$pages[$post['_reader']] ?? null;
            if (!$reader || !$this->visible($post) || !in_array($post['source'] ?? '',['','default'],true)) { continue; }
            $key='news:'.$post['id'];$sources[$key]=$this->source($key,$post['headline'],$reader['language'],$post['url'],$post['headline'].' '.($post['teaser'] ?? '').' '.$this->elements('tl_news',(int)$post['id']),(int)$reader['id']);
            if (in_array($post['_mode'],['Article','NewsArticle','BlogPosting'],true)) { $records[$key]=$post; }
        }
        foreach ($this->db->fetchAllAssociative('SELECT * FROM tl_schema_entity ORDER BY id') as $row) { $records['entity:'.$row['id']]=$row; }
        foreach ($this->db->fetchAllAssociative('SELECT * FROM tl_schema_translation ORDER BY id') as $row) { if (isset($pages[$row['page']])) { $records['translation:'.$row['id']]=$row; } }
        // Keep only schema-editable text/relations and routing metadata. Binary UUIDs,
        // backend configuration and unrelated custom fields must not enter prompts/history.
        foreach ($records as $key=>&$record) {
            $kind=strstr($key,':',true);$table=match($kind){'entity'=>'tl_schema_entity','translation'=>'tl_schema_translation','news'=>'tl_news',default=>'tl_page'};
            $type=$record['entityType'] ?? ($kind==='translation'?($records['entity:'.$record['pid']]['entityType'] ?? ''):'');
            $allowed=array_merge(FieldPolicy::fields($table,$type),array_keys(FieldPolicy::links($table,$type)),['id','pid','name','title','headline','entityType','entityId','identityBase','page','language','published','_mode']);
            $record=array_intersect_key($record,array_flip($allowed));
        }
        unset($record);
        foreach ($pages as &$p) { $p=array_intersect_key($p,array_flip(['id','pid','title','language','published','protected','start','stop','requireItem','robots','url','_root'])); } unset($p);
        if (count($sources)>200) { throw new \RuntimeException('This root has more than 200 sources. Use a smaller site root for this first version.'); }
        return ['sources'=>$sources,'records'=>$records,'pages'=>array_filter($pages,static fn($p)=>empty($p['requireItem']))];
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
