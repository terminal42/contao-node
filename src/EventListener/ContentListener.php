<?php

namespace Terminal42\NodeBundle\EventListener;

use Contao\CoreBundle\Exception\AccessDeniedException;
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
     * @param Connection $db
     * @param PermissionChecker $permissionChecker
     */
    public function __construct(Connection $db, PermissionChecker $permissionChecker) {
        $this->db = $db;
        $this->permissionChecker = $permissionChecker;
    }

    /**
     * On data container load callback.
     */
    public function onLoadCallback(): void
    {
        $node = $this->db->fetchColumn('SELECT type FROM tl_node WHERE id=?', [CURRENT_ID]);

        // Throw an exception if the node is not present or is of a folder type
        if (!$node || $node === NodeModel::TYPE_FOLDER) {
            throw new AccessDeniedException('Node of folder type cannot have content elements');
        }

        $this->checkPermissions();
    }

    /**
     * Check the permissions
     */
    private function checkPermissions(): void
    {
        if (!$this->permissionChecker->hasUserPermission(PermissionChecker::PERMISSION_CONTENT)) {
            throw new AccessDeniedException('The user is not allowed to manage the node content');
        }

        if (!$this->permissionChecker->isUserAllowedNode(CURRENT_ID)) {
            throw new AccessDeniedException(sprintf('The user is not allowed to manage the content of node ID %s', CURRENT_ID));
        }
    }
}
