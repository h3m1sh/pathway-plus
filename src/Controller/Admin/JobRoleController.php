<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\JobRole;
use App\Form\JobRoleFormType;
use App\Repository\JobRoleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/job-role')]
final class JobRoleController extends AbstractController
{
    #[Route(name: 'app_admin_job_role_index', methods: ['GET'])]
    public function index(JobRoleRepository $repository, Request $request): Response
    {
        $page = $request->query->getInt('page', 1);
        $itemsPerPage = 10;

        $jobRoles = $repository->findPaginatedByIndustry($page, $itemsPerPage);

        return $this->render('admin/job_role/index.html.twig', [
            'job_roles' => $jobRoles->getCurrentPageResults(),
            'currentPage' => $jobRoles->getCurrentPage(),
            'totalPages' => $jobRoles->getNbPages(),
            'totalItems' => $jobRoles->getNbResults(),
            'itemsPerPage' => $itemsPerPage,
        ]);
    }

    #[Route('/{id}', name: 'app_admin_job_role_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(JobRole $jobRole): Response
    {
        return $this->render('admin/job_role/show.html.twig', [
            'job_role' => $jobRole,
        ]);
    }

    #[Route('/new', name: 'app_admin_job_role_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $jobRole = new JobRole();
        $form = $this->createForm(JobRoleFormType::class, $jobRole);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Mark as manually created/edited since it's not from API sync
            $jobRole->markAsManuallyEdited();
            
            $entityManager->persist($jobRole);
            $entityManager->flush();

            $this->addFlash('success', 'Job role created successfully!');
            return $this->redirectToRoute('app_admin_job_role_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/job_role/new.html.twig', [
            'job_role' => $jobRole,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_admin_job_role_edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function edit(Request $request, JobRole $jobRole, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(JobRoleFormType::class, $jobRole);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Mark as manually edited
            $jobRole->markAsManuallyEdited();
            
            $entityManager->flush();

            $this->addFlash('success', 'Job role updated successfully!');
            return $this->redirectToRoute('app_admin_job_role_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/job_role/edit.html.twig', [
            'job_role' => $jobRole,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_admin_job_role_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(Request $request, JobRole $jobRole, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$jobRole->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($jobRole);
            $entityManager->flush();
            
            $this->addFlash('success', 'Job role deleted successfully!');
        } else {
            $this->addFlash('error', 'Invalid security token.');
        }

        return $this->redirectToRoute('app_admin_job_role_index', [], Response::HTTP_SEE_OTHER);
    }
} 