<?php

declare(strict_types=1);

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
use Terminal42\NodeBundle\ContentElement\NodesContentElement;

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

        // Display the backend wildcard
        if (TL_MODE === 'BE') {
            return NodesContentElement::generateBackendWildcard($this->arrData, $ids);
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
    protected function compile(): void
    {
        $this->Template->nodes = $this->nodes;
    }
}
