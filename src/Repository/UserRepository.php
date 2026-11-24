<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\User;
use App\Enum\UserRole;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository
{
    public const int PER_PAGE = 25;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * @return User[]
     */
    public function findUsers(): array
    {
        return $this->findBy(
            ['role' => UserRole::USER],
            ['id' => 'ASC']
        );
    }

    /**
     * One page of all users, ordered by ID. Pages start at 1.
     *
     * @return Paginator<User>
     */
    public function paginate(int $page, int $perPage = self::PER_PAGE): Paginator
    {
        $query = $this->createQueryBuilder('u')
            ->orderBy('u.id', \SortDirection::Ascending)
            ->setFirstResult(($page - 1) * $perPage)
            ->setMaxResults($perPage)
            ->getQuery();

        return new Paginator($query, fetchJoinCollection: false);
    }

    /**
     * Returns $base, or $base with a numeric suffix ("-2", "-3", ...) if it is already taken.
     */
    public function getAvailableIdentifier(string $base): string
    {
        $identifier = $base;
        $suffix = 1;

        while (null !== $this->findOneBy(['identifier' => $identifier])) {
            $identifier = $base.'-'.++$suffix;
        }

        return $identifier;
    }
}
