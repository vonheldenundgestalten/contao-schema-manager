<?php
declare(strict_types=1);
require_once dirname(__DIR__).'/src/Ai/ProposalQueue.php';
use VHUG\SchemaManagerBundle\Ai\ProposalQueue;
$check=static function($ok,$message){if(!$ok)throw new RuntimeException($message);};
$p=static fn($action,$target,$field,$value)=>compact('action','target','field','value')+['status'=>'pending'];
$run=['inventory'=>['records'=>['author:1'=>['name'=>'Test Author']],'pages'=>[5=>['title'=>'Team','language'=>'en']]],'mapped'=>[],
'proposals'=>[$p('set','new:person@5','jobTitle','Editor'),$p('add','author:1','schemaPerson','new:person'),$p('home','new:person','page','5'),$p('create','new:person','Person','Test Author')]];
$plan=ProposalQueue::plan($run,[0,1]);
$check(count($plan['order'])===4&&array_search(3,$plan['order'])<array_search(2,$plan['order'])&&array_search(2,$plan['order'])<array_search(0,$plan['order'])&&array_search(3,$plan['order'])<array_search(1,$plan['order'])&&!$plan['blocked'],'Dependencies automatically included and ordered, even when source order is reversed');
$run['proposals'][2]['status']='rejected';$plan=ProposalQueue::plan($run,[0,1]);
$check($plan['order']===[3,1]&&isset($plan['blocked'][0])&&str_contains($plan['blocked'][0],'Test Author')&&str_contains($plan['blocked'][0],'jobTitle'),'Rejected home blocks only its dependent field with a readable explanation');
$run['mapped']=['new:person'=>7,'new:person@5'=>8];$run['proposals'][3]['status']='applied';$plan=ProposalQueue::plan($run,[0,1]);
$check($plan['order']===[0,1]&&!$plan['blocked'],'Previously applied dependencies are reused');
$run['proposals'][0]=$p('set','entity:7@5','jobTitle','Editor');$run['inventory']['records']['translation:8']=['pid'=>7,'page'=>5];
$check(ProposalQueue::plan($run,[0])['order']===[0],'Existing language record resolves composite target');
$run['proposals'][0]=$p('set','new:missing@5','jobTitle','Editor');$plan=ProposalQueue::plan($run,[0]);
$check(!$plan['order']&&isset($plan['blocked'][0]),'Missing prerequisites remain pending without retries or writes');
echo "PASS: dependency closure, ordering, blocked items, applied prerequisites and existing translations.\n";
