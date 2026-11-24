<?php

declare(strict_types=1);

namespace App\Tests\Security\Voter;

use App\Entity\User;
use App\Enum\UserRole;
use App\Security\Voter\UserVoter;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

final class UserVoterTest extends TestCase
{
    /**
     * @return iterable<string, array{string}>
     */
    public static function attributes(): iterable
    {
        yield 'delete' => [UserVoter::DELETE];
        yield 'change role' => [UserVoter::CHANGE_ROLE];
    }

    #[DataProvider('attributes')]
    public function testAdminCanManageOtherUsers(string $attribute): void
    {
        $admin = $this->user(1, UserRole::ADMIN);

        self::assertSame(
            VoterInterface::ACCESS_GRANTED,
            $this->voter(true)->vote($this->token($admin), $this->user(2, UserRole::USER), [$attribute]),
        );
    }

    #[DataProvider('attributes')]
    public function testAdminCannotManageOwnAccount(string $attribute): void
    {
        $admin = $this->user(1, UserRole::ADMIN);

        self::assertSame(
            VoterInterface::ACCESS_DENIED,
            $this->voter(true)->vote($this->token($admin), $admin, [$attribute]),
        );
    }

    #[DataProvider('attributes')]
    public function testNonAdminCannotManageUsers(string $attribute): void
    {
        $user = $this->user(1, UserRole::USER);

        self::assertSame(
            VoterInterface::ACCESS_DENIED,
            $this->voter(false)->vote($this->token($user), $this->user(2, UserRole::USER), [$attribute]),
        );
    }

    public function testAbstainsOnOtherAttributes(): void
    {
        $admin = $this->user(1, UserRole::ADMIN);

        self::assertSame(
            VoterInterface::ACCESS_ABSTAIN,
            $this->voter(true)->vote($this->token($admin), $this->user(2, UserRole::USER), ['USER_VIEW']),
        );
    }

    private function voter(bool $isAdmin): UserVoter
    {
        $accessDecisionManager = $this->createStub(AccessDecisionManagerInterface::class);
        $accessDecisionManager->method('decide')->willReturn($isAdmin);

        return new UserVoter($accessDecisionManager);
    }

    private function token(User $user): UsernamePasswordToken
    {
        return new UsernamePasswordToken($user, 'main', $user->getRoles());
    }

    private function user(int $id, UserRole $role): User
    {
        // The ID is set by Doctrine; restore it like Doctrine does.
        $user = new User();
        $user->__unserialize(['id' => $id, 'identifier' => 'user'.$id]);

        return $user->setRole($role);
    }
}
