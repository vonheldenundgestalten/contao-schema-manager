<?php
declare(strict_types=1);
namespace VHUG\SchemaManagerBundle\Schema;
/** The same fields are archive defaults and optional record overrides. */
final class JobFields
{
    public const EMPLOYMENT = ['FULL_TIME', 'PART_TIME', 'CONTRACTOR', 'TEMPORARY', 'INTERN', 'VOLUNTEER', 'PER_DIEM', 'OTHER'];
    public static function defaults(): array
    {
        $fields = [
            'schemaJobEmployment' => ['inputType' => 'select', 'options' => self::EMPLOYMENT, 'eval' => ['multiple' => true, 'chosen' => true, 'tl_class' => 'clr'], 'sql' => 'blob NULL'],
            'schemaJobWorkplace' => ['inputType' => 'select', 'options' => ['', 'onsite', 'hybrid', 'remote'], 'reference' => &$GLOBALS['TL_LANG']['MSC']['schemaJobWorkplaces'], 'eval' => ['tl_class' => 'w50'], 'sql' => "varchar(16) NOT NULL default ''"],
            'schemaJobApplicantCountries' => ['inputType' => 'select', 'options_callback' => [\VHUG\SchemaManagerBundle\EventListener\EntityDetailsListener::class, 'countries'], 'eval' => ['multiple' => true, 'chosen' => true, 'tl_class' => 'clr'], 'sql' => 'blob NULL'],
        ];
        foreach (['LocationName', 'Street', 'PostalCode', 'City', 'Region'] as $name) {
            $fields['schemaJob'.$name] = ['inputType' => 'text', 'eval' => ['maxlength' => 255, 'tl_class' => 'w50'], 'sql' => "varchar(255) NOT NULL default ''"];
        }
        $fields['schemaJobCountry'] = ['inputType' => 'select', 'options_callback' => [\VHUG\SchemaManagerBundle\EventListener\EntityDetailsListener::class, 'countries'], 'eval' => ['includeBlankOption' => true, 'chosen' => true, 'tl_class' => 'w50'], 'sql' => "varchar(2) NOT NULL default ''"];
        return $fields;
    }
}
