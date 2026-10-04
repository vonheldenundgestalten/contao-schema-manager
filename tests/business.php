<?php
declare(strict_types=1);
// Run from the installed Contao application's root; all fixture writes roll back.
require getcwd().'/vendor/autoload.php';
$kernel = Contao\ManagerBundle\HttpKernel\ContaoKernel::fromInput(getcwd(), new Symfony\Component\Console\Input\ArgvInput());
if (getenv('SCHEMA_TEST_DB_TCP') === '1') {
    foreach (['_SERVER', '_ENV'] as $scope) {
        if (isset($GLOBALS[$scope]['DATABASE_URL'])) {
            $GLOBALS[$scope]['DATABASE_URL'] = str_replace('@localhost', '@127.0.0.1', $GLOBALS[$scope]['DATABASE_URL']);
        }
    }
    if (isset($_SERVER['DATABASE_URL'])) { putenv('DATABASE_URL='.$_SERVER['DATABASE_URL']); }
}
$kernel->boot();
$c = $kernel->getContainer();
$c->get('contao.framework')->initialize();
$db = $c->get('database_connection');
$pages = [];
foreach ($db->fetchFirstColumn("SELECT id FROM tl_page WHERE type = 'regular' AND published = '1' ORDER BY id") as $id) {
    $page = Contao\PageModel::findById($id); $page->loadDetails();
    if (!$page->protected && in_array($page->language, ['de', 'en'], true)) { $pages[$page->language] ??= $page; }
}
if (count($pages) !== 2) { throw new RuntimeException('Integration fixture needs published DE and EN pages.'); }
$check = static function (bool $ok, string $message): void { if (!$ok) { throw new RuntimeException($message); } };
$entities = new VHUG\SchemaManagerBundle\Schema\EntityGraph(
    $db, $c->get('contao.routing.content_url_generator'), $c->get('contao.cache.tag_manager'),
    new VHUG\SchemaManagerBundle\Schema\EntityMapper(),
    $c->get('request_stack')
);
$news = new VHUG\SchemaManagerBundle\Schema\NewsGraph($db, $entities, $c->get('contao.routing.content_url_generator'), $c->get('contao.string.html_decoder'), $c->get('contao.cache.tag_manager'));
$listener = new VHUG\SchemaManagerBundle\EventListener\JsonLdListener(
    $db, $c->get('request_stack'), $c->get('contao.routing.content_url_generator'),
    $c->get('contao.cache.tag_manager'), $entities, $news
);

