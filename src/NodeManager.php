<?php

namespace Terminal42\NodeBundle;

use Contao\ContentModel;
use Contao\Controller;
use Contao\CoreBundle\Framework\Adapter;
use Contao\CoreBundle\Framework\ContaoFrameworkInterface;
use Terminal42\NodeBundle\Model\NodeModel;

class NodeManager
{
    /**
     * @var ContaoFrameworkInterface
     */
    private $framework;

    /**
     * NodeManager constructor.
     *
     * @param ContaoFrameworkInterface $framework
     */
    public function __construct(ContaoFrameworkInterface $framework)
    {
        $this->framework = $framework;
    }

    /**
     * Generate single node
     *
     * @param int $id
     *
     * @return null|string
     */
    public function generateSingle(int $id): ?string
    {
        if (!$id) {
            return null;
        }

        /** @var NodeModel $nodeModelAdapter */
        $nodeModelAdapter = $this->framework->getAdapter(NodeModel::class);

        if (($nodeModel = $nodeModelAdapter->findOneBy(['id=?', 'type=?'], [$id, NodeModel::TYPE_CONTENT])) === null) {
            return null;
        }

        /** @var Controller $controllerAdapter */
        $controllerAdapter = $this->framework->getAdapter(Controller::class);

        return $this->generateBuffer($nodeModel, $controllerAdapter);
    }

    /**
     * Generate multiple nodes
     *
     * @param array $ids
     *
     * @return array
     */
    public function generateMultiple(array $ids): array
    {
        $ids = array_filter($ids);

        if (count($ids) === 0) {
            return [];
        }

        /** @var NodeModel $nodeModelAdapter */
        $nodeModelAdapter = $this->framework->getAdapter(NodeModel::class);

        $nodeModels = $nodeModelAdapter->findBy(
            ['id IN (' . implode(',', $ids) . ')', 'type=?'],
            [NodeModel::TYPE_CONTENT, implode(',', $ids)],
            ['order' => 'FIND_IN_SET(`id`, ?)']
        );

        if ($nodeModels === null) {
            return [];
        }

        $nodes = [];

        /** @var Controller $controllerAdapter */
        $controllerAdapter = $this->framework->getAdapter(Controller::class);

        /** @var NodeModel $nodeModel */
        foreach ($nodeModels as $nodeModel) {
            $nodes[$nodeModel->id] = $this->generateBuffer($nodeModel, $controllerAdapter);
        }

        return array_filter($nodes);
    }

    /**
     * Generate the node buffer (content elements)
     *
     * @param NodeModel          $nodeModel
     * @param Controller|Adapter $controllerAdapter
     *
     * @return string
     */
    private function generateBuffer(NodeModel $nodeModel, $controllerAdapter): string
    {
        $buffer = '';

        if (($elements = $nodeModel->getContentElements()) !== null) {
            /** @var ContentModel $element */
            foreach ($elements as $element) {
                $buffer .= $controllerAdapter->getContentElement($element);
            }
        }

        return $buffer;
    }
}
