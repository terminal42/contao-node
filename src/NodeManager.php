<?php

declare(strict_types=1);

namespace Terminal42\NodeBundle;

use Contao\ContentModel;
use Contao\Controller;
use Contao\FrontendTemplate;
use Contao\StringUtil;
use Doctrine\DBAL\Connection;
use Terminal42\NodeBundle\Model\NodeModel;

class NodeManager
{
    public function __construct(private readonly Connection $connection)
    {
    }

    public function generateSingle(int|string $idOrAlias): string|null
    {
        if (!$idOrAlias) {
            return null;
        }

        if (null === ($nodeModel = NodeModel::findOneBy(['(id=? OR alias=?)', 'type=?'], [$idOrAlias, $idOrAlias, NodeModel::TYPE_CONTENT]))) {
            return null;
        }

        return $this->generateBuffer($nodeModel);
    }

    public function generateMultiple(array $idsOrAliases): array
    {
        $idsOrAliases = array_filter($idsOrAliases);

        if (0 === \count($idsOrAliases)) {
            return [];
        }

        $isAlias = static fn ($v) => is_string($v) && !is_numeric($v);
        $aliases = array_filter($idsOrAliases, $isAlias);

        // If there are aliases, fetch their IDs and put them in respective places
        if ($aliases !== []) {
            $aliasesWithIds = $this->connection->fetchAllKeyValue("SELECT alias, id FROM tl_node WHERE id IN ('" . \implode("','", $aliases) . "') ORDER BY FIND_IN_SET(`alias`, '" . implode(',', $aliases) . "')");

            foreach ($idsOrAliases as $k => $v) {
                if (!$isAlias($v)) {
                    continue;
                }

                // Replace the alias with ID
                if (array_key_exists($v, $aliasesWithIds)) {
                    $idsOrAliases[$k] = $aliasesWithIds[$v];
                } else {
                    // Remove it completely, so it doesn't produce unexpected results later on with intval()
                    unset($idsOrAliases[$k]);
                }
            }

            $idsOrAliases = array_values($idsOrAliases);
        }

        $ids = array_map(intval(...), $idsOrAliases);

        $nodeModels = NodeModel::findBy(
            ['id IN ('.implode(',', $ids).')', 'type=?'],
            [NodeModel::TYPE_CONTENT, implode(',', $ids)],
            ['order' => 'FIND_IN_SET(`id`, ?)'],
        );

        if (null === $nodeModels) {
            return [];
        }

        $nodes = [];

        /** @var NodeModel $nodeModel */
        foreach ($nodeModels as $nodeModel) {
            $nodes[$nodeModel->id] = $this->generateBuffer($nodeModel);
        }

        return array_filter($nodes, static fn ($buffer) => null !== $buffer);
    }

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

        $template = new FrontendTemplate($nodeModel->nodeTpl ?: 'node/default');
        $template->setData($nodeModel->row());
        $template->elementsData = $elementsData;

        $cssID = StringUtil::deserialize($nodeModel->cssID, true);

        $template->class = !empty($cssID[1]) ? $cssID[1] : '';
        $template->cssID = !empty($cssID[0]) ? $cssID[0] : '';
        $template->buffer = $buffer;

        return $template->parse();
    }
}
