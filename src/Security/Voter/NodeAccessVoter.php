<?php

namespace Terminal42\NodeBundle\Security\Voter;

use Contao\CoreBundle\Security\DataContainer\CreateAction;
use Contao\CoreBundle\Security\DataContainer\DeleteAction;
use Contao\CoreBundle\Security\DataContainer\ReadAction;
use Contao\CoreBundle\Security\DataContainer\UpdateAction;
use Contao\CoreBundle\Security\Voter\DataContainer\AbstractDataContainerVoter;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Terminal42\NodeBundle\Security\NodePermissions;

class NodeAccessVoter extends AbstractDataContainerVoter
{
    public function __construct(private readonly AccessDecisionManagerInterface $accessDecisionManager)
    {
    }

    protected function getTable(): string
    {
        return 'tl_node';
    }

    protected function hasAccess(TokenInterface $token, UpdateAction|CreateAction|ReadAction|DeleteAction $action): bool
    {
        if (!$this->accessDecisionManager->decide($token, [NodePermissions::USER_CAN_ACCESS_MODULE])) {
            return false;
        }

        switch (true) {
            case $action instanceof CreateAction:
                if ($action->getNewPid() === '0' && !$this->accessDecisionManager->decide($token, [NodePermissions::USER_CAN_CREATE_ROOT_NODES])) {
                    return false;
                }

                return $this->accessDecisionManager->decide($token, [NodePermissions::USER_CAN_CREATE_NODES]);

            case $action instanceof ReadAction:
            case $action instanceof UpdateAction:
                if (!$this->accessDecisionManager->decide($token, [NodePermissions::USER_CAN_EDIT_NODES])) {
                    return false;
                }

                return $this->accessDecisionManager->decide($token, [NodePermissions::USER_CAN_EDIT_NODE], $action->getCurrentId());

            case $action instanceof DeleteAction:
                if (!$this->accessDecisionManager->decide($token, [NodePermissions::USER_CAN_DELETE_NODES])) {
                    return false;
                }

                return $this->accessDecisionManager->decide($token, [NodePermissions::USER_CAN_EDIT_NODE], $action->getCurrentId());
        }

        return false;
    }
}
