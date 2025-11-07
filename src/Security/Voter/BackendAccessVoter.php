<?php

declare(strict_types=1);

namespace Terminal42\NodeBundle\Security\Voter;

use Contao\BackendUser;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\Database;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\CacheableVoterInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;
use Symfony\Contracts\Service\ResetInterface;
use Terminal42\NodeBundle\Security\NodePermissions;

/**
 * Decorates the Contao Core BackendAccessVoter in order to support our own NodePermissions::USER_CAN_ACCESS_NODE
 * which works exactly like the core pagemounts or filemounts. We're using decoration so all the other NodePermissions
 * can work just the same as they do in core. Decoration is only needed for the "tree-like" permissions.
 */
class BackendAccessVoter implements ResetInterface, VoterInterface, CacheableVoterInterface
{
    private array $nodeMountsCache = [];

    public function __construct(
        private readonly ContaoFramework $contaoFramework,
        private readonly VoterInterface $inner,
    ) {
    }

    public function reset(): void
    {
        $this->nodeMountsCache = [];

        if ($this->inner instanceof ResetInterface) {
            $this->inner->reset();
        }
    }

    public function supportsAttribute(string $attribute): bool
    {
        if ($this->inner instanceof CacheableVoterInterface) {
            return $this->inner->supportsAttribute($attribute);
        }

        return false;
    }

    public function supportsType(string $subjectType): bool
    {
        if ($this->inner instanceof CacheableVoterInterface) {
            return $this->inner->supportsType($subjectType);
        }

        return false;
    }

    public function vote(TokenInterface $token, mixed $subject, array $attributes, Vote|null $vote = null): int
    {
        foreach ($attributes as $attribute) {
            if (NodePermissions::USER_CAN_ACCESS_NODE === $attribute) {
                return $this->voteOnAttribute($token, $subject, $attribute) ? self::ACCESS_GRANTED : self::ACCESS_DENIED;
            }
        }

        return $this->inner->vote($token, $subject, $attributes);
    }

    private function voteOnAttribute(TokenInterface $token, mixed $subject, string $attribute): bool
    {
        $user = $token->getUser();

        if (!$user instanceof BackendUser) {
            return false;
        }

        if ($user->isAdmin) {
            return true;
        }

        $permission = explode('.', $attribute, 3);

        if ('contao_user' !== $permission[0] || !isset($permission[1])) {
            return false;
        }

        $field = $permission[1];

        if (null === $subject) {
            return \is_array($user->$field) && [] !== $user->$field;
        }

        if (!\is_scalar($subject) && !\is_array($subject)) {
            return false;
        }

        if (!\is_array($subject)) {
            $subject = [$subject];
        }

        if (\is_array($user->$field) && array_intersect($subject, $user->$field)) {
            return true;
        }

        if (!isset($this->nodeMountsCache[$user->id]) || (!empty($this->nodeMountsCache[$user->id]) && !array_intersect($subject, $this->nodeMountsCache[$user->id]))) {
            $database = $this->contaoFramework->createInstance(Database::class);
            $this->nodeMountsCache[$user->id] = $database->getChildRecords($user->$field, 'tl_node');
        }

        return !empty($this->nodeMountsCache[$user->id]) && array_intersect($subject, $this->nodeMountsCache[$user->id]);
    }
}
