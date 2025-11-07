<?php

declare(strict_types=1);

namespace Terminal42\NodeBundle\Controller;

use Contao\ContentModel;
use Contao\CoreBundle\Twig\FragmentTemplate;
use Contao\Model;
use Contao\ModuleModel;
use Contao\StringUtil;
use Symfony\Component\HttpFoundation\Response;
use Terminal42\NodeBundle\Model\NodeModel;

/**
 * @internal
 */
trait NodesTrait
{
    private function getNodeIdsFromModel(Model $model): array
    {
        $ids = StringUtil::deserialize($model->nodes);

        if (!\is_array($ids) || [] === $ids) {
            return [];
        }

        return array_map(intval(...), $ids);
    }

    private function generateNodesResponse(FragmentTemplate $template, Model $model): Response
    {
        $ids = $this->getNodeIdsFromModel($model);

        if ([] === $ids || $this->isCircularReference($model, $ids)) {
            return new Response();
        }

        $nodes = $this->nodeManager->generateMultiple($ids);

        if ([] === $nodes) {
            return new Response();
        }

        $template->set('nodes', $nodes);
        $template->set('nodes_wrapper', (bool) $model->nodesWrapper);

        return $template->getResponse();
    }

    private function generateNodesBackendResponse(Model $model): Response
    {
        $ids = $this->getNodeIdsFromModel($model);

        if ([] === $ids) {
            return new Response();
        }

        if ($this->isCircularReference($model, $ids)) {
            return new Response(\sprintf('<strong class="tl_red">%s</strong>', $this->container->get('translator')->trans('ERR.circularReference', [], 'contao_default')));
        }

        $nodeModels = NodeModel::findBy(
            ['id IN ('.implode(',', $ids).')', 'type=?'],
            [NodeModel::TYPE_CONTENT, implode(',', $ids)],
            ['order' => 'FIND_IN_SET(`id`, ?)'],
        );

        $context = [
            'id' => $model->id,
            'nodes' => $nodeModels?->fetchAll() ?? [],
        ];

        if ($model instanceof ContentModel) {
            $context['label'] = $this->container->get('translator')->trans('CTE.nodes.0', [], 'contao_default');
        } elseif ($model instanceof ModuleModel) {
            $context['label'] = $this->container->get('translator')->trans('FMD.nodes.0', [], 'contao_modules');
            $context['name'] = $model->name;
            $context['hrefParams'] = ['do' => 'themes', 'table' => 'tl_module', 'act' => 'edit', 'id' => $model->id, 'rt' => $this->container->get('contao.csrf.token_manager')->getDefaultTokenValue()];
        }

        return $this->render('@Contao/backend/nodes_wildcard.html.twig', $context);
    }

    private function isCircularReference(Model $model, array $ids): bool
    {
        if (!$model instanceof ContentModel) {
            return false;
        }

        if ('tl_node' !== $model->ptable) {
            return false;
        }

        return \in_array((int) $model->pid, $ids, true);
    }
}
