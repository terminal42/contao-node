<?php

declare(strict_types=1);

namespace Terminal42\NodeBundle;

use Contao\ContentModel;
use Contao\Controller;
use Contao\StringUtil;
use Terminal42\NodeBundle\Model\NodeModel;
use Twig\Environment;

class NodeManager
{
    public function __construct(private readonly Environment $twig)
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
        $idsOrAliases = array_values(array_filter($idsOrAliases));

        if ([] === $idsOrAliases) {
            return [];
        }

        $aliases = array_values(array_filter($idsOrAliases, static fn ($v) => \is_string($v) && !is_numeric($v)));
        $ids = array_map(intval(...), array_values(array_diff($idsOrAliases, $aliases)));

        if ($aliases === [] && $ids === []) {
            return [];
        }

        $columns = ['type=?'];
        $values = [NodeModel::TYPE_CONTENT];

        if ($aliases !== []) {
            $columns[] = "alias IN ('" . implode("','", $aliases) . "')";
        }

        if ($ids !== []) {
            $columns[] = "id IN (" . implode(",", $ids) . ")";
        }

        if (null === ($nodeModels = NodeModel::findBy($columns, $values))) {
            return [];
        }

        $sortedNodeModels = [];

        /** @var NodeModel $nodeModel */
        foreach ($nodeModels as $nodeModel) {
            // No strict check here
            $sortedNodeModels[array_search($nodeModel->alias ?: $nodeModel->id, $idsOrAliases, false)] = $nodeModel;
        }

        $nodes = [];

        /** @var NodeModel $nodeModel */
        foreach ($sortedNodeModels as $nodeModel) {
            $nodes[$nodeModel->id] = $this->generateBuffer($nodeModel);
        }

        return array_filter($nodes, static fn ($buffer) => '' !== $buffer);
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
            $templateName = \sprintf('@Contao/%s.html.twig', $nodeModel->nodeTpl);
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
