<?php

declare(strict_types=1);

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Terminal42\NodeBundle\Security\Voter\BackendAccessVoter;
use Terminal42\NodeBundle\Security\Voter\NodeContentVoter;
use Terminal42\NodeBundle\Security\Voter\NodePermissionVoter;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $containerConfigurator): void {
    $services = $containerConfigurator->services();
    $services->defaults()->autoconfigure();

    $services
        ->set(BackendAccessVoter::class)
        ->decorate('contao.security.backend_access_voter')
        ->arg('$contaoFramework', service('contao.framework'))
        ->arg('$inner', service('.inner'))
    ;

    $services
        ->set(NodeContentVoter::class)
        ->arg('$accessDecisionManager', service('security.access.decision_manager'))
        ->arg('$connection', service('database_connection'))
    ;

    $services
        ->set(NodePermissionVoter::class)
        ->arg('$accessDecisionManager', service('security.access.decision_manager'))
        ->arg('$contaoFramework', service('contao.framework'))
    ;
};
