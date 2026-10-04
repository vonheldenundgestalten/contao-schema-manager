<?php
declare(strict_types=1);
namespace VHUG\SchemaManagerBundle\Backend;
use Contao\BackendUser;
use Contao\PageModel;
use Contao\NewsModel;
use Contao\StringUtil;
use Contao\CoreBundle\Routing\ContentUrlGenerator;
use Contao\CoreBundle\Security\ContaoCorePermissions;
use Contao\CoreBundle\Security\DataContainer\ReadAction;
use Doctrine\DBAL\Connection;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\Exception\ExceptionInterface as RoutingException;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/** Reads only content available through the current user's Contao permissions. */
final class ContentMapSource
{
    public function __construct(private readonly Connection $connection, private readonly ContentUrlGenerator $urls, private readonly AuthorizationCheckerInterface $security) {}
    public function load(BackendUser $user): array
    {
        $pages = []; $news = [];
        if ($user->isAdmin || $user->hasAccess('page', 'modules')) {
            foreach ($this->connection->fetchAllAssociative("SELECT * FROM tl_page WHERE type IN ('regular','root') ORDER BY sorting,id") as $row) {
                if (!$user->isAdmin && !$this->security->isGranted(ContaoCorePermissions::DC_PREFIX.'tl_page', new ReadAction('tl_page',$row))) { continue; }
                $page = PageModel::findById($row['id']);
                if (!$page) { continue; }
                $page->loadDetails();
                $row['_root'] = $row['type']==='root' ? (int)$row['id'] : (int)$page->rootId;
                $row['language'] = $page->language;
                $row['url'] = '';
                if ($row['type']==='regular' && !$page->requireItem) {
                    try { $row['url'] = $this->urls->generate($page, [], UrlGeneratorInterface::ABSOLUTE_URL); } catch (RoutingException) { /* Draft or incomplete route: still show the record. */ }
                }
                $row['schemaEntities'] = StringUtil::deserialize($row['schemaEntities'] ?? null, true);
                $pages[(int)$row['id']] = $row;
            }
        }
        if (class_exists(NewsModel::class) && ($user->isAdmin || $user->hasAccess('news', 'modules'))) {
            $archives = $this->connection->fetchAllAssociativeIndexed('SELECT id, schemaType, schemaPublisher, jumpTo FROM tl_news_archive');
            foreach ($this->connection->fetchAllAssociative('SELECT n.*, u.schemaPerson AS mapAuthor, u.name AS mapAuthorName FROM tl_news n LEFT JOIN tl_user u ON u.id=n.author ORDER BY n.date DESC,n.id') as $row) {
                if (!$user->isAdmin && !$this->security->isGranted(ContaoCorePermissions::DC_PREFIX.'tl_news', new ReadAction('tl_news',$row))) { continue; }
                $archive = $archives[$row['pid']] ?? null;
                if (!$archive) { continue; }
                $row['_mode'] = $archive['schemaType'];
                $row['_publisher'] = (int)$archive['schemaPublisher'];
                $row['_reader'] = (int)(($row['source'] ?? '') === 'default' || empty($row['source']) ? $archive['jumpTo'] : (($row['source'] ?? '') === 'internal' ? $row['jumpTo'] : 0));
                $row['_author'] = (int)(($row['schemaAuthor'] ?? 0) ?: ($row['mapAuthor'] ?? 0));
                $row['url'] = '';
                try {
                    if ($model = NewsModel::findById($row['id'])) { $row['url'] = $this->urls->generate($model, [], UrlGeneratorInterface::ABSOLUTE_URL); }
                } catch (RoutingException) { /* Keep records with missing reader pages visible. */ }
                foreach (['schemaAbout','schemaMentions'] as $field) { $row[$field] = StringUtil::deserialize($row[$field] ?? null, true); }
                $news[] = $row;
            }
        }
        $events=[];
        if(class_exists(\Contao\CalendarEventsModel::class)&&($user->isAdmin||$user->hasAccess('calendar','modules'))){
            $calendars=$this->connection->fetchAllAssociativeIndexed('SELECT * FROM tl_calendar');
            foreach($this->connection->fetchAllAssociative('SELECT * FROM tl_calendar_events ORDER BY startTime,id') as $row){
                if(!$user->isAdmin&&!$this->security->isGranted(ContaoCorePermissions::DC_PREFIX.'tl_calendar_events',new ReadAction('tl_calendar_events',$row)))continue;
                $calendar=$calendars[$row['pid']]??null;if(!$calendar)continue;
                $row['_reader']=(int)$calendar['jumpTo'];$row['_mode']=$calendar['schemaMode'];$row['_protected']=$calendar['protected'];$row['_organizer']=(int)($row['schemaOrganizer']?:$calendar['schemaOrganizer']);$row['url']='';
                try{if($model=\Contao\CalendarEventsModel::findById($row['id']))$row['url']=$this->urls->generate($model,[],UrlGeneratorInterface::ABSOLUTE_URL);}catch(RoutingException){}
                foreach(['schemaAbout','schemaPerformer'] as $field)$row[$field]=StringUtil::deserialize($row[$field]??null,true);
                $events[]=$row;
            }
        }
        return ['pages'=>array_values($pages), 'news'=>$news,'events'=>$events];
    }
}
