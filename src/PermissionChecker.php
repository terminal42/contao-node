<?php

declare(strict_types=1);

namespace Terminal42\NodeBundle;

use Contao\BackendUser;
use Contao\Database;
use Contao\StringUtil;
use Doctrine\DBAL\Connection;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class PermissionChecker
{
    public const PERMISSION_CREATE = 'create';

    public const PERMISSION_EDIT = 'edit';

    public const PERMISSION_CONTENT = 'content';

    public const PERMISSION_DELETE = 'delete';

    public const PERMISSION_ROOT = 'root';

    public function __construct(
        private Connection $db,
        private AuthorizationCheckerInterface $authorizationChecker,
        private TokenStorageInterface $tokenStorage,
    ) {
    }

    /**
     * Return true if the user is admin.
     */
    public function isUserAdmin(): bool
    {
        return $this->authorizationChecker->isGranted('ROLE_ADMIN');
    }

    /**
     * Return true if the user has permission.
     */
    public function hasUserPermission(string $permission): bool
    {
        $isGranted = $this->authorizationChecker->isGranted('contao_user.nodePermissions.'.$permission);

        // If the user is able to create records, he is automatically able to edit them
        if (!$isGranted && self::PERMISSION_EDIT === $permission) {
            return $this->hasUserPermission(self::PERMISSION_CREATE);
        }

        return $isGranted;
    }

    /**
     * Get the user allowed roots. Return null if the user has no limitation.
     */
    public function getUserAllowedRoots(): array|null
    {
        if ($this->isUserAdmin()) {
            return null;
        }

        $ids = (array) $this->getUser()->nodeMounts;

        if (empty($ids)) {
            return null;
        }

        return array_map('intval', $ids);
    }

    /**
     * Check whether at least one of the given IDs is in the allowed root nodes.
     *
     * @param int|array<int> $nodeId
     */
    public function isUserAllowedRootNode(array|int $nodeId): bool
    {
        if (null === ($roots = $this->getUserAllowedRoots())) {
            return true;
        }

        if (!\is_array($nodeId)) {
            $nodeId = [$nodeId];
        }

        return (bool) array_intersect($nodeId, $roots);
    }

    /**
     * Return if the user is allowed to manage the node.
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

        $ids = Database::getInstance()->getChildRecords($roots, 'tl_node', false, $roots);
        $ids = array_map('intval', $ids);

        return \in_array($nodeId, $ids, true);
    }

    /**
     * Add the node to allowed roots.
     */
    public function addNodeToAllowedRoots(int $nodeId): void
    {
        if (null === ($roots = $this->getUserAllowedRoots())) {
            return;
        }

        $user = $this->getUser();

        // Add the permissions on group level
        if ('custom' !== $user->inherit) {
            $groups = $this->db->fetchAllAssociative('SELECT id, nodeMounts, nodePermissions FROM tl_user_group WHERE id IN('.implode(',', array_map('intval', $user->groups)).')');

            foreach ($groups as $group) {
                $permissions = StringUtil::deserialize($group['nodePermissions'], true);

                if (\in_array(self::PERMISSION_CREATE, $permissions, true)) {
                    $nodeIds = (array) StringUtil::deserialize($group['nodeMounts'], true);
                    $nodeIds[] = $nodeId;

                    $this->db->update('tl_user_group', ['nodeMounts' => serialize($nodeIds)], ['id' => $group['id']]);
                }
            }
        }

        // Add the permissions on user level
        if ('group' !== $user->inherit) {
            $userData = $this->db->fetchAssociative('SELECT nodePermissions, nodeMounts FROM tl_user WHERE id=?', [$user->id]);
            $permissions = StringUtil::deserialize($userData['nodePermissions'], true);

            if (\in_array(self::PERMISSION_CREATE, $permissions, true)) {
                $nodeIds = (array) StringUtil::deserialize($userData['nodeMounts'], true);
                $nodeIds[] = $nodeId;

                $this->db->update('tl_user', ['nodeMounts' => serialize($nodeIds)], ['id' => $user->id]);
            }
        }

        // Add the new element to the user object
        $user->nodeMounts = array_merge($roots, [$nodeId]);
    }

    /**
     * Filter the allowed IDs.
     */
    public function filterAllowedIds(array $ids, string $permission): array
    {
        if (0 === \count($ids) || !$this->hasUserPermission($permission)) {
            return [];
        }

        return array_filter(array_map('intval', $ids), [$this, 'isUserAllowedNode']);
    }

    private function getUser(): BackendUser
    {
        $user = $this->tokenStorage->getToken()?->getUser();

        if (!$user instanceof BackendUser) {
            throw new \RuntimeException('The token does not contain a back end user object');
        }

        return $user;
    }
}