$db->beginTransaction();
try {
    $add=static function(string $table,array $row)use($db):int{$db->insert($table,$row+['tstamp'=>time()]);return (int)$db->lastInsertId();};
    $token=bin2hex(random_bytes(6));
    $make=static function(string $type,string $name,array $extra=[])use($add,$token):int{return $add('tl_schema_entity',$extra+['name'=>$name,'entityType'=>$type,'identityBase'=>'https://example.org','entityId'=>'https://example.org/#'.$name.'-'.$token,'published'=>'1']);};
    $company=$make('Organization','company');
    $office=$make('LocalBusiness','office',['organization'=>$company,'addressRegion'=>'Baden-Württemberg','addressLocality'=>'Stuttgart','telephone'=>'+49 123','latitude'=>'48.7','longitude'=>'9.1']);
    $external=$make('Organization','network',['externalUrl'=>'https://external.example/']);
    $person=$make('Person','person',['organization'=>$company,'workLocation'=>serialize([$office])]);
    $service=$make('Service','service',['organization'=>$company]);
    $db->update('tl_schema_entity',['knowledgeTopics'=>serialize([$service,$service,$office])],['id'=>$person]);
    $db->update('tl_schema_entity',['memberOf'=>serialize([$external]),'subservices'=>serialize([$service])],['id'=>$company]);
    foreach ([$company,$office,$person,$service] as $id) {
        foreach ($pages as $locale=>$page) {$add('tl_schema_translation',['pid'=>$id,'page'=>(int)$page->id,'language'=>$locale,'catalogName'=>'Services '.$locale,'slogan'=>'Slogan '.$locale,'knowsAbout'=>'Knowledge '.$locale,'credentials'=>'Qualification '.$locale,'published'=>'1']);}
    }
    $render=static function(int $id,$page,string $lang)use($entities,$c):array {
        $request=Symfony\Component\HttpFoundation\Request::create(getenv('SCHEMA_TEST_ORIGIN')?:'https://example.test/');
        $request->attributes->set('pageModel',$page);$c->get('request_stack')->push($request);
        try{
            $manager=new Contao\CoreBundle\Routing\ResponseContext\JsonLd\JsonLdManager(new Contao\CoreBundle\Routing\ResponseContext\ResponseContext());
            $emitted=[];$entities->emit($id,$lang,$manager,$emitted);
            $nodes=$manager->getGraphForSchema($manager::SCHEMA_ORG)->toArray()['@graph'];
            return array_column($nodes,null,'@id');
        }finally{$c->get('request_stack')->pop();}
    };
    $ids=$db->fetchAllKeyValue('SELECT id,entityId FROM tl_schema_entity');
    $nodes=$render($company,$pages['de'],'de');
    $check($nodes[$ids[$company]]['location']===[['@id'=>$ids[$office]]],'Company infers offices from parent relation');
    $check($nodes[$ids[$company]]['memberOf']===[['@id'=>$ids[$external]]] && $nodes[$ids[$external]]['url']==='https://external.example/','External network reference');
    $check($nodes[$ids[$company]]['hasOfferCatalog']['name']==='Services de','Company catalogue localizes');
    $check($nodes[$ids[$office]]['parentOrganization']===['@id'=>$ids[$company]] && $nodes[$ids[$office]]['geo']['latitude']===48.7,'Office links back without recursive duplication');
    $personNodes=$render($person,$pages['en'],'en');
    $check($personNodes[$ids[$person]]['workLocation']===[['@id'=>$ids[$office]]] && $personNodes[$ids[$person]]['hasCredential'][0]['name']==='Qualification en','Workplace and localized credential');
    $check($personNodes[$ids[$person]]['knowsAbout']===['Knowledge en',['@id'=>$ids[$service]],['@id'=>$ids[$office]]], 'Localized text and deduplicated subject links coexist');
    $dePerson=$render($person,$pages['de'],'de');
    $check($dePerson[$ids[$person]]['knowsAbout'][0]==='Knowledge de', 'Knowledge text localizes while linked identities remain stable');
    $other=clone $pages['en'];$other->id=2147483000;
    $compact=$render($office,$other,'en');
    $check(!isset($compact[$ids[$office]]['address']) && count($compact)===2,'Unrelated page contains compact office/company only, not catalogue/network');
    $other->schemaLocationOverview='1';$overview=$render($office,$other,'en');
    $check($overview[$ids[$office]]['address']['addressLocality']==='Stuttgart' && $overview[$ids[$office]]['telephone']==='+49 123','Location overview keeps NAP');
    $db->update('tl_schema_entity',['published'=>''],['id'=>$office]);
    $check(!isset($render($company,$pages['de'],'de')[$ids[$company]]['location']),'Unpublished office excluded');
    $personWithoutOffice=$render($person,$pages['en'],'en');
    $check($personWithoutOffice[$ids[$person]]['knowsAbout']===['Knowledge en',['@id'=>$ids[$service]]], 'Unpublished knowledge topic omitted');
    $identity=new VHUG\SchemaManagerBundle\Schema\EntityIdentity($db);
    $replacement='https://example.org/#established-'.$token;
    $identity->adopt($service,$ids[$service],$replacement);
    $check($db->fetchOne('SELECT entityId FROM tl_schema_entity WHERE id=?',[$service])===$ids[$service],'ID dry run does not mutate');
    try{$identity->adopt($service,'wrong',$replacement,true);throw new LogicException('Stale expected ID accepted');}catch(InvalidArgumentException $expected){}
    try{$identity->adopt($service,$ids[$service],$ids[$company],true);throw new LogicException('Duplicate ID accepted');}catch(InvalidArgumentException $expected){}
    $identity->adopt($service,$ids[$service],$replacement,true);
    $check($db->fetchOne('SELECT entityId FROM tl_schema_entity WHERE id=?',[$service])===$replacement,'Established ID adopted');
    $v=new VHUG\SchemaManagerBundle\EventListener\BusinessDetailsListener($db);
    $check($v->latitude('48,7')==='48.7' && $v->hours('Mo-Fr 09:00-17:00')==='Mo-Fr 09:00-17:00','Office inputs normalize');
    foreach ([['latitude','91'],['longitude','-181'],['hours','Monday 9-5'],['employees','4.5'],['url','javascript:alert(1)']] as [$method,$value]) {
        try{$v->$method($value);throw new LogicException('Invalid office field accepted');}catch(InvalidArgumentException $expected){}
    }
    echo "PASS: real office/company graphs, catalogues, external memberships, workplace/credentials, compact/NAP scopes, ID adoption and field validation.\n";
}finally{$db->rollBack();echo "Business fixtures rolled back.\n";}
