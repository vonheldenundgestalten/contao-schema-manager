<?php
declare(strict_types=1);
require __DIR__.'/../src/Schema/PriceParser.php';
$parser = new VHUG\SchemaManagerBundle\Schema\PriceParser();
$check = static function(bool $ok,string $message):void { if (!$ok) { throw new RuntimeException($message); } };
$a=$parser->parse('**10 €**/month');
$check($a['price']==='10' && !isset($a['minPrice']) && $a['referenceQuantity']['unitCode']==='MON','Exact monthly price');
$b=$parser->parse('Ab **60 €**/monatlich');
$check($b['minPrice']==='60' && !isset($b['price']),'Starting price is not an exact price');
$check($parser->parse('From  **1.200 €**')['minPrice']==='1200','Grouped amount');
$check($parser->parse('19,95 EUR')['price']==='19.95','Decimal comma');
$check($parser->parse('120 EUR/year')['referenceQuantity']['unitCode']==='ANN','Annual unit');
foreach(['On request','Nach Bedarf','free maybe','1,200.00 EUR','-10 EUR'] as $text){$check($parser->parse($text)===[],'No invented amount: '.$text);}
echo "PASS: 10 exact, minimum, billing-unit and unknown-price checks.\n";
