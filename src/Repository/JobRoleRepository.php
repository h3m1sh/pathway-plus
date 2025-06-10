<?php

namespace App\Repository;

use App\Entity\JobRole;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Pagerfanta\Doctrine\ORM\QueryAdapter;
use Pagerfanta\Pagerfanta;

/**
 * @extends ServiceEntityRepository<JobRole>
 */
class JobRoleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, JobRole::class);
    }

    /**
     * Get paginated job roles with optional industry filter
     *
     * @param int $page Current page number (1-based)
     * @param int $maxPerPage Number of items per page
     * @param string|null $industry Optional industry filter
     * @return Pagerfanta
     */
    public function findPaginatedByIndustry(int $page = 1, int $maxPerPage = 10, ?string $industry = null): Pagerfanta
    {
        $qb = $this->createQueryBuilder('jr')
            ->orderBy('jr.title', 'ASC');

        if ($industry) {
            $qb->andWhere('jr.industry = :industry')
               ->setParameter('industry', $industry);
        }

        $adapter = new QueryAdapter($qb);
        $pagerfanta = new Pagerfanta($adapter);
        $pagerfanta->setMaxPerPage($maxPerPage);
        $pagerfanta->setCurrentPage($page);

        return $pagerfanta;
    }

    /**
     * Get all distinct industries from job roles
     * @return array
     */
    public function findDistinctIndustries(): array
    {
        $result = $this->createQueryBuilder('jr')
            ->select('DISTINCT jr.industry')
            ->where('jr.industry IS NOT NULL')
            ->andWhere('jr.industry != :empty')
            ->setParameter('empty', '')
            ->orderBy('jr.industry', 'ASC')
            ->getQuery()
            ->getScalarResult();

        // Extract just the industry values from the result array
        return array_column($result, 'industry');
    }

    /**
     * Get all distinct salary ranges from job roles
     * @return array
     */
    public function findDistinctSalaryRanges(): array
    {
        $result = $this->createQueryBuilder('jr')
            ->select('DISTINCT jr.salaryRange')
            ->where('jr.salaryRange IS NOT NULL')
            ->andWhere('jr.salaryRange != :empty')
            ->setParameter('empty', '')
            ->orderBy('jr.salaryRange', 'ASC')
            ->getQuery()
            ->getScalarResult();

        return array_column($result, 'salaryRange');
    }

    //    /**
    //     * @return JobRole[] Returns an array of JobRole objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('j')
    //            ->andWhere('j.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('j.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?JobRole
    //    {
    //        return $this->createQueryBuilder('j')
    //            ->andWhere('j.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
