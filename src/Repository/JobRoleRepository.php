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
     * Get paginated job roles with optional search filter
     *
     * @param int $page Current page number (1-based)
     * @param int $maxPerPage Number of items per page
    * @param string|null $search Optional search query
     * @return Pagerfanta
     */
    public function findPaginated(int $page = 1, int $maxPerPage = 10, ?string $search = null): Pagerfanta
    {
        $qb = $this->createQueryBuilder('jr')
            ->orderBy('jr.title', 'ASC');

        if ($search) {
            $qb->andWhere('jr.title LIKE :search OR jr.industry LIKE :search OR jr.jobCode LIKE :search')
               ->setParameter('search', '%' . $search . '%');
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
