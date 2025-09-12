<?php

namespace Terminal42\NodeBundle\Controller\ContentElement;

use Contao\ContentModel;
use Contao\CoreBundle\Controller\ContentElement\AbstractContentElementController;
use Contao\CoreBundle\DependencyInjection\Attribute\AsContentElement;
use Contao\CoreBundle\Twig\FragmentTemplate;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Translation\TranslatorInterface;
use Terminal42\NodeBundle\Controller\NodesTrait;
use Terminal42\NodeBundle\NodeManager;

#[AsContentElement('nodes', category: 'miscellaneous', template: 'content_element/nodes')]
class NodesController extends AbstractContentElementController
{
    use NodesTrait;

    public function __construct(private readonly NodeManager $nodeManager)
    {
    }

    protected function getResponse(FragmentTemplate $template, ContentModel $model, Request $request): Response
    {
        if ($this->isBackendScope($request)) {
            return $this->generateNodesBackendResponse($model);
        }

        return $this->generateNodesResponse($template, $model);
    }

    public static function getSubscribedServices(): array
    {
        $services = parent::getSubscribedServices();
        $services['translator'] = TranslatorInterface::class;

        return $services;
    }
}
