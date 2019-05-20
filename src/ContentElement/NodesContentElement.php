<?php

/*
 * Node Bundle for Contao Open Source CMS.
 *
 * @copyright  Copyright (c) 2019, terminal42 gmbh
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
        if (0 === \count($ids = StringUtil::deserialize($this->objModel->nodes, true))) {
            return '';
        }

        $ids = array_map('intval', $ids);

        // Check for potential circular reference
        if ('tl_node' === $this->objModel->ptable && \in_array((int) $this->objModel->pid, $ids, true)) {
            if (TL_MODE === 'BE') {
                return sprintf('<strong class="tl_red">%s</strong>', $GLOBALS['TL_LANG']['ERR']['circularReference']);
            }

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
