<?php

declare(strict_types=1);

/*
 * Node Bundle for Contao Open Source CMS.
 *
 * @copyright  Copyright (c) 2019, terminal42 gmbh
 * @author     terminal42 <https://terminal42.ch>
 * @license    MIT
 */

namespace Terminal42\NodeBundle\ContentElement;

use Contao\BackendTemplate;
use Contao\ContentElement;
use Contao\StringUtil;
use Contao\System;
use Patchwork\Utf8;
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
            if (TL_MODE === 'BE') {
                return sprintf('<strong class="tl_red">%s</strong>', $GLOBALS['TL_LANG']['ERR']['circularReference']);
            }

            return '';
        }

        // Display the backend wildcard
        if (TL_MODE === 'BE') {
            return static::generateBackendWildcard($this->arrData, $ids);
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

        $nodeModels = NodeModel::findBy(
            ['id IN ('.implode(',', $ids).')', 'type=?'],
            [NodeModel::TYPE_CONTENT, implode(',', $ids)],
            ['order' => 'FIND_IN_SET(`id`, ?)']
        );

        if (null !== $nodeModels) {
            $router = System::getContainer()->get('router');

            /** @var NodeModel $nodeModel */
            foreach ($nodeModels as $nodeModel) {
                $nodes[] = sprintf(
                    '<a href="%s" class="tl_gray" target="_blank">%s (ID: %s)</a>',
                    $router->generate('contao_backend', ['do' => 'nodes', 'table' => 'tl_content', 'id' => $nodeModel->id]),
                    $nodeModel->name,
                    $nodeModel->id
                );
            }
        }

        $wildcard = '### '.Utf8::strtoupper($GLOBALS['TL_LANG']['FMD'][$data['type']][0]).' ###';

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
