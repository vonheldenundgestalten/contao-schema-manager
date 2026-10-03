<?php
declare(strict_types=1);
require __DIR__.'/../src/Metadata/ImageSelection.php';
$picker=new VHUG\SchemaManagerBundle\Metadata\ImageSelection();
$all=[];foreach(['page','pageimage','news','hero','fallback'] as $key){$all[$key]=['uuid'=>$key];}
$check=static function($ok,$message){if(!$ok){throw new RuntimeException($message);}};
$check(array_column($picker->candidates('auto',$all),'source')===['news','hero','page','pageimage','fallback'],'Automatic precedence');
$check(array_column($picker->candidates('override',$all),'source')===['page','pageimage','news','hero','fallback'],'Explicit override precedence');
$check($picker->candidates('none',$all)===[],'Explicit suppression');
$check($picker->candidates('auto',[])===[],'No invented image');
unset($all['news']);
$check($picker->candidates('auto',$all)[0]['source']==='hero','Hero before page fallback');
unset($all['hero'],$all['page']);
$check($picker->candidates('auto',$all)[0]['source']==='pageimage','Reuse explicit pageimage');
unset($all['pageimage']);
$check($picker->candidates('auto',$all)[0]['source']==='fallback','Root fallback');
$check($picker->candidates('override',$all)[0]['source']==='fallback','Missing override safely falls back');
echo "PASS: 8 representative-image selection checks.\n";
