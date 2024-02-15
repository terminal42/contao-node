<?php

declare(strict_types=1);

namespace Terminal42\NodeBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\DependencyInjection\Reference;
use Terminal42\Geoip2CountryBundle\EventListener\DcaLoaderListener;

class Terminal42NodeExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $loader = new YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yml');

        if (class_exists(DcaLoaderListener::class)) {
            $definition = new Definition(DcaLoaderListener::class, [new Reference('database_connection'), new Reference('translator'), ['tl_node']]);
            $definition->addTag('contao.hook', ['hook' => 'loadDataContainer']);
            $container->setDefinition('terminal42_node.listener.geoip2country_dca_loader', $definition);
        }
    }
}
