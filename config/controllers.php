<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Terminal42\NodeBundle\Controller\ContentElement\NodesController as NodesContentElementController;
use Terminal42\NodeBundle\Controller\FrontendModule\NodesController as NodesFrontendModuleController;
use Terminal42\NodeBundle\NodeManager;

return static function (ContainerConfigurator $containerConfigurator): void {
    $services = $containerConfigurator->services();
    $services->defaults()->autoconfigure();

    $services
        ->set(NodesContentElementController::class)
        ->arg('$nodeManager', service(NodeManager::class))
    ;

    $services
        ->set(NodesFrontendModuleController::class)
        ->arg('$nodeManager', service(NodeManager::class))
    ;
};
