<?php

namespace App\Service;

use App\Entity\User;
Use App\Repository\StudentProgressRepository;
Use App\Repository\MicroCredentialRepository;

class SkillPassportService
{
    public function __construct(private StudentProgressRepository $studentProgressRepository,
                                private MicroCredentialRepository $microCredentialRepository)
    {
    }

    public function getSkillPassportData(User $student): array
    {
        $allProgress = $this->studentProgressRepository->findBy(['student' => $student], ['dateEarned' => 'DESC']);

        $categories = [];
        $statusCounts = ['Completed' => 0, 'InProgress' => 0, 'Verified' => 0, 'Under Review' => 0];

        foreach ($allProgress as $progress) {
            $category = $progress->getMicroCredential()->getCategory() ?? 'Other';
            if (!isset($categories[$category])) {
                $categories[$category] = [];
            }
            $categories[$category][] = $progress;

            if (isset($statusCounts[$progress->getStatus()])) {
                $statusCounts[$progress->getStatus()]++;
            }
        }

        $allCategories = $this->microCredentialRepository->findDistinctCategories();

        return [
            'allprogress' => $allProgress,
            'categories' => $categories,
            'statusCounts' => $statusCounts,
            'allCategories' => $allCategories,
            'totalCredentials' => count($allProgress),
            'completedCredentials' => $statusCounts['Completed'] + $statusCounts['Verified'],
        ];
    }

    public function filterCredentials(User $student, array $filters): array
    {
        $qb = $this->studentProgressRepository->createQueryBuilder('sp')
            ->leftJoin('sp.microCredential', 'mc')
            ->andWhere('sp.student = :student')
            ->setParameter('student', $student);

        if (!empty($filters['category'])) {
            $qb->andWhere('mc.category = :category')
                ->setParameter('category', $filters['category']);
        }

        if (!empty($filters['status'])) {
            $qb->andWhere('sp.status = :status')
                ->setParameter('status', $filters['status']);
        }

        if (!empty($filters['search'])) {
            $qb->andWhere('mc.name LIKE :search OR mc.description LIKE :search')
                ->setParameter('search', '%' . $filters['search'] . '%');
        }

        if (!empty($filters['dateFrom'])) {
            $qb->andWhere('sp.dateEarned >= :dateFrom')
                ->setParameter('dateFrom', new \DateTimeImmutable($filters['dateFrom']));
        }

        if (!empty($filters['dateTo'])) {
            $qb->andWhere('sp.dateEarned <= :dateTo')
                ->setParameter('dateTo', new \DateTimeImmutable($filters['dateTo']));
        }

        // Sorting
        $sortBy = $filters['sortBy'] ?? 'dateEarned';
        $sortOrder = $filters['sortOrder'] ?? 'DESC';

        switch ($sortBy) {
            case 'name':
                $qb->orderBy('mc.name', $sortOrder);
                break;
            case 'category':
                $qb->orderBy('mc.category', $sortOrder);
                break;
            case 'status':
                $qb->orderBy('sp.status', $sortOrder);
                break;
            default:
                $qb->orderBy('sp.dateEarned', $sortOrder);
        }

        return $qb->getQuery()->getResult();
    }

    public function getCredentialStats(User $student): array
    {
        $allProgress = $this->studentProgressRepository->findBy(['student' => $student]);

        $monthlyProgress = [];
        $categoryStats = [];
        $statusStats = [];

        foreach ($allProgress as $progress) {
            $month = $progress->getDateEarned()->format('Y-m');
            $monthlyProgress[$month] = ($monthlyProgress[$month] ?? 0) + 1;

            $category = $progress->getMicroCredential()->getCategory() ?? 'Other';
            $categoryStats[$category] = ($categoryStats[$category] ?? 0) + 1;

            $status = $progress->getStatus();
            $statusStats[$status] = ($statusStats[$status] ?? 0) + 1;
        }
        return [
            'monthlyProgress' => $monthlyProgress,
            'categoryStats' => $categoryStats,
            'statusStats' => $statusStats,
        ];
    }
}
