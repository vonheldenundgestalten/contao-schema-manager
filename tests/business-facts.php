<?php
declare(strict_types=1);
require __DIR__.'/../src/Schema/BusinessFacts.php';
use VHUG\SchemaManagerBundle\Schema\BusinessFacts;
$check=static function(bool $ok,string $label):void { if(!$ok)throw new RuntimeException($label); };
$check(BusinessFacts::areaServed(['DE','EE'],true)===['@type'=>'AdministrativeArea','name'=>'Worldwide'],'Worldwide overrides countries');
$check(BusinessFacts::areaServed(['DE','EE'],false)===['DE','EE'],'Country selection unchanged');
$check(BusinessFacts::areaServed([],false)===[],'Empty coverage omitted');
$rows=[['key'=>'Commercial Register Estonia','value'=>'17334484'],['key'=>'Other register','value'=>'00123']];
$nodes=BusinessFacts::registrations(array_merge($rows,[$rows[0],['key'=>'','value'=>'']]));
$check(count($nodes)===2&&$nodes[0]===['@type'=>'PropertyValue','name'=>'Commercial Register Estonia','value'=>'17334484'],'Typed identifiers, blanks omitted and duplicates removed');
$check($nodes[1]['value']==='00123','Leading zeroes preserved');
try {BusinessFacts::registrations([['key'=>'Register','value'=>'']]);throw new LogicException('Incomplete row accepted');}catch(InvalidArgumentException){}
echo "PASS: worldwide coverage, named registration identifiers, leading zeroes and incomplete-row validation.\n";

$translated=BusinessFacts::registrations($rows,[['key'=>'17334484','value'=>'Handelsregister Estland']]);
$check($translated[0]['value']==='17334484' && $translated[0]['name']==='Handelsregister Estland','Translated label preserves fixed identifier');
$check($translated[1]['name']==='Other register','Missing translation uses default register name');
