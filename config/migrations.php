<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Terminal42\NodeBundle\Migration\GuestsMigration;

return static function (ContainerConfigurator $containerConfigurator): void {
    $services = $containerConfigurator->services();
    $services->defaults()->autoconfigure();

    $services
        ->set(GuestsMigration::class)
        ->arg('$connection', service('database_connection'))
    ;
};
