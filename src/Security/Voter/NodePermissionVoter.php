<?php

declare(strict_types=1);

namespace Terminal42\NodeBundle\Security\Voter;

use Contao\BackendUser;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Security\DataContainer\CreateAction;
use Contao\CoreBundle\Security\DataContainer\DeleteAction;
use Contao\CoreBundle\Security\DataContainer\ReadAction;
use Contao\CoreBundle\Security\DataContainer\UpdateAction;
use Contao\CoreBundle\Security\Voter\DataContainer\AbstractDataContainerVoter;
use Contao\Database;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Terminal42\NodeBundle\Security\NodePermissions;

class NodePermissionVoter extends AbstractDataContainerVoter
{
    private array $nodeMountsCache = [];

    public function __construct(
        private readonly AccessDecisionManagerInterface $accessDecisionManager,
        private readonly ContaoFramework $contaoFramework,
    ) {
    }

    protected function getTable(): string
    {
        return 'tl_node';
    }

    protected function hasAccess(TokenInterface $token, CreateAction|DeleteAction|ReadAction|UpdateAction $action): bool
    {
        if ($this->accessDecisionManager->decide($token, ['ROLE_ADMIN'])) {
            return true;
        }

        if (!$this->accessDecisionManager->decide($token, [NodePermissions::USER_CAN_ACCESS_MODULE])) {
            return false;
        }

        return match (true) {
            $action instanceof CreateAction => $this->canCreate($action, $token),
            $action instanceof ReadAction => $this->canRead($action, $token),
            $action instanceof UpdateAction => $this->canUpdate($action, $token),
            default => $this->canDelete($action, $token), // DeleteAction
        };
    }

    private function canCreate(CreateAction $action, TokenInterface $token): bool
    {
        if (!$this->accessDecisionManager->decide($token, [NodePermissions::USER_CAN_CREATE_NODE])) {
            return false;
        }

        $newAction = $action->getNew();

        // The copy operation is allowed if edit is allowed.
        if (null !== $newAction && null === ($newAction['sorting'] ?? null)) {
            $nodeId = $action->getNewId();

            return $this->canEdit($token, $nodeId) && $this->canCreate(new CreateAction($action->getDataSource()), $token);
        }

        // Check access to any node for the "new" operation.
        if (null === $action->getNewPid()) {
            $nodeIds = $this->getNodeMounts($token);
        } else {
            $nodeIds = [(int) $action->getNewPid()];
        }

        // To create a record, the edit permissions must be available.
        foreach ($nodeIds as $nodeId) {
            if ($this->canEdit($token, $nodeId)) {
                return true;
            }
        }

        return false;
    }

    private function canRead(ReadAction $action, TokenInterface $token): bool
    {
        return $this->canAccessNode($token, $action->getCurrentId());
    }

    private function canUpdate(UpdateAction $action, TokenInterface $token): bool
    {
        $nodeId = $action->getCurrentId();

        if (!$this->canAccessNode($token, $nodeId)) {
            return false;
        }

        $newRecord = $action->getNew();

        // Edit operation
        if (null === $newRecord) {
            return $this->canEdit($token, $nodeId);
        }

        // Move existing record
        $changeSorting = \array_key_exists('sorting', $newRecord);
        $changePid = \array_key_exists('pid', $newRecord) && $action->getCurrentPid() !== $action->getNewPid();

        if (($changeSorting || $changePid) && !$this->canEdit($token, $nodeId)) {
            return false;
        }

        if ($changePid && !$this->canEdit($token, $action->getNewPid())) {
            return false;
        }

        unset($newRecord['pid'], $newRecord['sorting'], $newRecord['tstamp']);

        // Record was possibly only moved (pid, sorting), no need to check edit permissions
        if ([] === array_diff($newRecord, $action->getCurrent())) {
            return true;
        }

        return $this->accessDecisionManager->decide($token, [NodePermissions::USER_CAN_EDIT_NODE]);
    }

    private function canEdit(TokenInterface $token, string|null $nodeId): bool
    {
        return $this->accessDecisionManager->decide($token, [NodePermissions::USER_CAN_ACCESS_NODE], $nodeId)
            && $this->accessDecisionManager->decide($token, [NodePermissions::USER_CAN_EDIT_NODE]);
    }

    private function canDelete(DeleteAction $action, TokenInterface $token): bool
    {
        return $this->accessDecisionManager->decide($token, [NodePermissions::USER_CAN_ACCESS_NODE], $action->getCurrentId())
            && $this->accessDecisionManager->decide($token, [NodePermissions::USER_CAN_DELETE_NODE]);
    }

    private function canAccessNode(TokenInterface $token, string|null $nodeId): bool
    {
        return $this->accessDecisionManager->decide($token, [NodePermissions::USER_CAN_ACCESS_NODE], $nodeId);
    }

    private function getNodeMounts(TokenInterface $token): array
    {
        $user = $token->getUser();

        if (!$user instanceof BackendUser) {
            return [];
        }

        if (isset($this->nodeMountsCache[$user->id])) {
            return $this->nodeMountsCache[$user->id];
        }

        $database = $this->contaoFramework->createInstance(Database::class);

        return $this->nodeMountsCache[$user->id] = $database->getChildRecords($user->nodeMounts, 'tl_node', false, $user->nodeMounts);
    }
}
