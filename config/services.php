<?php

declare(strict_types=1);

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $containerConfigurator): void {
    $services = $containerConfigurator->services();

    $services->defaults()
        ->autoconfigure()
        ->autowire()
    ;

    $services
        ->load('Terminal42\\NodeBundle\\', __DIR__ . '/../src')
        ->exclude(__DIR__ . '/../src/ContaoManager')
        ->exclude(__DIR__ . '/../src/DependencyInjection')
        ->exclude(__DIR__ . '/../src/Model')
        ->exclude(__DIR__ . '/../src/Widget')
        ->exclude(__DIR__ . '/../src/Terminal42NodeBundle.php')
    ;
};
