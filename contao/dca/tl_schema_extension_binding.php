<?php
declare(strict_types=1);
$GLOBALS['TL_DCA']['tl_schema_extension_binding'] = [
 'config'=>['dataContainer'=>Contao\DC_Table::class,'sql'=>['keys'=>['id'=>'primary','sourceHash'=>'unique','entity'=>'unique']]],
 'fields'=>[
  'id'=>['sql'=>'int unsigned NOT NULL auto_increment'],
  'tstamp'=>['sql'=>"int unsigned NOT NULL default 0"],
  'sourceHash'=>['sql'=>"varchar(64) NOT NULL default ''"],
  'adapterKey'=>['sql'=>"varchar(128) NOT NULL default ''"],
  'installationScope'=>['sql'=>"varchar(255) NOT NULL default ''"],
  'subjectKey'=>['sql'=>"varchar(255) NOT NULL default ''"],
  'entity'=>['sql'=>"int unsigned NOT NULL default 0"],
 ],
];
