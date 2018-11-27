<?php

declare(strict_types=1);

/*
 * Node Bundle for Contao Open Source CMS.
 *
 * @copyright  Copyright (c) 2018, terminal42 gmbh
 * @author     terminal42 <https://terminal42.ch>
 * @license    MIT
 */

namespace Terminal42\NodeBundle\EventListener;

use Contao\CoreBundle\Framework\ContaoFrameworkInterface;
use Contao\StringUtil;
use Terminal42\NodeBundle\NodeManager;

class InsertTagsListener
{
    /**
     * @var ContaoFrameworkInterface
     */
    private $framework;

    /**
     * @var NodeManager
     */
    private $manager;

    /**
     * InsertTagsListener constructor.
     *
     * @param ContaoFrameworkInterface $framework
     * @param NodeManager              $manager
     */
    public function __construct(ContaoFrameworkInterface $framework, NodeManager $manager)
    {
        $this->framework = $framework;
        $this->manager = $manager;
    }

    /**
     * On replace insert tag.
     *
     * @param string $tag
     *
     * @return string|bool
     */
    public function onReplace(string $tag)
    {
        $chunks = explode('::', $tag);

        if ('insert_node' === $chunks[0] || 'insert_nodes' === $chunks[0]) {
            return $this->generateNodes($chunks[1]);
        }

        return false;
    }

    /**
     * Generate the nodes.
     *
     * @param string $ids
     *
     * @return string
     */
    private function generateNodes(string $ids): string
    {
        /** @var StringUtil $stringUtilAdapter */
        $stringUtilAdapter = $this->framework->getAdapter(StringUtil::class);

        if (0 === \count($ids = $stringUtilAdapter->trimsplit(',', $ids))) {
            return '';
        }

        // Generate a single node
        if (1 === \count($ids)) {
            return $this->manager->generateSingle((int) $ids[0]);
        }

        return implode("\n", $this->manager->generateMultiple($ids));
    }
}
