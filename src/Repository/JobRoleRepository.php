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
     * Get paginated job roles with optional search and industry filter
     *
     * @param int $page Current page number (1-based)
     * @param int $maxPerPage Number of items per page
     * @param string|null $search Optional search query
     * @param string|null $industry Optional industry filter
     * @return Pagerfanta
     */
    public function findPaginated(int $page = 1, int $maxPerPage = 10, ?string $search = null, ?string $industry = null): Pagerfanta
    {
        $qb = $this->createQueryBuilder('jr')
            ->orderBy('jr.title', 'ASC');

        if ($search) {
            $qb->andWhere('jr.title LIKE :search OR jr.industry LIKE :search OR jr.jobCode LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }

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

    /**
     * Get recently added job roles
     * @param int $limit Number of jobs to return
     * @return array
     */
    public function findRecentlyAdded(int $limit = 5): array
    {
        return $this->createQueryBuilder('jr')
            ->orderBy('jr.id', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Get trending/popular job roles (based on skills count as a simple metric)
     * @param int $limit Number of jobs to return
     * @return array
     */
    public function findTrendingJobs(int $limit = 10): array
    {
        // Get jobs with their skill counts
        $qb = $this->createQueryBuilder('jr')
            ->leftJoin('jr.skills', 's')
            ->addSelect('COUNT(s.id) as skillCount')
            ->groupBy('jr.id')
            ->orderBy('skillCount', 'DESC')
            ->addOrderBy('jr.title', 'ASC')
            ->setMaxResults($limit);
        
        return $qb->getQuery()->getResult();
    }

    /**
     * Get job statistics
     * @return array
     */
    public function getJobStatistics(): array
    {
        $totalJobs = $this->count([]);
        
        $industryStats = $this->createQueryBuilder('jr')
            ->select('jr.industry, COUNT(jr.id) as count')
            ->where('jr.industry IS NOT NULL')
            ->andWhere('jr.industry != :empty')
            ->setParameter('empty', '')
            ->groupBy('jr.industry')
            ->orderBy('count', 'DESC')
            ->setMaxResults(5)
            ->getQuery()
            ->getResult();

        // Calculate average skills per job
        $qb = $this->createQueryBuilder('jr')
            ->select('COUNT(s.id) as skillCount')
            ->leftJoin('jr.skills', 's')
            ->groupBy('jr.id');
        
        $skillCounts = $qb->getQuery()->getScalarResult();
        $avgSkillsPerJob = 0;
        
        if (!empty($skillCounts)) {
            $totalSkills = array_sum(array_column($skillCounts, 'skillCount'));
            $avgSkillsPerJob = round($totalSkills / count($skillCounts), 1);
        }

        return [
            'totalJobs' => $totalJobs,
            'topIndustries' => $industryStats,
            'avgSkillsPerJob' => $avgSkillsPerJob,
        ];
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
