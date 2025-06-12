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

    /**
     * Find recent progress for a student within specified days
     */
    public function findRecentProgress(User $student, int $days = 30): array
    {
        $since = new \DateTimeImmutable("-{$days} days");
        
        return $this->createQueryBuilder('sp')
            ->leftJoin('sp.microCredential', 'mc')
            ->andWhere('sp.student = :student')
            ->andWhere('sp.dateEarned >= :since')
            ->setParameter('student', $student)
            ->setParameter('since', $since)
            ->orderBy('sp.dateEarned', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Get completion statistics for a student
     */
    public function getStudentStats(User $student): array
    {
        $qb = $this->createQueryBuilder('sp')
            ->select('COUNT(sp.id) as total')
            ->addSelect('COUNT(CASE WHEN sp.status IN (:completed_statuses) THEN 1 END) as completed')
            ->andWhere('sp.student = :student')
            ->setParameter('student', $student)
            ->setParameter('completed_statuses', ['Completed', 'Verified'])
            ->getQuery()
            ->getSingleResult();

        return [
            'total' => (int) $qb['total'],
            'completed' => (int) $qb['completed'],
            'completion_rate' => $qb['total'] > 0 ? round(($qb['completed'] / $qb['total']) * 100) : 0
        ];
    }

    /**
     * Find credentials by category for a student
     */
    public function findByCategory(User $student, string $category): array
    {
        return $this->createQueryBuilder('sp')
            ->leftJoin('sp.microCredential', 'mc')
            ->andWhere('sp.student = :student')
            ->andWhere('mc.category = :category')
            ->setParameter('student', $student)
            ->setParameter('category', $category)
            ->orderBy('sp.dateEarned', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find credentials by status for a student
     */
    public function findByStatus(User $student, string $status): array
    {
        return $this->createQueryBuilder('sp')
            ->leftJoin('sp.microCredential', 'mc')
            ->andWhere('sp.student = :student')
            ->andWhere('sp.status = :status')
            ->setParameter('student', $student)
            ->setParameter('status', $status)
            ->orderBy('sp.dateEarned', 'DESC')
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
