<?php

namespace Terminal42\NodeBundle\Security\Voter;

use Contao\CoreBundle\Security\Voter\DataContainer\AbstractDynamicPtableVoter;
use Doctrine\DBAL\Connection;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Terminal42\NodeBundle\Security\NodePermissions;

class NodeContentVoter extends AbstractDynamicPtableVoter
{
    public function __construct(
        private readonly AccessDecisionManagerInterface $accessDecisionManager,
        Connection $connection,
    ) {
        parent::__construct($connection);
    }

    protected function getTable(): string
    {
        return 'tl_content';
    }

    protected function hasAccessToRecord(TokenInterface $token, string $table, int $id): bool
    {
        if ('tl_node' !== $table) {
            return true;
        }

        if (!$this->accessDecisionManager->decide($token, [NodePermissions::USER_CAN_ACCESS_MODULE])) {
            return false;
        }

        if (!$this->accessDecisionManager->decide($token, [NodePermissions::USER_CAN_EDIT_NODE_CONTENT])) {
            return false;
        }

        return $this->accessDecisionManager->decide($token, [NodePermissions::USER_CAN_EDIT_NODE], $id);
    }
}
