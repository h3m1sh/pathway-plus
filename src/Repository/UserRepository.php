<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

/**
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * Used to upgrade (rehash) the user's password automatically over time.
     */
    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', $user::class));
        }

        $user->setPassword($newHashedPassword);
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
    }

    public function findRecentProfileUpdates(User $user, int $days): array
    {
        $date = new \DateTimeImmutable("-{$days} days");

        return $this->createQueryBuilder('u')
            ->select('u.updatedAt as dateEarned, u.lastProfileUpdate as updateDescription')
            ->andWhere('u.id = :user')
            ->andWhere('u.updatedAt >= :date')
            ->setParameter('user', $user)
            ->setParameter('date', $date)
            ->orderBy('u.updatedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findRecentCareerGoals(User $user, int $days): array
    {
        $date = new \DateTimeImmutable("-{$days} days");

        return $this->createQueryBuilder('u')
            ->select('u.careerGoalUpdatedAt as dateEarned, u.careerGoal as goalDescription')
            ->andWhere('u.id = :user')
            ->andWhere('u.careerGoalUpdatedAt >= :date')
            ->setParameter('user', $user)
            ->setParameter('date', $date)
            ->orderBy('u.careerGoalUpdatedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
