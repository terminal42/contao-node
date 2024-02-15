<?php

declare(strict_types=1);

namespace Terminal42\NodeBundle\ContentElement;

use Contao\BackendTemplate;
use Contao\ContentElement;
use Contao\CoreBundle\Routing\ScopeMatcher;
use Contao\StringUtil;
use Contao\System;
use Symfony\Component\HttpFoundation\Request;
use Terminal42\NodeBundle\Model\NodeModel;

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
            /** @var Request $request */
            $request = System::getContainer()->get('request_stack')->getCurrentRequest();

            if (null !== $request) {
                /** @var ScopeMatcher $scopeMatcher */
                $scopeMatcher = System::getContainer()->get('contao.routing.scope_matcher');

                if ($scopeMatcher->isBackendRequest($request)) {
                    return sprintf('<strong class="tl_red">%s</strong>', $GLOBALS['TL_LANG']['ERR']['circularReference']);
                }
            }

            return '';
        }

        /** @var Request $request */
        $request = System::getContainer()->get('request_stack')->getCurrentRequest();

        // Display the backend wildcard
        if (null !== $request) {
            /** @var ScopeMatcher $scopeMatcher */
            $scopeMatcher = System::getContainer()->get('contao.routing.scope_matcher');

            if ($scopeMatcher->isBackendRequest($request)) {
                return static::generateBackendWildcard($this->arrData, $ids);
            }
        }

        $this->nodes = System::getContainer()->get('terminal42_node.manager')->generateMultiple($ids);

        if (0 === \count($this->nodes)) {
            return '';
        }

        return parent::generate();
    }

    /**
     * Generate a wildcard in the backend.
     */
    public static function generateBackendWildcard(array $data, array $ids): string
    {
        $nodes = [];

        $ids = array_map('intval', $ids);

        $nodeModels = NodeModel::findBy(
            ['id IN ('.implode(',', $ids).')', 'type=?'],
            [NodeModel::TYPE_CONTENT, implode(',', $ids)],
            ['order' => 'FIND_IN_SET(`id`, ?)'],
        );

        if (null !== $nodeModels) {
            $router = System::getContainer()->get('router');

            /** @var NodeModel $nodeModel */
            foreach ($nodeModels as $nodeModel) {
                $nodes[] = sprintf(
                    '<a href="%s" class="tl_gray" target="_blank">%s (ID: %s)</a>',
                    $router->generate('contao_backend', ['do' => 'nodes', 'table' => 'tl_content', 'id' => $nodeModel->id]),
                    $nodeModel->name,
                    $nodeModel->id,
                );
            }
        }

        $wildcard = '### '.mb_strtoupper($GLOBALS['TL_LANG']['FMD'][$data['type']][0]).' ###';

        // Add nodes
        if (\count($nodes) > 0) {
            $wildcard .= '<br><p>'.implode('<br>', $nodes).'</p>';
        }

        $template = new BackendTemplate('be_wildcard');
        $template->wildcard = $wildcard;

        return $template->parse();
    }

    /**
     * Generate the module.
     */
    protected function compile(): void
    {
        $this->Template->nodes = $this->nodes;
    }
}
