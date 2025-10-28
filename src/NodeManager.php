<?php

declare(strict_types=1);

namespace Terminal42\NodeBundle;

use Contao\ContentModel;
use Contao\Controller;
use Contao\StringUtil;
use Doctrine\DBAL\Connection;
use Terminal42\NodeBundle\Model\NodeModel;
use Twig\Environment;

class NodeManager
{
    public function __construct(
        private readonly Connection $connection,
        private readonly Environment $twig,
    ) {
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

        $isAlias = static fn ($v) => \is_string($v) && !is_numeric($v);
        $aliases = array_filter($idsOrAliases, $isAlias);

        // If there are aliases, fetch their IDs and put them in respective places
        if ([] !== $aliases) {
            $aliasesWithIds = $this->connection->fetchAllKeyValue("SELECT alias, id FROM tl_node WHERE id IN ('".implode("','", $aliases)."') ORDER BY FIND_IN_SET(`alias`, '".implode(',', $aliases)."')");

            foreach ($idsOrAliases as $k => $v) {
                if (!$isAlias($v)) {
                    continue;
                }

                // Replace the alias with ID
                if (\array_key_exists($v, $aliasesWithIds)) {
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

        $nodeElements = [];

        if (null !== ($elements = $nodeModel->getContentElements())) {
            /** @var ContentModel $element */
            foreach ($elements as $index => $element) {
                // Keep the index if somebody wants to refer that inside the generated content element
                $element->nodeElementIndex = $index;

                $nodeElements[] = new NodeElement($element->row(), Controller::getContentElement($element));
            }
        }

        if (!$nodeModel->wrapper) {
            return implode('', array_map(static fn (NodeElement $v) => $v->getRenderedHtml(), $nodeElements));
        }

        $cssID = StringUtil::deserialize($nodeModel->cssID, true);

        if ($nodeModel->nodeTpl) {
            $templateName = sprintf('@Contao/%s.html.twig', $nodeModel->nodeTpl);
        } else {
            $templateName = '@Contao/node/default.html.twig';
        }

        return $this->twig->render($templateName, [
            ...$nodeModel->row(),
            'elements' => $nodeElements,
            'class' => !empty($cssID[1]) ? $cssID[1] : '',
            'cssID' => !empty($cssID[0]) ? $cssID[0] : '',
        ]);
    }
}
