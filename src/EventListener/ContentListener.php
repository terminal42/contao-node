<?php

/*
 * Node Bundle for Contao Open Source CMS.
 *
 * @copyright  Copyright (c) 2019, terminal42 gmbh
 * @author     terminal42 <https://terminal42.ch>
 * @license    MIT
 */

namespace Terminal42\NodeBundle\EventListener;

use Contao\CoreBundle\Exception\AccessDeniedException;
use Contao\DataContainer;
use Contao\Input;
use Contao\StringUtil;
use Doctrine\DBAL\Connection;
use Terminal42\NodeBundle\Model\NodeModel;
use Terminal42\NodeBundle\PermissionChecker;

class ContentListener
{
    /**
     * @var Connection
     */
    private $db;

    /**
     * @var PermissionChecker
     */
    private $permissionChecker;

    /**
     * ContentListener constructor.
     *
     * @param Connection        $db
     * @param PermissionChecker $permissionChecker
     */
    public function __construct(Connection $db, PermissionChecker $permissionChecker)
    {
        $this->db = $db;
        $this->permissionChecker = $permissionChecker;
    }

    /**
     * On data container load callback.
     */
    public function onLoadCallback(DataContainer $dc): void
    {
        // Determine the node type
        if ($dc->table === 'tl_content' && Input::get('act')) {
            $type = $this->db->fetchColumn('SELECT type FROM tl_node WHERE id=(SELECT pid FROM tl_content WHERE id=? AND ptable=?)', [$dc->id, 'tl_node']);
        } else {
            $type = $this->db->fetchColumn('SELECT type FROM tl_node WHERE id=?', [$dc->id]);
        }

        // Throw an exception if the node is not present or is of a folder type
        if (!$type || NodeModel::TYPE_FOLDER === $type) {
            throw new AccessDeniedException('Node of folder type cannot have content elements');
        }

        $this->checkPermissions($dc);
    }

    /**
     * On nodes fields save callback.
     *
     * @param string|null   $value
     * @param DataContainer $dc
     *
     * @throws \InvalidArgumentException
     *
     * @return string
     */
    public function onNodesSaveCallback(?string $value, DataContainer $dc): string
    {
        $ids = (array) StringUtil::deserialize($value, true);

        if (\count($ids) > 0) {
            $folders = $this->db->fetchAll('SELECT name FROM tl_node WHERE id IN ('.implode(', ', $ids).') AND type=?', [NodeModel::TYPE_FOLDER]);

            // Do not allow folder nodes
            if (\count($folders) > 0) {
                throw new \InvalidArgumentException(sprintf($GLOBALS['TL_LANG']['ERR']['invalidNodes'], implode(', ', array_column($folders, 'name'))));
            }

            $ids = array_map('intval', $ids);

            // Check for potential circular reference
            if ('tl_node' === $dc->activeRecord->ptable && \in_array((int) $dc->activeRecord->pid, $ids, true)) {
                throw new \InvalidArgumentException($GLOBALS['TL_LANG']['ERR']['circularReference']);
            }
        }

        return $value;
    }

    /**
     * Check the permissions.
     */
    private function checkPermissions(DataContainer $dc): void
    {
        if (!$this->permissionChecker->hasUserPermission(PermissionChecker::PERMISSION_CONTENT)) {
            throw new AccessDeniedException('The user is not allowed to manage the node content');
        }

        // Determine the node ID
        if ($dc->table === 'tl_content' && Input::get('act')) {
            $nodeId = $this->db->fetchColumn('SELECT pid FROM tl_content WHERE id=? AND ptable=?', [$dc->id, 'tl_node']);
        } else {
            $nodeId = $dc->id;
        }

        if (!$this->permissionChecker->isUserAllowedNode($nodeId)) {
            throw new AccessDeniedException(sprintf('The user is not allowed to manage the content of node ID %s', $nodeId));
        }
    }
}
