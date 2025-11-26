<?php

declare(strict_types=1);

namespace Terminal42\NodeBundle\InsertTag;

use Contao\CoreBundle\DependencyInjection\Attribute\AsInsertTag;
use Contao\CoreBundle\InsertTag\InsertTagResult;
use Contao\CoreBundle\InsertTag\OutputType;
use Contao\CoreBundle\InsertTag\ResolvedInsertTag;
use Contao\CoreBundle\InsertTag\Resolver\InsertTagResolverNestedResolvedInterface;
use Contao\CoreBundle\Monolog\ContaoContext;
use Contao\Environment;
use Contao\StringUtil;
use Psr\Log\LoggerInterface;
use Terminal42\NodeBundle\NodeManager;

#[AsInsertTag('insert_node')]
#[AsInsertTag('insert_nodes')]
class NodeInsertTag implements InsertTagResolverNestedResolvedInterface
{
    public function __construct(
        private readonly NodeManager $manager,
        private readonly LoggerInterface|null $logger = null,
    ) {
    }

    public function __invoke(ResolvedInsertTag $insertTag): InsertTagResult
    {
        $ids = StringUtil::trimsplit(',', $insertTag->getParameters()->get(0));
        $total = \count($ids);

        if (0 === $total) {
            return new InsertTagResult('');
        }

        if (1 === $total) {
            $buffer = $this->manager->generateSingle($ids[0]);

            if (null === $buffer) {
                $this->logError($insertTag, $ids);

                return new InsertTagResult('');
            }

            return new InsertTagResult($buffer, OutputType::html);
        }

        $nodes = $this->manager->generateMultiple($ids);
        $invalid = array_keys(array_diff_key(array_flip($ids), $nodes));

        if (!empty($invalid)) {
            $this->logError($insertTag, $invalid);
        }

        return new InsertTagResult(implode("\n", $nodes), OutputType::html);
    }

    private function logError(ResolvedInsertTag $insertTag, array $ids): void
    {
        if (null === $this->logger) {
            return;
        }

        $this->logger->error(
            'Invalid nodes ('.implode(', ', $ids).') in insert tag ('.$insertTag->getName().') on page '.Environment::get('uri'),
            ['contao' => new ContaoContext(self::class, ContaoContext::ERROR)],
        );
    }
}
