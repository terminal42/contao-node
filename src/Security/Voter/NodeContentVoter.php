<?php

declare(strict_types=1);

namespace Terminal42\NodeBundle\Security\Voter;

use Contao\CoreBundle\Security\Voter\DataContainer\AbstractDynamicPtableVoter;
use Doctrine\DBAL\Connection;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Terminal42\NodeBundle\Model\NodeModel;
use Terminal42\NodeBundle\Security\NodePermissions;

class NodeContentVoter extends AbstractDynamicPtableVoter
{
    public function __construct(
        private readonly AccessDecisionManagerInterface $accessDecisionManager,
        private readonly Connection $connection,
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

        if (!$this->accessDecisionManager->decide($token, [NodePermissions::USER_CAN_ACCESS_NODE], $id)) {
            return false;
        }

        $type = $this->connection->fetchOne('SELECT type FROM tl_node WHERE id = ?', [$id]);

        if (NodeModel::TYPE_FOLDER === $type) {
            return false;
        }

        return true;
    }
}
