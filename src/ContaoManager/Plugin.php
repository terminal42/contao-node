<?php

declare(strict_types=1);

namespace Terminal42\NodeBundle\ContaoManager;

use Contao\CoreBundle\ContaoCoreBundle;
use Contao\ManagerPlugin\Bundle\BundlePluginInterface;
use Contao\ManagerPlugin\Bundle\Config\BundleConfig;
use Contao\ManagerPlugin\Bundle\Parser\ParserInterface;
use Contao\ManagerPlugin\Config\ContainerBuilder;
use Contao\ManagerPlugin\Config\ExtensionPluginInterface;
use Terminal42\Geoip2CountryBundle\Terminal42Geoip2CountryBundle;
use Terminal42\NodeBundle\Terminal42NodeBundle;

class Plugin implements BundlePluginInterface, ExtensionPluginInterface
{
    public function getBundles(ParserInterface $parser): array
    {
        return [
            BundleConfig::create(Terminal42NodeBundle::class)
                ->setLoadAfter([ContaoCoreBundle::class, 'haste', Terminal42Geoip2CountryBundle::class]),
        ];
    }

    public function getExtensionConfig($extensionName, array $extensionConfigs, ContainerBuilder $container)
    {
        if ('codefog_tags' === $extensionName && !isset($extensionConfigs[0]['managers']['terminal42_node'])) {
            $extensionConfigs[0]['managers']['terminal42_node'] = [
                'source' => 'tl_node.tags',
            ];
        }

        if ('rocksolid_frontend_helper' === $extensionName && !isset($extensionConfigs[0]['backend_modules']['nodes'])) {
            $extensionConfigs[0]['backend_modules']['nodes'] = [
                'table' => 'tl_node',
                'act' => 'edit',
                'column' => 'nodes',
                'column_type' => 'serialized',
                'ce_column' => 'nodes',
                'ce_column_type' => 'serialized',
                'icon' => 'header.svg',
                'content_elements' => [
                    'nodes',
                ],
                'fe_modules' => [
                    'nodes',
                ],
            ];
        }

        return $extensionConfigs;
    }
}
