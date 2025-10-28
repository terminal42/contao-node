<?php

declare(strict_types=1);

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Terminal42\NodeBundle\Controller\ContentElement\NodesController as NodesContentElementController;
use Terminal42\NodeBundle\Controller\FrontendModule\NodesController as NodesFrontendModuleController;
use Terminal42\NodeBundle\NodeManager;

return static function (ContainerConfigurator $containerConfigurator): void {
    $services = $containerConfigurator->services();
    $services->defaults()->autoconfigure();

    $services
        ->set(NodesContentElementController::class)
        ->arg('$manager', NodeManager::class)
    ;

    $services
        ->set(NodesFrontendModuleController::class)
        ->arg('$manager', NodeManager::class)
    ;
};
