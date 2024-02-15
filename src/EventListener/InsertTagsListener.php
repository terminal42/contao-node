<?php

declare(strict_types=1);

namespace Terminal42\NodeBundle\EventListener;

use Contao\CoreBundle\Monolog\ContaoContext;
use Contao\Environment;
use Contao\StringUtil;
use Psr\Log\LoggerInterface;
use Terminal42\NodeBundle\NodeManager;

class InsertTagsListener
{
    /**
     * InsertTagsListener constructor.
     */
    public function __construct(
        private NodeManager $manager,
        private LoggerInterface|null $logger = null,
    ) {
    }

    /**
     * On replace insert tag.
     *
     * @return string|bool
     */
    public function onReplace(string $tag)
    {
        $chunks = explode('::', $tag);

        if ('insert_node' !== $chunks[0] && 'insert_nodes' !== $chunks[0]) {
            return false;
        }

        $ids = StringUtil::trimsplit(',', $chunks[1]);
        $total = \count($ids);

        if (0 === $total) {
            // Let Contao generate an "unknown insert tag" error in the back end
            return false;
        }

        if (1 === $total) {
            if (null === $buffer = $this->manager->generateSingle((int) $ids[0])) {
                $this->logError($ids, $tag);
            }

            return $buffer;
        }

        $nodes = $this->manager->generateMultiple($ids);
        $invalid = array_keys(array_diff_key(array_flip($ids), $nodes));

        if (!empty($invalid)) {
            $this->logError($invalid, $tag);
        }

        return implode("\n", $nodes);
    }

    private function logError(array $ids, string $tag): void
    {
        if (null === $this->logger) {
            return;
        }

        $this->logger->error(
            'Invalid nodes ('.implode(', ', $ids).') in insert tag ('.$tag.') on page '.Environment::get('uri'),
            ['contao' => new ContaoContext(self::class, ContaoContext::ERROR)],
        );
    }
}
