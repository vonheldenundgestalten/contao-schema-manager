<?php
declare(strict_types=1);
namespace VHUG\SchemaManagerBundle\ContaoManager;
use Contao\CoreBundle\ContaoCoreBundle;
use Contao\ManagerPlugin\Bundle\BundlePluginInterface;
use Contao\ManagerPlugin\Bundle\Config\BundleConfig;
use Contao\ManagerPlugin\Bundle\Parser\ParserInterface;
use Contao\ManagerPlugin\Config\ConfigPluginInterface;
use Symfony\Component\Config\Loader\LoaderInterface;
use VHUG\SchemaManagerBundle\SchemaManagerBundle;
final class Plugin implements BundlePluginInterface, ConfigPluginInterface
{
    public function getBundles(ParserInterface $parser): array
    {
        $after = [ContaoCoreBundle::class];
        if (class_exists(\Contao\NewsBundle\ContaoNewsBundle::class)) { $after[] = \Contao\NewsBundle\ContaoNewsBundle::class; }
        if(class_exists(\Contao\CalendarBundle\ContaoCalendarBundle::class)){$after[]=\Contao\CalendarBundle\ContaoCalendarBundle::class;}
        return [BundleConfig::create(SchemaManagerBundle::class)->setLoadAfter($after)];
    }
    public function registerContainerConfiguration(LoaderInterface $loader, array $config): void
    {
        $loader->load('@SchemaManagerBundle/config/services.yaml');
    }
}
