<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\JobRole;
use App\Repository\JobRoleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/jobs')]
class JobController extends AbstractController
{
    public function __construct(
        private JobRoleRepository $jobRoleRepository,
        private EntityManagerInterface $entityManager
    ) {}

    #[Route('/', name: 'app_jobs_browse', methods: ['GET'])]
    public function browse(Request $request): Response
    {
        $page = max(1, (int) $request->query->get('page', 1));
        $search = $request->query->get('search');
        $industry = $request->query->get('industry');
        $maxPerPage = 12;

        $pagerfanta = $this->jobRoleRepository->findPaginated($page, $maxPerPage, $search, $industry);

        // Get additional data for side components
        $recentlyAddedJobs = $this->jobRoleRepository->findRecentlyAdded(5);
        $trendingJobs = $this->jobRoleRepository->findTrendingJobs(10);
        $jobStats = $this->jobRoleRepository->getJobStatistics();

        return $this->render('jobs/browse.html.twig', [
            'jobs' => $pagerfanta,
            'search' => $search,
            'industry' => $industry,
            'recentlyAddedJobs' => $recentlyAddedJobs,
            'trendingJobs' => $trendingJobs,
            'jobStats' => $jobStats,
        ]);
    }

    #[Route('/{id}', name: 'app_jobs_show', methods: ['GET'])]
    public function show(int $id): Response
    {
        $job = $this->jobRoleRepository->find($id);

        if (!$job) {
            throw $this->createNotFoundException('Job role not found.');
        }

        
        $userHasInterest = false;
        $user = $this->getUser();
        if ($user) {
            $userHasInterest = $user->getJobRoleInterests()->contains($job);
        }

        return $this->render('jobs/show.html.twig', [
            'job' => $job,
            'userHasInterest' => $userHasInterest,
        ]);
    }

    #[Route('/{id}/add-to-interests', name: 'app_jobs_add_to_interests', methods: ['POST'])]
    public function addToInterests(int $id): JsonResponse
    {
        $job = $this->jobRoleRepository->find($id);

        if (!$job) {
            return $this->json(['success' => false, 'message' => 'Job not found'], 404);
        }

        $user = $this->getUser();
        if (!$user) {
            return $this->json(['success' => false, 'message' => 'User not authenticated'], 401);
        }

        // Check if already in interests
        if ($user->getJobRoleInterests()->contains($job)) {
            return $this->json(['success' => false, 'message' => 'Job already in interests'], 400);
        }

        // Add to interests
        $user->addJobRoleInterest($job);
        $this->entityManager->flush();

        return $this->json([
            'success' => true, 
            'message' => 'Job added to interests successfully'
        ]);
    }

    #[Route('/{id}/remove-from-interests', name: 'app_jobs_remove_from_interests', methods: ['POST'])]
    public function removeFromInterests(int $id): JsonResponse
    {
        $job = $this->jobRoleRepository->find($id);

        if (!$job) {
            return $this->json(['success' => false, 'message' => 'Job not found'], 404);
        }

        $user = $this->getUser();
        if (!$user) {
            return $this->json(['success' => false, 'message' => 'User not authenticated'], 401);
        }

        // Remove from interests
        $user->removeJobRoleInterest($job);
        $this->entityManager->flush();

        return $this->json([
            'success' => true, 
            'message' => 'Job removed from interests successfully'
        ]);
    }
} 