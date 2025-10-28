<?php

declare(strict_types=1);

namespace Terminal42\NodeBundle\Controller\FrontendModule;

use Contao\CoreBundle\Controller\FrontendModule\AbstractFrontendModuleController;
use Contao\CoreBundle\DependencyInjection\Attribute\AsFrontendModule;
use Contao\CoreBundle\Twig\FragmentTemplate;
use Contao\ModuleModel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Translation\TranslatorInterface;
use Terminal42\NodeBundle\Controller\NodesTrait;
use Terminal42\NodeBundle\NodeManager;

#[AsFrontendModule('nodes', category: 'includes', template: 'frontend_module/nodes')]
class NodesController extends AbstractFrontendModuleController
{
    use NodesTrait;

    public function __construct(private readonly NodeManager $nodeManager)
    {
    }

    public static function getSubscribedServices(): array
    {
        $services = parent::getSubscribedServices();
        $services['translator'] = TranslatorInterface::class;

        return $services;
    }

    protected function getResponse(FragmentTemplate $template, ModuleModel $model, Request $request): Response
    {
        return $this->generateNodesResponse($template, $model);
    }
}
