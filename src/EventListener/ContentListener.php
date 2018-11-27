<?php

/*
 * Node Bundle for Contao Open Source CMS.
 *
 * @copyright  Copyright (c) 2018, terminal42 gmbh
 * @author     terminal42 <https://terminal42.ch>
 * @license    MIT
 */

namespace Terminal42\NodeBundle\EventListener;

use Contao\CoreBundle\Exception\AccessDeniedException;
use Contao\CoreBundle\Framework\ContaoFrameworkInterface;
use Contao\DataContainer;
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
     * @var ContaoFrameworkInterface
     */
    private $framework;

    /**
     * @var PermissionChecker
     */
    private $permissionChecker;

    /**
     * ContentListener constructor.
     *
     * @param Connection               $db
     * @param ContaoFrameworkInterface $framework
     * @param PermissionChecker        $permissionChecker
     */
    public function __construct(
        Connection $db,
        ContaoFrameworkInterface $framework,
        PermissionChecker $permissionChecker
    ) {
        $this->db = $db;
        $this->framework = $framework;
        $this->permissionChecker = $permissionChecker;
    }

    /**
     * On data container load callback.
     */
    public function onLoadCallback(): void
    {
        $node = $this->db->fetchColumn('SELECT type FROM tl_node WHERE id=?', [CURRENT_ID]);

        // Throw an exception if the node is not present or is of a folder type
        if (!$node || NodeModel::TYPE_FOLDER === $node) {
            throw new AccessDeniedException('Node of folder type cannot have content elements');
        }

        $this->checkPermissions();
    }

    /**
     * On nodes fields save callback.
     *
     * @param string        $value
     * @param DataContainer $dc
     *
     * @throws \InvalidArgumentException
     *
     * @return string
     */
    public function onNodesSaveCallback(string $value, DataContainer $dc): string
    {
        // Check for potential circular reference
        if ('tl_node' === $dc->activeRecord->ptable) {
            /** @var StringUtil $stringUtilAdapter */
            $stringUtilAdapter = $this->framework->getAdapter(StringUtil::class);

            $ids = (array) $stringUtilAdapter->deserialize($value, true);
            $ids = array_map('intval', $ids);

            if (\in_array((int) $dc->activeRecord->pid, $ids, true)) {
                throw new \InvalidArgumentException($GLOBALS['TL_LANG']['ERR']['circularReference']);
            }

            $value = serialize($ids);
        }

        return $value;
    }

    /**
     * Check the permissions.
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
