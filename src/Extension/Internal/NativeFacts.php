<?php
declare(strict_types=1);
namespace VHUG\SchemaManagerBundle\Extension\Internal;
use VHUG\SchemaManagerBundle\Extension\V1 as Api;
/** Source adapters may reuse the existing core scalar mapper; richer facts use contributed definitions. */
final class NativeFacts
{
    public static function fields(string $type): array
    {
        $global=['name'];$localized=['description'];
        if(in_array($type,['Organization','LocalBusiness'],true)) {
            $global=array_merge($global,['legalName','alternateName','foundingDate','telephone','email','faxNumber','numberOfEmployees','award']);
            $localized=array_merge($localized,['knowsAbout','slogan']);
        }
        if(in_array($type,['Organization','LocalBusiness','Place','Event'],true))$global=array_merge($global,['streetAddress','postalCode','addressLocality','addressRegion','addressCountry','postOfficeBoxNumber','latitude','longitude','hasMap']);
        if($type==='LocalBusiness')$global=array_merge($global,['priceRange','openingHours']);
        if($type==='Person'){$global=array_merge($global,['telephone','email']);$localized=array_merge($localized,['jobTitle','knowsAbout','credentials','award']);}
        if(in_array($type,['Product','Service','SoftwareApplication','Place','Event'],true))$localized[]='name';
        if($type==='Product')$global=array_merge($global,['sku','mpn','brand']);
        if($type==='Service')$localized=array_merge($localized,['serviceType','audienceType']);
        if($type==='SoftwareApplication'){$global=array_merge($global,['applicationCategory','operatingSystem','softwareVersion','runtimePlatform']);$localized=array_merge($localized,['softwareRequirements','featureList']);}
        if($type==='Event')$global=array_merge($global,['startDate','endDate','eventStatus','eventAttendanceMode','eventUrl','locationName']);
        return [$global,$localized];
    }
    public static function validate(Api\SourceSnapshot $snapshot): void
    {
        foreach(array_merge($snapshot->globalFields,$snapshot->localizedFields) as $value)if($value!==null && !is_string($value))throw new \InvalidArgumentException('Core source facts require strings or null.');
    }
}
