<?php
declare(strict_types=1);
namespace VHUG\SchemaManagerBundle\Schema;

/** Adds permission-filtered content records to the editorial graph. No frontend requests or writes. */
final class ContentRelationshipMap
{
    public function extend(array $map, array $pages, array $news, array $translations,array $events=[]): array
    {
        $excluded = [];
        foreach ($pages as $page) {
            if ($page['type']==='regular' && preg_match('/(?:^|[,\s])noindex(?:$|[,\s])/i', $page['robots'] ?? '')) { $excluded[(int)$page['id']]=true; }
        }
        $pages=array_values(array_filter($pages,static fn($page)=>!isset($excluded[(int)$page['id']])));
        $news=array_values(array_filter($news,static fn($post)=>!isset($excluded[(int)$post['_reader']])));
        $translations=array_values(array_filter($translations,static fn($home)=>!isset($excluded[(int)$home['page']])));
        foreach ($map['nodes'] as &$node) { $node['data']['homes']=array_values(array_filter($node['data']['homes'],static fn($home)=>!isset($excluded[(int)$home['page']]))); }
        unset($node);
        $nodes = []; foreach ($map['nodes'] as $node) { $nodes[$node['data']['id']] = $node; }
        $edges = [];
        foreach ($map['edges'] as $edge) { $d=$edge['data']; $edges[$d['source'].'|'.$d['label'].'|'.$d['target']]=$edge; }
        $add = static function(string $id, string $type, string $name, int $record, string $kind, array $extra=[]) use (&$nodes):void {
            $nodes[$id] = ['data'=>$extra + ['id'=>$id,'type'=>$type,'name'=>html_entity_decode(strip_tags($name),ENT_QUOTES|ENT_HTML5,'UTF-8'),'record'=>$record,'kind'=>$kind,
                'detail'=>'','identity'=>'','published'=>true,'missing'=>false,'homes'=>[],'warnings'=>[]]];
        };
        $link = static function(string $source, string $target, string $label) use (&$nodes,&$edges,$add):void {
            if (isset($nodes[$source]) && preg_match('/^entity-([1-9][0-9]*)$/D',$target,$match) && !isset($nodes[$target])) {
                $add($target,'Missing','#'.$match[1],(int)$match[1],'entity',['published'=>false,'missing'=>true]);
            }
            // Never create placeholders for inaccessible pages/news (would reveal protected records).
            if (!isset($nodes[$source],$nodes[$target])) { return; }
            $edges[$source.'|'.$label.'|'.$target] = ['data'=>['source'=>$source,'target'=>$target,'label'=>$label]];
        };
        $byPage = array_column($pages,null,'id'); $siteFor = [];
        foreach ($pages as $page) {
            $root = $byPage[$page['_root']] ?? null;
            $site = $root ? ($byPage[($root['schemaWebsiteRoot'] ?? 0) ?: $root['id']] ?? null) : null;
            if ($site && $site['type']==='root') { $siteFor[$page['id']] = (int)$site['id']; }
        }
        foreach (array_unique(array_values($siteFor)) as $siteId) {
            $site = $byPage[$siteId];
            $add('site-'.$siteId,'WebSite',($site['schemaSiteName'] ?? '') ?: $site['title'],$siteId,'page',[
                'identity'=>$site['schemaWebsiteId'] ?? '', 'published'=>(bool)$site['published'], 'detail'=>$site['language'] ?? '',
                'languages'=>array_values(array_unique(array_filter(array_map(static fn($page)=>($siteFor[$page['id']] ?? null)===$siteId ? ($page['language'] ?? '') : '',$pages)))),
                'warnings'=>empty($site['schemaWebsiteId'])?['missingIdentity']:[]]);
            $link('site-'.$siteId,'entity-'.($site['schemaPublisher'] ?? 0),'publisher');
        }
        foreach ($pages as $page) {
            // Keep reader metadata for real detail URLs, but omit its bare container.
            if ($page['type'] !== 'regular' || !empty($page['requireItem'])) { continue; }
            $key='page-'.$page['id']; $url=$page['url'] ?? '';
            $type=in_array($page['schemaPageType'] ?? '', ['WebPage','AboutPage','ContactPage','CollectionPage','ProfilePage','ItemPage'],true)?$page['schemaPageType']:'WebPage';
            $add($key,$type,$page['title'],(int)$page['id'],'page',['identity'=>$url?$url.'#webpage':'','detail'=>$page['language'] ?? '', 'published'=>(bool)$page['published']]);
            if ($siteId=$siteFor[$page['id']] ?? null) {
                $link($key,'site-'.$siteId,'isPartOf');
                $link($key,'entity-'.($byPage[$siteId]['schemaPublisher'] ?? 0),'publisher');
            }
            foreach ($page['schemaEntities'] ?? [] as $id) { $link($key,'entity-'.$id,'about'); }
        }
        foreach ($translations as $home) {
            $page=$byPage[$home['page']] ?? null;
            if (!$page || $home['language'] !== ($page['language'] ?? '')) { continue; }
            if (!empty($home['isMainEntity'])) {
                $link('page-'.$home['page'],'entity-'.$home['pid'],'mainEntity');
                $link('entity-'.$home['pid'],'page-'.$home['page'],'mainEntityOfPage');
            }
        }
        foreach ($news as $post) {
            $key='news-'.$post['id']; $mode=$post['_mode'];
            $managed=in_array($mode,['BlogPosting','Article','NewsArticle','JobPosting'],true);
            $enriched=$managed || ($mode==='' && (!empty($post['schemaAuthor']) || !empty($post['schemaAbout']) || !empty($post['schemaMentions']) || !empty($post['schemaDateModified']) || !empty($post['_publisher'])));
            $type=$managed?$mode:'NewsArticle'; $suppressed=$mode==='suppress';
            $warnings=$suppressed?['suppressed']:[];
            if ($managed && empty($post['schemaIdentity'])) { $warnings[]='missingIdentity'; }
            if ($mode==='JobPosting' && !empty($post['schemaJobValidThrough']) && (int)$post['schemaJobValidThrough']<=time()) { $warnings[]='expired'; }
            $add($key,$type,$post['headline'],(int)$post['id'],'news',['identity'=>$managed?($post['schemaIdentity'] ?? ''):'',
                'published'=>(bool)$post['published'], 'warnings'=>$warnings, 'detail'=>$byPage[$post['_reader']]['language'] ?? '']);
            if ($suppressed) { continue; }
            if ($mode==='JobPosting') {
                $link($key,'entity-'.(($post['schemaJobEmployer'] ?? 0) ?: $post['_publisher']),'hiringOrganization');
            } else {
                if ($enriched) { $link($key,'entity-'.$post['_publisher'],'publisher'); }
                $author='entity-'.$post['_author'];
                if ($enriched && !empty($nodes[$author]) && !empty($nodes[$author]['data']['published']) && empty($nodes[$author]['data']['missing'])) {
                    $link($key,$author,'author');
                } elseif (!empty($post['mapAuthorName'])) {
                    // Core embeds an unnamed-identity Person. Keep it distinct from managed people.
                    $author='core-author-'.$post['author'];
                    if (!isset($nodes[$author])) { $add($author,'Person',$post['mapAuthorName'],0,'coreAuthor',['warnings'=>['unmappedAuthor']]); }
                    $link($key,$author,'author');
                }
                if ($enriched) {
                    foreach (['schemaAbout'=>'about','schemaMentions'=>'mentions'] as $field=>$property) {
                        foreach ($post[$field] ?? [] as $related) { $link($key,'entity-'.$related,$property); }
                    }
                }
            }
            $reader=$byPage[$post['_reader']] ?? null;
            if ($enriched && $reader && !empty($post['url']) && in_array($post['source'] ?? 'default',['','default'],true)) {
                // Each detail URL is a separate WebPage, not the bare reader page shared by all posts.
                $readerKey='reader-'.$post['id'];
                $add($readerKey,($reader['schemaPageType'] ?? '') ?: 'WebPage',$post['headline'],(int)$reader['id'],'page',[
                    'identity'=>$post['url'].'#webpage','detail'=>$reader['language'] ?? '', 'published'=>(bool)$post['published']]);
                $link($key,$readerKey,'mainEntityOfPage'); $link($readerKey,$key,'mainEntity');
                if ($siteId=$siteFor[$reader['id']] ?? null) {
                    $link($readerKey,'site-'.$siteId,'isPartOf');
                    $link($readerKey,'entity-'.($byPage[$siteId]['schemaPublisher'] ?? 0),'publisher');
                }
                foreach ($reader['schemaEntities'] ?? [] as $id) { $link($readerKey,'entity-'.$id,'about'); }
            }
        }
        foreach ($news as $post) {
            if (in_array($post['_mode'],['BlogPosting','Article','NewsArticle'],true) && !empty($post['languageMain'])) {
                $link('news-'.$post['id'],'news-'.$post['languageMain'],'translationOfWork');
            }
        }
        foreach($events as $event){
            if(isset($excluded[(int)$event['_reader']]))continue;
            $key='event-'.$event['id'];$reader=$byPage[$event['_reader']]??null;
            $add($key,'Event',$event['title'],(int)$event['id'],'event',['identity'=>$event['schemaIdentity']??'','detail'=>$reader['language']??'','published'=>(bool)$event['published'],'warnings'=>$event['_mode']==='suppress'?['suppressed']:[]]);
            if($event['_mode']==='suppress')continue;
            $link($key,'entity-'.$event['_organizer'],'organizer');
            if(!empty($event['_venue']))$link($key,'entity-'.$event['_venue'],'location');
            foreach(['schemaAbout'=>'about','schemaPerformer'=>'performer'] as $field=>$property)foreach($event[$field]??[] as $id)$link($key,'entity-'.$id,$property);
            if($reader&&!empty($event['url'])){
                $detail='event-reader-'.$event['id'];$add($detail,'ItemPage',$event['title'],(int)$reader['id'],'page',['identity'=>$event['url'].'#webpage','detail'=>$reader['language']??'','published'=>(bool)$event['published']]);
                $link($key,$detail,'mainEntityOfPage');$link($detail,$key,'mainEntity');if($siteId=$siteFor[$reader['id']]??null)$link($detail,'site-'.$siteId,'isPartOf');
            }
        }
        // Topic diagnostics intentionally ignore structural website/publisher/author relationships.
        foreach ($nodes as &$node) {
            $d=&$node['data']; $d['kind'] ??= 'entity';
            $d['language']=in_array($d['kind'],['page','news','event'],true) && $d['type']!=='WebSite' ? $d['detail'] : '';
            $d['languages'] ??= [];
            if ($d['kind']==='news' && in_array($d['type'],['BlogPosting','Article','NewsArticle'],true) && !in_array('suppressed',$d['warnings'],true)) {
                $d['needsService']=true;
                foreach ($edges as $edge) {
                    $e=$edge['data'];
                    if ($e['source']===$d['id'] && in_array($e['label'],['about','mentions'],true) && ($nodes[$e['target']]['data']['type'] ?? '')==='Service') { $d['needsService']=false; break; }
                }
            }
        }
        unset($node,$d);
        $edges=array_values($edges); foreach ($edges as $i=>&$edge) { $edge['data']['id']='relation-'.$i; } unset($edge);
        return ['nodes'=>array_values($nodes),'edges'=>$edges];
    }
}
