<?php

declare(strict_types=1);

namespace Terminal42\NodeBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class Terminal42NodeBundle extends Bundle
{
    public function getPath(): string
    {
        return \dirname(__DIR__);
    }
}
