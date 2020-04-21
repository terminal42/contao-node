<?php

declare(strict_types=1);

/*
 * Node Bundle for Contao Open Source CMS.
 *
 * @copyright  Copyright (c) 2019, terminal42 gmbh
 * @author     terminal42 <https://terminal42.ch>
 * @license    MIT
 */

namespace Terminal42\NodeBundle\ContaoManager;

use Contao\CoreBundle\ContaoCoreBundle;
use Contao\ManagerPlugin\Bundle\BundlePluginInterface;
use Contao\ManagerPlugin\Bundle\Config\BundleConfig;
use Contao\ManagerPlugin\Bundle\Parser\ParserInterface;
use Contao\ManagerPlugin\Config\ContainerBuilder;
use Contao\ManagerPlugin\Config\ExtensionPluginInterface;
use Terminal42\NodeBundle\Terminal42NodeBundle;

class Plugin implements BundlePluginInterface, ExtensionPluginInterface
{
    /**
     * {@inheritdoc}
     */
    public function getBundles(ParserInterface $parser): array
    {
        return [
            BundleConfig::create(Terminal42NodeBundle::class)->setLoadAfter([ContaoCoreBundle::class]),
        ];
    }

    /**
     * @inheritDoc
     */
    public function getExtensionConfig($extensionName, array $extensionConfigs, ContainerBuilder $container)
    {
        if ($extensionName === 'codefog_tags' && !isset($extensionConfigs[0]['managers']['terminal42_node'])) {
            $extensionConfigs[0]['managers']['terminal42_node'] = [
                'source' => 'tl_node.tags'
            ];
        }

        return $extensionConfigs;
    }
}
