<?php

declare(strict_types=1);

use PhpCsFixer\Fixer\Comment\HeaderCommentFixer;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symplify\EasyCodingStandard\ValueObject\Option;

return static function (ContainerConfigurator $containerConfigurator): void {
    $containerConfigurator->import(__DIR__.'/vendor/contao/easy-coding-standard/config/set/contao.php');

    $parameters = $containerConfigurator->parameters();

    $parameters->set(Option::SKIP, [
        '*/Resources/*',
        HeaderCommentFixer::class => null
    ]);
    $parameters->set(Option::CACHE_DIRECTORY, sys_get_temp_dir().'/ecs_self_cache');
};
