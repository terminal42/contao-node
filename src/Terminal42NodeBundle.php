<?php

declare(strict_types=1);

/*
 * Node Bundle for Contao Open Source CMS.
 *
 * @copyright  Copyright (c) 2018, terminal42 gmbh
 * @author     terminal42 <https://terminal42.ch>
 * @license    MIT
 */

namespace Terminal42\NodeBundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Terminal42\NodeBundle\DependencyInjection\Compiler\ConfigProviderPass;

class Terminal42NodeBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new ConfigProviderPass(
            'terminal42_url_rewrite.provider',
            'terminal42_url_rewrite.provider.chain',
            'terminal42_url_rewrite.provider'
        ));
    }
}
