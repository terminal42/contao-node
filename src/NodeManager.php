<?php

declare(strict_types=1);

/*
 * Node Bundle for Contao Open Source CMS.
 *
 * @copyright  Copyright (c) 2019, terminal42 gmbh
 * @author     terminal42 <https://terminal42.ch>
 * @license    MIT
 */

namespace Terminal42\NodeBundle;

use Contao\ContentModel;
use Contao\Controller;
use Contao\FrontendTemplate;
use Contao\StringUtil;
use Terminal42\NodeBundle\Model\NodeModel;

class NodeManager
{
    /**
     * Generate single node.
     */
    public function generateSingle(int $id): ?string
    {
        if (!$id) {
            return null;
        }

        if (null === ($nodeModel = NodeModel::findOneBy(['id=?', 'type=?'], [$id, NodeModel::TYPE_CONTENT]))) {
            return null;
        }

        return $this->generateBuffer($nodeModel);
    }

    /**
     * Generate multiple nodes.
     */
    public function generateMultiple(array $ids): array
    {
        $ids = array_filter($ids);

        if (0 === \count($ids)) {
            return [];
        }

        $ids = array_map('intval', $ids);

        $nodeModels = NodeModel::findBy(
            ['id IN ('.implode(',', $ids).')', 'type=?'],
            [NodeModel::TYPE_CONTENT, implode(',', $ids)],
            ['order' => 'FIND_IN_SET(`id`, ?)']
        );

        if (null === $nodeModels) {
            return [];
        }

        $nodes = [];

        /** @var NodeModel $nodeModel */
        foreach ($nodeModels as $nodeModel) {
            $nodes[$nodeModel->id] = $this->generateBuffer($nodeModel);
        }

        return array_filter($nodes, static function ($buffer) {
            return null !== $buffer;
        });
    }

    /**
     * Generate the node buffer (content elements).
     */
    private function generateBuffer(NodeModel $nodeModel): string
    {
        if (!Controller::isVisibleElement($nodeModel)) {
            return '';
        }

        $buffer = '';
        $elementsData = [];

        if (null !== ($elements = $nodeModel->getContentElements())) {
            /** @var ContentModel $element */
            foreach ($elements as $index => $element) {
                $elementsData[] = $element->row();
                $element->nodeElementIndex = $index;
                $buffer .= Controller::getContentElement($element);
            }
        }

        if (!$nodeModel->wrapper) {
            return $buffer;
        }

        $template = new FrontendTemplate($nodeModel->nodeTpl ?: 'node_default');
        $template->setData($nodeModel->row());
        $template->elementsData = $elementsData;

        $cssID = StringUtil::deserialize($nodeModel->cssID, true);

        $template->class = !empty($cssID[1]) ? $cssID[1] : '';
        $template->cssID = !empty($cssID[0]) ? $cssID[0] : '';
        $template->buffer = $buffer;

        return $template->parse();
    }
}
