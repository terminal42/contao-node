<?php

declare(strict_types=1);

namespace Terminal42\NodeBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Terminal42\Geoip2CountryBundle\DependencyInjection\Configuration;

class Terminal42NodeExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $loader = new YamlFileLoader($container, new FileLocator(__DIR__.'/../../config'));
        $loader->load('services.yml');

        if (class_exists(Configuration::class)) {
            Configuration::addDefaultTable('tl_node');
        }
    }
}
