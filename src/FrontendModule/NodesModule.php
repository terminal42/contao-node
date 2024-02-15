<?php

declare(strict_types=1);

namespace Terminal42\NodeBundle\FrontendModule;

use Contao\CoreBundle\Routing\ScopeMatcher;
use Contao\Module;
use Contao\StringUtil;
use Contao\System;
use Symfony\Component\HttpFoundation\Request;
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

        /** @var Request $request */
        $request = System::getContainer()->get('request_stack')->getCurrentRequest();

        // Display the backend wildcard
        if (null !== $request) {
            /** @var ScopeMatcher $scopeMatcher */
            $scopeMatcher = System::getContainer()->get('contao.routing.scope_matcher');

            if ($scopeMatcher->isBackendRequest($request)) {
                return NodesContentElement::generateBackendWildcard($this->arrData, $ids);
            }
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
