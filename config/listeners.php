<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Terminal42\NodeBundle\EventListener\ContentListener;
use Terminal42\NodeBundle\EventListener\DataContainerListener;

return static function (ContainerConfigurator $containerConfigurator): void {
    $services = $containerConfigurator->services();
    $services->defaults()->autoconfigure();

    $services
        ->set(DataContainerListener::class)
        ->arg('$connection', service('database_connection'))
        ->arg('$finderFactory', service('contao.twig.finder_factory'))
        ->arg('$locales', service('contao.intl.locales'))
        ->arg('$logger', service('monolog.logger.contao'))
        ->arg('$requestStack', service('request_stack'))
        ->arg('$security', service('security.helper'))
        ->arg('$tagsManager', service('codefog_tags.manager.terminal42_node'))
        ->arg('$translator', service('translator'))
    ;

    $services
        ->set(ContentListener::class)
        ->arg('$connection', service('database_connection'))
    ;
};
