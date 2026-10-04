<?php
declare(strict_types=1);
$GLOBALS['BE_MOD']['content']['schema_manager'] = ['tables' => ['tl_schema_entity', 'tl_schema_translation', 'tl_schema_contact']];
$GLOBALS['TL_MODELS']['tl_schema_entity'] = VHUG\SchemaManagerBundle\Model\EntityModel::class;
$GLOBALS['TL_MODELS']['tl_schema_translation'] = VHUG\SchemaManagerBundle\Model\TranslationModel::class;

$GLOBALS['TL_MODELS']['tl_schema_contact'] = VHUG\SchemaManagerBundle\Model\ContactModel::class;

$GLOBALS['BE_MOD']['content']['schema_manager']['relationships'] = [VHUG\SchemaManagerBundle\Backend\RelationshipMapModule::class, 'generate'];
