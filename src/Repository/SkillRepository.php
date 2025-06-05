<?php

namespace App\Repository;

use App\Entity\Skill;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Pagerfanta\Doctrine\ORM\QueryAdapter;
use Pagerfanta\Pagerfanta;

/**
 * @extends ServiceEntityRepository<Skill>
 */
class SkillRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Skill::class);
    }

    /**
     * Get all distinct categories from skills
     * @return array
     */

    /**
     * Get paginated skills with optional category filter
     *
     * @param int $page Current page number (1-based)
     * @param int $maxPerPage Number of items per page
     * @param string|null $category Optional category filter
     * @return Pagerfanta
     */
    public function findPaginatedByCategory(int $page = 1, int $maxPerPage = 10, ?string $category = null): Pagerfanta
    {
        $qb = $this->createQueryBuilder('s')
            ->orderBy('s.name', 'ASC');


        if ($category) {
            $qb->andWhere('s.category = :category')
               ->setParameter('category', $category);
        }

        $adapter = new QueryAdapter($qb);
        $pagerfanta = new Pagerfanta($adapter);
        $pagerfanta->setMaxPerPage($maxPerPage);
        $pagerfanta->setCurrentPage($page);

        return $pagerfanta;
    }

    public function findDistinctCategories(): array
    {
        $result = $this->createQueryBuilder('s')
            ->select('DISTINCT s.category')
            ->where('s.category IS NOT NULL')
            ->andWhere('s.category != :empty')
            ->setParameter('empty', '')
            ->orderBy('s.category', 'ASC')
            ->getQuery()
            ->getScalarResult();

        // Extract just the category values from the result array
        return array_column($result, 'category');
    }

    //    /**
    //     * @return Skill[] Returns an array of Skill objects
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

    //    public function findOneBySomeField($value): ?Skill
    //    {
    //        return $this->createQueryBuilder('s')
    //            ->andWhere('s.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
