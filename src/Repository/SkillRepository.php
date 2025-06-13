<?php

namespace App\Repository;

use App\Entity\Skill;
use App\Entity\User;
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
     * Get paginated skills with optional search filter
     *
     * @param int $page Current page number (1-based)
     * @param int $maxPerPage Number of items per page
     * @param string|null $search Optional search query
     * @return Pagerfanta
     */
    public function findPaginated(int $page = 1, int $maxPerPage = 10, ?string $search = null): Pagerfanta
    {
        $qb = $this->createQueryBuilder('s')
            ->orderBy('s.name', 'ASC');

        if ($search) {
            $qb->andWhere('s.name LIKE :search OR s.description LIKE :search OR s.category LIKE :search')
               ->setParameter('search', '%' . $search . '%');
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

    public function findRecentSkills(User $user, int $days): array
    {
        $date = new \DateTimeImmutable("-{$days} days");

        return $this->createQueryBuilder('s')
            ->join('s.microCredentials', 'mc')
            ->join('mc.studentProgress', 'p')
            ->andWhere('s.student = :user')
            ->andWhere('p.dateEarned >= :date')
            ->setParameter('user', $user)
            ->setParameter('date', $date)
            ->orderBy('p.dateEarned', 'DESC')
            ->getQuery()
            ->getResult();
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
