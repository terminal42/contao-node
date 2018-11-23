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

class NodesContentElement extends ContentElement
{
    /**
     * Template.
     *
     * @var string
     */
    protected $strTemplate = 'ce_nodes';

    /**
     * Display a wildcard in the back end.
     *
     * @return string
     */
    public function generate()
    {
        // @todo

        return parent::generate();
    }

    /**
     * Generate the module.
     */
    protected function compile()
    {
    }
}
