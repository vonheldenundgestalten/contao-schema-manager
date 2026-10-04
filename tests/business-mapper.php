<?php
declare(strict_types=1);
require __DIR__.'/../src/Schema/EntityMapper.php';
require __DIR__.'/../src/Schema/EntityIdentity.php';
use VHUG\SchemaManagerBundle\Schema\EntityIdentity;
$m=new VHUG\SchemaManagerBundle\Schema\EntityMapper();
$check=static function(bool $ok,string $label):void{if(!$ok){throw new RuntimeException($label);}};
$base=['entityType'=>'LocalBusiness','entityId'=>'https://example.org/#office','name'=>'Office','streetAddress'=>'Main Street 1','postalCode'=>'12345','addressLocality'=>'City','addressRegion'=>'Region','postOfficeBoxNumber'=>'42','addressCountry'=>'DE','latitude'=>'48.7','longitude'=>'9.1','openingHours'=>"Mo-Fr 09:00-17:00\nSa 10:00-12:00",'numberOfEmployees'=>'450','hasMap'=>'https://example.org/map','priceRange'=>'EUR 100-200','faxNumber'=>'+49 123','award'=>"Award 2026\nAward 2026"];
$n=$m->map($base,['slogan'=>'Knowing you','knowsAbout'=>"Tax\nAudit",'award'=>"Award 2026"],null);
$check($n['address']['addressRegion']==='Region' && $n['address']['postOfficeBoxNumber']==='42','Complete address');
$check($n['geo']['latitude']===48.7 && $n['geo']['longitude']===9.1,'Numeric geo');
$check(count($n['openingHours'])===2 && $n['hasMap']==='https://example.org/map','Opening hours and map');
$check($n['numberOfEmployees']===['@type'=>'QuantitativeValue','value'=>450],'Employee quantitative value');
$check($n['knowsAbout']===['Tax','Audit'] && $n['award']===['Award 2026'] && $n['slogan']==='Knowing you','Localized expertise and shared company awards');
foreach (['Organization','LocalBusiness'] as $type) {
    foreach ([null, ['award'=>'Outdated translated award'], ['award'=>'Andere Auszeichnung']] as $translation) {
        $check($m->map(array_replace($base,['entityType'=>$type]),$translation,null)['award']===['Award 2026'],'Company awards are language-independent and deduplicated');
    }
    $check(!isset($m->map(array_replace($base,['entityType'=>$type,'award'=>'']),['award'=>'Old value'],null)['award']),'Cleared shared awards do not resurrect translation data');
}
$check($m->map(array_replace($base,['entityType'=>'Person']),['award'=>'Personal award'],null)['award']===['Personal award'],'Person awards unchanged');
$check(!isset($m->map(array_replace($base,['latitude'=>'91']),null,null)['geo']),'Invalid imported coordinates omitted');
$check(!isset($m->map(array_replace($base,['longitude'=>'']),null,null)['geo']),'Incomplete coordinate pair omitted');
$person=$m->map(array_replace($base,['entityType'=>'Person']),['credentials'=>"Tax advisor\nTax advisor\nAuditor"],null);
$check(count($person['hasCredential'])===2 && $person['hasCredential'][0]['@type']==='EducationalOccupationalCredential','Credential nodes deduplicated');
$check(!isset($person['geo'],$person['address'],$person['numberOfEmployees']),'Fields do not leak across entity types');
$event=array_replace($base,['entityType'=>'Event','startDate'=>'2026-11-01','eventStatus'=>'EventScheduled','locationName'=>'Venue','eventAttendanceMode'=>'MixedEventAttendanceMode','eventUrl'=>'https://example.org/live']);
$e=$m->map($event,null,null);
$check(count($e['location'])===2 && $e['location'][0]['address']['addressLocality']==='City' && $e['location'][1]['@type']==='VirtualLocation','Mixed event venue and online location');
$online=$m->map(array_replace($event,['eventAttendanceMode'=>'OnlineEventAttendanceMode']),null,null);
$check($online['location']['@type']==='VirtualLocation' && !isset($online['address']),'Online event has no stale physical location');
$check(EntityIdentity::validate('https://example.org/#organization','https://example.org')==='https://example.org/#organization','Established ID accepted');
foreach (['https://elsewhere.org/#id','javascript:alert(1)','https://user:pass@example.org/#id'] as $id) {
    try{EntityIdentity::validate($id,'https://example.org');throw new LogicException('Unsafe ID accepted');}catch(InvalidArgumentException $expected){}
}
echo "PASS: office, company, credential, event and identity mapping checks.\n";
