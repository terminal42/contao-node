<?php

/*
 * Node Bundle for Contao Open Source CMS.
 *
 * @copyright  Copyright (c) 2019, terminal42 gmbh
 * @author     terminal42 <https://terminal42.ch>
 * @license    MIT
 */

namespace Terminal42\NodeBundle\FrontendModule;

use Contao\Module;
use Contao\StringUtil;
use Contao\System;

class NodesModule extends Module
{
    /**
     * Template.
     *
     * @var string
     */
    protected $strTemplate = 'mod_nodes';

    /**
     * @var array
     */
    protected $nodes;

    /**
     * Display a wildcard in the back end.
     *
     * @return string
     */
    public function generate()
    {
        if (0 === \count($ids = StringUtil::deserialize($this->objModel->nodes, true))) {
            return '';
        }

        $this->nodes = System::getContainer()->get('terminal42_node.manager')->generateMultiple($ids);

        if (0 === \count($this->nodes)) {
            return '';
        }

        return parent::generate();
    }

    /**
     * Generate the module.
     */
    protected function compile()
    {
        $this->Template->nodes = $this->nodes;
    }
}
