<?php

declare(strict_types=1);

namespace Terminal42\NodeBundle\EventListener;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\DataContainer;
use Contao\StringUtil;
use Doctrine\DBAL\Connection;
use Terminal42\NodeBundle\Model\NodeModel;

class ContentListener
{
    public function __construct(private readonly Connection $connection)
    {
    }

    #[AsCallback('tl_content', 'fields.nodes.save')]
    public function onNodesSaveCallback(string|null $value, DataContainer $dc): string|null
    {
        $ids = (array) StringUtil::deserialize($value, true);
        $ids = array_map('intval', $ids);

        if (\count($ids) > 0) {
            $folders = $this->connection->fetchAllAssociative('SELECT name FROM tl_node WHERE id IN ('.implode(', ', $ids).') AND type=?', [NodeModel::TYPE_FOLDER]);

            // Do not allow folder nodes
            if (\count($folders) > 0) {
                throw new \InvalidArgumentException(\sprintf($GLOBALS['TL_LANG']['ERR']['invalidNodes'], implode(', ', array_column($folders, 'name'))));
            }

            $ids = array_map('intval', $ids);

            // Check for potential circular reference
            if ('tl_node' === ($dc->activeRecord->ptable ?? null) && \in_array((int) $dc->activeRecord->pid, $ids, true)) {
                throw new \InvalidArgumentException($GLOBALS['TL_LANG']['ERR']['circularReference']);
            }
        }

        return $value;
    }
}
