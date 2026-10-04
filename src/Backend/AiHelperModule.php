<?php
declare(strict_types=1);
namespace VHUG\SchemaManagerBundle\Backend;
use Contao\BackendUser;
use Contao\BackendTemplate;
use Contao\System;
use Contao\Controller;
use Doctrine\DBAL\Connection;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use VHUG\SchemaManagerBundle\Ai\{ApiKeyStore,RunStore,AnalysisRunner,SiteInventory,ProposalEngine,OpenAiProvider,SetupPlanner};
final class AiHelperModule
{
    public function __construct(private readonly Connection $db,private readonly RequestStack $requests,private readonly ApiKeyStore $keys,private readonly RunStore $runs,private readonly AnalysisRunner $runner,private readonly SiteInventory $inventory,private readonly ProposalEngine $proposals,private readonly SetupPlanner $setup) {}
    public function generate(): string
    {
        $user=BackendUser::getInstance();
        // Initial release deliberately restricted to administrators, including all POST endpoints.
        if (!$user->isAdmin) { throw new AccessDeniedException('The optional AI helper currently requires an administrator.'); }
        $request=$this->requests->getCurrentRequest();$container=System::getContainer();
        foreach (['schema_ai','tl_schema_entity','tl_schema_translation','tl_news','tl_news_archive','tl_page'] as $languageFile) { System::loadLanguageFile($languageFile); }$l=$GLOBALS['TL_LANG']['schema_ai'];$error='';$message='';
        $id=$request->query->getInt('run');
        $installed=$this->db->createSchemaManager()->tablesExist(['tl_schema_ai_run']);
        if ($request->isMethod('POST')) {
            $token=$request->request->getString('REQUEST_TOKEN');
            if (!$container->get('contao.csrf.token_manager')->isTokenValid(new CsrfToken($container->getParameter('contao.csrf_token_name'),$token))) { throw new AccessDeniedException('Invalid request token.'); }
            $action=$request->request->getString('ai_action');
            try {
                if ($action==='key') { $this->keys->save($request->request->getString('api_key'));$message=$l['keySaved']; }
                elseif (!$installed) { throw new \RuntimeException($l['migrate']); }
                elseif ($action==='start') {
                    $stage=$request->request->getString('stage','content');
                    if($stage==='configuration'){$id=$this->setup->prepare($user,$request->request->getInt('root'),$request->request->getBoolean('multilingual'));}
                    else {
                    if (!$this->keys->get()) { throw new \RuntimeException($l['keyMissing']); }
                    $id=$this->runner->start($user,$request->request->getInt('root'),$request->request->getString('mode'),$request->request->getString('origin'),$request->request->getBoolean('changed'),$request->request->getBoolean('multilingual'),$stage);
                    }
                    Controller::redirect($container->get('router')->generate('contao_backend',['do'=>'schema_manager','key'=>'ai','run'=>$id]));
                } elseif ($action==='refine') {
                    if (!$this->keys->get()) { throw new \RuntimeException($l['keyMissing']); }
                    if ($request->hasSession()) { $request->getSession()->save(); }
                    $newId=$this->runner->refine($id,$user,$request->request->getString('feedback'));
                    throw new \Contao\CoreBundle\Exception\ResponseException(new JsonResponse(['url'=>$container->get('router')->generate('contao_backend',['do'=>'schema_manager','key'=>'ai','run'=>$newId])]));
                } elseif ($action==='status') {
                    throw new \Contao\CoreBundle\Exception\ResponseException(new JsonResponse($this->runner->status($id,$user)));
                } elseif ($action==='step') {
                    if (!$this->keys->get()) { throw new \RuntimeException($l['keyMissing']); }
                    // Release the backend session lock while the provider works. Other tabs
                    // and status/review requests must remain responsive.
                    if ($request->hasSession()) { $request->getSession()->save(); }
                    $cursor=$request->request->getString('cursor');
                    $result=$this->runner->step($id,$user,$cursor!==''?$cursor:null);
                    if (!$request->isXmlHttpRequest()) { Controller::redirect($container->get('router')->generate('contao_backend',['do'=>'schema_manager','key'=>'ai','run'=>$id])); }
                    throw new \Contao\CoreBundle\Exception\ResponseException(new JsonResponse($result));
                } elseif (in_array($action,['apply','reject'],true)) {
                    $selected=$request->request->all('selected');
                    if (!$selected) { throw new \RuntimeException($l['selectSome']); }
                    $this->db->beginTransaction();
                    try {
                        $run=$this->runs->get($id,(int)$user->id,true);
                        if ($run['status']==='working') { throw new \RuntimeException('Wait for the running batch before reviewing.'); }
                        if ($action==='apply') {
                            $edits=$request->request->all('value');
                            foreach ($selected as $index) { if (isset($edits[$index],$run['proposals'][$index]) && $run['proposals'][$index]['action']==='set') { $run['proposals'][$index]['value']=(string)$edits[$index]; } }
                            $count=$this->proposals->apply($run,$selected,$user);$message=sprintf($l['applied'],$count);
                        } else {
                            foreach($selected as $index){if(isset($run['proposals'][$index])&&$run['proposals'][$index]['status']==='pending')$run['proposals'][$index]['status']='rejected';}
                            $message=$l['rejected'];
                        }
                        $this->runs->save($id,(int)$user->id,$run);$this->db->commit();
                    } catch (\Throwable $e) { $this->db->rollBack();throw $e; }
                    if ($action==='apply') { $this->proposals->invalidate(); }
                } else { throw new \InvalidArgumentException('Unknown action.'); }
            } catch (\Contao\CoreBundle\Exception\ResponseException $e) { throw $e; }
            catch (\Throwable $e) {
                // Never render exception diagnostics from secret parsing/network configuration.
                $error=$action==='key'?$l['keyError']:($e instanceof \RuntimeException || $e instanceof \InvalidArgumentException ? $e->getMessage():$l['error']);
                if (in_array($action,['step','refine','status'],true)) { throw new \Contao\CoreBundle\Exception\ResponseException(new JsonResponse(['done'=>false,'paused'=>true,'message'=>$error],400)); }
            }
        }
        try { $hasKey=$this->keys->get()!==''; } catch (\Throwable) { $hasKey=false;$error=$l['keyError']; }
        try { $run=$id && $installed?$this->runs->get($id,(int)$user->id):null; } catch (\RuntimeException) { throw new AccessDeniedException('Analysis is not available for this user.'); }
        $template=new BackendTemplate('be_schema_ai');
        $template->l=$l;$template->error=$error;$template->message=$message;$template->hasKey=$hasKey;$template->installed=$installed;
        $template->token=$container->get('contao.csrf.token_manager')->getDefaultTokenValue();$template->run=$run;$template->runId=$id;
        $template->roots=$this->inventory->roots();$template->recent=$installed?$this->runs->recent((int)$user->id):[];$template->model=OpenAiProvider::MODEL;
        $template->selectedRoot=$run['root'] ?? $request->query->getInt('root',(int)array_key_first($template->roots));
        $orgCount=(int)$this->db->fetchOne("SELECT COUNT(*) FROM tl_schema_entity WHERE entityType IN ('Organization','LocalBusiness')");
        $configured=(int)$this->db->fetchOne('SELECT schemaPublisher FROM tl_page WHERE id=?',[$template->selectedRoot]);
        $recommended=$orgCount?($configured?'content':'configuration'):'foundation';
        $stage=$run['stage'] ?? $request->query->getString('stage',$recommended);
        $template->stage=in_array($stage,['foundation','configuration','content'],true)?$stage:$recommended;
        $template->hasOrganizations=$orgCount>0;
        $template->hasPublishedOrganization=(bool)$this->db->fetchOne("SELECT id FROM tl_schema_entity WHERE entityType IN ('Organization','LocalBusiness') AND published='1' LIMIT 1");
        $origins=$this->db->fetchFirstColumn("SELECT DISTINCT identityBase FROM tl_schema_entity WHERE identityBase<>''");$template->origin=count($origins)===1?$origins[0]:'';
        $GLOBALS['TL_CSS'][]='bundles/schemamanager/ai-helper.css?v='.filemtime(__DIR__.'/../../public/ai-helper.css');
        $GLOBALS['TL_JAVASCRIPT'][]='bundles/schemamanager/ai-helper.js?v='.filemtime(__DIR__.'/../../public/ai-helper.js');
        return $template->parse();
    }
}
