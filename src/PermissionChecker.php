<?php

/*
 * Node Bundle for Contao Open Source CMS.
 *
 * @copyright  Copyright (c) 2018, terminal42 gmbh
 * @author     terminal42 <https://terminal42.ch>
 * @license    MIT
 */

namespace Terminal42\NodeBundle;

use Contao\BackendUser;
use Contao\CoreBundle\Framework\ContaoFrameworkInterface;
use Contao\Database;
use Contao\StringUtil;
use Doctrine\DBAL\Connection;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class PermissionChecker
{
    const PERMISSION_CREATE = 'create';
    const PERMISSION_EDIT = 'edit';
    const PERMISSION_CONTENT = 'content';
    const PERMISSION_DELETE = 'delete';
    const PERMISSION_ROOT = 'root';

    /**
     * @var Connection
     */
    private $db;

    /**
     * @var ContaoFrameworkInterface
     */
    private $framework;

    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    /**
     * @var BackendUser
     */
    private $user;

    /**
     * PermissionChecker constructor.
     *
     * @param Connection               $db
     * @param ContaoFrameworkInterface $framework
     * @param TokenStorageInterface    $tokenStorage
     */
    public function __construct(
        Connection $db,
        ContaoFrameworkInterface $framework,
        TokenStorageInterface $tokenStorage
    ) {
        $this->db = $db;
        $this->framework = $framework;
        $this->tokenStorage = $tokenStorage;
    }

    /**
     * Return true if the user is admin.
     *
     * @return bool
     */
    public function isUserAdmin(): bool
    {
        return (bool) $this->getUser()->isAdmin;
    }

    /**
     * Return true if the user has permission.
     *
     * @param string $permission
     *
     * @return bool
     */
    public function hasUserPermission(string $permission): bool
    {
        if ($this->isUserAdmin()) {
            return true;
        }

        $value = $this->getUser()->hasAccess($permission, 'nodePermissions');

        // If the user is able to create records, he is automatically able to edit them
        if (!$value && self::PERMISSION_EDIT === $permission) {
            return $this->hasUserPermission(self::PERMISSION_CREATE);
        }

        return $value;
    }

    /**
     * Get the user allowed roots. Return null if the user has no limitation.
     *
     * @return array|null
     */
    public function getUserAllowedRoots(): ?array
    {
        if ($this->isUserAdmin()) {
            return null;
        }

        return \array_map('intval', (array) $this->getUser()->nodeMounts);
    }

    /**
     * Return if the user is allowed to manage the node.
     *
     * @param int $nodeId
     *
     * @return bool
     */
    public function isUserAllowedNode(int $nodeId): bool
    {
        if (null === ($roots = $this->getUserAllowedRoots())) {
            return true;
        }

        // Return true if the node is a root one and user has permission to manage those
        if (\in_array($nodeId, $roots, true) && $this->hasUserPermission(self::PERMISSION_ROOT)) {
            return true;
        }

        /** @var Database $db */
        $db = $this->framework->createInstance(Database::class);

        $ids = $db->getChildRecords($roots, 'tl_node', false, $roots);
        $ids = \array_map('intval', $ids);

        return \in_array($nodeId, $ids, true);
    }

    /**
     * Add the node to allowed roots.
     *
     * @param int $nodeIds
     */
    public function addNodeToAllowedRoots(int $nodeId): void
    {
        if (null === ($roots = $this->getUserAllowedRoots())) {
            return;
        }

        $user = $this->getUser();

        /** @var StringUtil $stringUtil */
        $stringUtil = $this->framework->getAdapter(StringUtil::class);

        // Add the permissions on group level
        if ('custom' !== $user->inherit) {
            $groups = $this->db->fetchAll('SELECT id, nodeMounts, nodePermissions FROM tl_user_group WHERE id IN('.\implode(',', \array_map('intval', $user->groups)).')');

            foreach ($groups as $group) {
                $permissions = $stringUtil->deserialize($group['nodePermissions'], true);

                if (\in_array(self::PERMISSION_CREATE, $permissions, true)) {
                    $nodeIds = (array) $stringUtil->deserialize($group['nodeMounts'], true);
                    $nodeIds[] = $nodeId;

                    $this->db->update('tl_user_group', ['nodeMounts' => \serialize($nodeIds)], ['id' => $group['id']]);
                }
            }
        }

        // Add the permissions on user level
        if ('group' !== $user->inherit) {
            $userData = $this->db->fetchAssoc('SELECT nodePermissions, nodeMounts FROM tl_user WHERE id=?', [$user->id]);
            $permissions = $stringUtil->deserialize($userData['nodePermissions'], true);

            if (\in_array(self::PERMISSION_CREATE, $permissions, true)) {
                $nodeIds = (array) $stringUtil->deserialize($userData['nodeMounts'], true);
                $nodeIds[] = $nodeId;

                $this->db->update('tl_user', ['nodeMounts' => \serialize($nodeIds)], ['id' => $user->id]);
            }
        }

        // Add the new element to the user object
        $user->nodeMounts = \array_merge($roots, [$nodeId]);
    }

    /**
     * Filter the allowed IDs.
     *
     * @param array  $ids
     * @param string $permission
     *
     * @return array
     */
    public function filterAllowedIds(array $ids, string $permission): array
    {
        if (0 === \count($ids) || !$this->hasUserPermission($permission)) {
            return [];
        }

        return array_filter($ids, [$this, 'isUserAllowedNode']);
    }

    /**
     * Get the user.
     *
     * @throws \RuntimeException
     *
     * @return BackendUser
     */
    private function getUser()
    {
        if (null === $this->user) {
            if (null === $this->tokenStorage) {
                throw new \RuntimeException('No token storage provided');
            }

            $token = $this->tokenStorage->getToken();

            if (null === $token) {
                throw new \RuntimeException('No token provided');
            }

            $this->user = $token->getUser();

            if (!$this->user instanceof BackendUser) {
                throw new \RuntimeException('The token does not contain a back end user object');
            }
        }

        return $this->user;
    }
}
