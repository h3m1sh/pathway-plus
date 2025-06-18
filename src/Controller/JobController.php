<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\JobRoleRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/jobs')]
class JobController extends AbstractController
{
    public function __construct(
        private JobRoleRepository $jobRoleRepository
    ) {}

    #[Route('/', name: 'app_jobs_browse', methods: ['GET'])]
    public function browse(Request $request): Response
    {
        $page = max(1, (int) $request->query->get('page', 1));
        $search = $request->query->get('search');
        $maxPerPage = 10;

        $pagerfanta = $this->jobRoleRepository->findPaginated($page, $maxPerPage, $search);

        return $this->render('jobs/browse.html.twig', [
            'jobs' => $pagerfanta,
            'search' => $search,
        ]);
    }

    #[Route('/{id}', name: 'app_jobs_show', methods: ['GET'])]
    public function show(int $id): Response
    {
        $job = $this->jobRoleRepository->find($id);

        if (!$job) {
            throw $this->createNotFoundException('Job role not found.');
        }

        return $this->render('jobs/show.html.twig', [
            'job' => $job,
        ]);
    }
} 