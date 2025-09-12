<?php

declare(strict_types=1);

namespace Terminal42\NodeBundle\EventListener;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\CoreBundle\Exception\AccessDeniedException;
use Contao\DataContainer;
use Contao\Input;
use Contao\StringUtil;
use Doctrine\DBAL\Connection;
use Terminal42\NodeBundle\Model\NodeModel;

class ContentListener
{
    public function __construct(private readonly Connection $connection)
    {
    }

    #[AsCallback('tl_content', 'config.onload')]
    public function onLoadCallback(DataContainer $dc): void
    {
        if ('nodes' !== Input::get('do')) {
            return;
        }

        switch (Input::get('act')) {
            case 'edit':
            case 'delete':
            case 'show':
                $nodeId = $this->connection->fetchOne('SELECT pid FROM tl_content WHERE id=? AND ptable=?', [$dc->id, 'tl_node']);
                break;

            case 'paste':
                if ('create' === Input::get('mode')) {
                    $nodeId = $dc->id;
                } else {
                    $nodeId = $this->connection->fetchOne('SELECT pid FROM tl_content WHERE id=? AND ptable=?', [$dc->id, 'tl_node']);
                }
                break;

            case 'create':
            case 'copy':
            case 'copyAll':
            case 'cut':
            case 'cutAll':
                if (1 === (int) Input::get('mode')) {
                    $nodeId = $this->connection->fetchOne('SELECT pid FROM tl_content WHERE id=? AND ptable=?', [Input::get('pid'), 'tl_node']);
                } else {
                    $nodeId = Input::get('pid');
                }
                break;

            default:
                // Ajax requests such as toggle
                if (Input::get('field') && ($id = Input::get('cid') ?: Input::get('id'))) {
                    $nodeId = $this->connection->fetchOne('SELECT pid FROM tl_content WHERE id=? AND ptable=?', [$id, 'tl_node']);
                } else {
                    $nodeId = $dc->id;
                }
                break;
        }

        $type = $this->connection->fetchOne('SELECT type FROM tl_node WHERE id=?', [$nodeId]);

        // Throw an exception if the node is not present or is of a folder type
        if (!$type || NodeModel::TYPE_FOLDER === $type) {
            throw new AccessDeniedException('Node of folder type cannot have content elements');
        }
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
