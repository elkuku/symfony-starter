<?php

declare(strict_types=1);

namespace App\Security\Voter;

use App\Entity\User;
use App\Enum\UserRole;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * What an administrator may do to a user account.
 *
 * Administrators can't delete their own account or change their own role,
 * so the application can't be left without an administrator.
 *
 * @extends Voter<self::DELETE|self::CHANGE_ROLE, User>
 */
final class UserVoter extends Voter
{
    public const string DELETE = 'USER_DELETE';

    public const string CHANGE_ROLE = 'USER_CHANGE_ROLE';

    public function __construct(
        private readonly AccessDecisionManagerInterface $accessDecisionManager,
    ) {
    }

    #[\Override]
    protected function supports(string $attribute, mixed $subject): bool
    {
        return \in_array($attribute, [self::DELETE, self::CHANGE_ROLE], true)
            && $subject instanceof User;
    }

    #[\Override]
    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        $currentUser = $token->getUser();

        if (!$currentUser instanceof User) {
            return false;
        }

        if (!$this->accessDecisionManager->decide($token, [UserRole::ADMIN->value])) {
            $vote?->addReason('Only administrators can manage users.');

            return false;
        }

        if ($subject->id === $currentUser->id) {
            $vote?->addReason(self::DELETE === $attribute
                ? 'You cannot delete your own account.'
                : 'You cannot change your own role.');

            return false;
        }

        return true;
    }
}
