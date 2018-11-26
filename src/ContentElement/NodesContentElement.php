<?php

/*
 * Node Bundle for Contao Open Source CMS.
 *
 * @copyright  Copyright (c) 2018, terminal42 gmbh
 * @author     terminal42 <https://terminal42.ch>
 * @license    MIT
 */

namespace Terminal42\NodeBundle\ContentElement;

use Contao\ContentElement;
use Contao\StringUtil;
use Contao\System;

class NodesContentElement extends ContentElement
{
    /**
     * Template.
     *
     * @var string
     */
    protected $strTemplate = 'ce_nodes';

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
        if (count($ids = StringUtil::deserialize($this->objModel->nodes, true)) === 0) {
            return '';
        }

        $this->nodes = System::getContainer()->get('terminal42_node.manager')->generateMultiple($ids);

        if (count($this->nodes) === 0) {
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
