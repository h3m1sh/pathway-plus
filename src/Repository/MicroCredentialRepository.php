<?php

namespace App\Repository;

use App\Entity\MicroCredential;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Pagerfanta\Doctrine\ORM\QueryAdapter;
use Pagerfanta\Pagerfanta;

/**
 * @extends ServiceEntityRepository<MicroCredential>
 */
class MicroCredentialRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MicroCredential::class);
    }

    /**
     * Get paginated micro-credentials with optional search filter
     *
     * @param int $page Current page number (1-based)
     * @param int $maxPerPage Number of items per page
     * @param string|null $search Optional search query
     * @return Pagerfanta
     */
    public function findPaginated(int $page = 1, int $maxPerPage = 10, ?string $search = null): Pagerfanta
    {
        $qb = $this->createQueryBuilder('m')
            ->orderBy('m.name', 'ASC');

        if ($search) {
            $qb->andWhere('m.name LIKE :search OR m.description LIKE :search OR m.category LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }

        $adapter = new QueryAdapter($qb);
        $pagerfanta = new Pagerfanta($adapter);
        $pagerfanta->setMaxPerPage($maxPerPage);
        $pagerfanta->setCurrentPage($page);

        return $pagerfanta;
    }

    /**
     * Get all distinct categories from micro-credentials
     * @return array
     */
    public function findDistinctCategories(): array
    {
        $result = $this->createQueryBuilder('m')
            ->select('DISTINCT m.category')
            ->where('m.category IS NOT NULL')
            ->andWhere('m.category != :empty')
            ->setParameter('empty', '')
            ->orderBy('m.category', 'ASC')
            ->getQuery()
            ->getScalarResult();

        // Extract just the category values from the result array
        return array_column($result, 'category');
    }

    //    /**
    //     * @return MicroCredential[] Returns an array of MicroCredential objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('m')
    //            ->andWhere('m.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('m.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?MicroCredential
    //    {
    //        return $this->createQueryBuilder('m')
    //            ->andWhere('m.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
