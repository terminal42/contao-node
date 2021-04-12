<?php

declare(strict_types=1);

/*
 * Node Bundle for Contao Open Source CMS.
 *
 * @copyright  Copyright (c) 2019, terminal42 gmbh
 * @author     terminal42 <https://terminal42.ch>
 * @license    MIT
 */

namespace Terminal42\NodeBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

final class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('terminal42_node');
        $treeBuilder
            ->getRootNode()
            ->children()
                ->booleanNode('geoip2country')
                    ->defaultTrue()
                    ->info('Enable integration with terminal42/contao-geoip2country extension (when installed).')
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
