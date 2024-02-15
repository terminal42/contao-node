<?php

declare(strict_types=1);

/*
 * Node Bundle for Contao Open Source CMS.
 *
 * @copyright  Copyright (c) 2019, terminal42 gmbh
 * @author     terminal42 <https://terminal42.ch>
 * @license    MIT
 */

namespace Terminal42\NodeBundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class Terminal42NodeBundle extends Bundle
{
    public function getPath(): string
    {
        return \dirname(__DIR__);
    }
}
