<?php
declare(strict_types=1);
$GLOBALS['TL_DCA']['tl_schema_ai_run'] = [
    'config'=>['sql'=>['keys'=>['id'=>'primary','owner,root'=>'index']]],
    'fields'=>[
        'id'=>['sql'=>'int unsigned NOT NULL auto_increment'],
        'tstamp'=>['sql'=>"int unsigned NOT NULL default 0"],
        'owner'=>['sql'=>"int unsigned NOT NULL default 0"],
        'root'=>['sql'=>"int unsigned NOT NULL default 0"],
        'data'=>['sql'=>'longtext NULL'],
    ],
];
