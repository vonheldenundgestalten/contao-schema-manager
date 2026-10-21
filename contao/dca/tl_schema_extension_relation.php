<?php
declare(strict_types=1);
$GLOBALS['TL_DCA']['tl_schema_extension_relation'] = [
 'config'=>['dataContainer'=>Contao\DC_Table::class,'sql'=>['keys'=>['id'=>'primary','entity'=>'index','target'=>'index']]],
 'fields'=>[
  'id'=>['sql'=>'int unsigned NOT NULL auto_increment'],
  'entity'=>['sql'=>"int unsigned NOT NULL default 0"],
  'relationKey'=>['sql'=>"varchar(128) NOT NULL default ''"],
  'language'=>['sql'=>"varchar(16) NOT NULL default ''"],
  'target'=>['sql'=>"int unsigned NOT NULL default 0"],
  'evidence'=>['sql'=>'text NULL'],
 ],
];
