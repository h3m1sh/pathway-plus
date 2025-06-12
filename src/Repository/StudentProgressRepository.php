<?php

namespace App\Repository;

use App\Entity\StudentProgress;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<StudentProgress>
 */
class StudentProgressRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, StudentProgress::class);
    }


    /*
     * Find recent progress for a student within specified days
     * */

    public function findRecentProgress(User $student, int $days = 30): array
{
    $since = new \DateTimeImmutable("-{$days} days");

    return $this->createQueryBuilder('sp')
        ->andWhere('sp.student = :student')
        ->andWhere('sp.dateEarned >= :since')
        ->setParameter('student', $student)
        ->setParameter('since', $since)
        ->orderBy('sp.dateEarned', 'ASC')
        ->getQuery()
        ->getResult();

}

    //    /**
    //     * @return StudentProgress[] Returns an array of StudentProgress objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('s')
    //            ->andWhere('s.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('s.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?StudentProgress
    //    {
    //        return $this->createQueryBuilder('s')
    //            ->andWhere('s.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
