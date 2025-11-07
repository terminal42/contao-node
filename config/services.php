<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Terminal42\NodeBundle\InsertTag\NodeInsertTag;
use Terminal42\NodeBundle\NodeManager;
use Terminal42\NodeBundle\Picker\NodePickerProvider;

return static function (ContainerConfigurator $containerConfigurator): void {
    $services = $containerConfigurator->services();
    $services->defaults()->autoconfigure();

    $services
        ->set(NodeInsertTag::class)
        ->arg('$manager', service(NodeManager::class))
        ->arg('$logger', service('monolog.logger.contao'))
    ;

    $services
        ->set(NodeManager::class)
        ->arg('$twig', service('twig'))
    ;

    $services
        ->set(NodePickerProvider::class)
        ->arg('$menuFactory', service('knp_menu.factory'))
        ->arg('$router', service('router'))
        ->arg('$translator', service('translator'))
    ;
};
