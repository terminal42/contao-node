<?php

namespace Terminal42\NodeBundle\Migration;

use Contao\CoreBundle\Migration\Version500\AbstractGuestsMigration;

class GuestsMigration extends AbstractGuestsMigration
{
    protected function getTables(): array
    {
        return ['tl_node'];
    }
}
