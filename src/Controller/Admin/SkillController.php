<?php

namespace App\Controller\Admin;

use App\Entity\Skill;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Repository\SkillRepository;
use Symfony\Component\HttpFoundation\Request;
use App\Form\SkillFormType;
use Doctrine\ORM\EntityManagerInterface;

#[Route('/admin/skill')]
final class SkillController extends AbstractController
{
    #[Route( name: 'app_admin_skill_index')]
    public function index(SkillRepository $repository, Request $request): Response
    {
        $page = $request->query->getInt('page', 1);
        $search = $request->query->getString('search');
        $itemsPerPage = 25;

        $skills = $repository->findPaginated($page, $itemsPerPage, $search);

        return $this->render('admin/skill/student.html.twig', [
            'skills' => $skills->getCurrentPageResults(),
            'currentPage' => $skills->getCurrentPage(),
            'totalPages' => $skills->getNbPages(),
            'totalItems' => $skills->getNbResults(),
            'itemsPerPage' => $itemsPerPage,
            'search' => $search,
        ]);
    }

    #[Route('/new', name: 'app_admin_skill_new', methods: ['GET', 'POST'])]
    public function new(EntityManagerInterface $entityManager, Request $request): Response
    {
        $skill = new Skill();
        $form = $this->createForm(SkillFormType::class, $skill);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $skill = $form->getData();
            $skill->setUpdatedAt(new \DateTimeImmutable());
            $entityManager->persist($skill);
            $entityManager->flush();

            $this->addFlash('success', 'Skill created successfully!');
            return $this->redirectToRoute('app_admin_skill_index');
        }

        return $this->render('admin/skill/new.html.twig', [
            'form' => $form->createView(),
            'skill' => $skill,
        ]);
    }

    #[Route('/edit/{id}', name: 'app_admin_skill_edit', methods: ['GET', 'POST'])]
    public function edit(int $id, SkillRepository $skillRepository, Request $request, EntityManagerInterface $entityManager): Response
    {
        $skill = $skillRepository->find($id);

        if (!$skill) {
            throw $this->createNotFoundException('Skill not found');
        }

        $form = $this->createForm(SkillFormType::class, $skill);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $skill->setUpdatedAt(new \DateTimeImmutable());
            $entityManager->persist($skill);
            $entityManager->flush();

            $this->addFlash('success', 'Skill updated successfully!');
            return $this->redirectToRoute('app_admin_skill_index');
        }

        return $this->render('admin/skill/edit.html.twig', [
            'skill' => $skill,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/delete/{id}', name: 'app_admin_skill_delete', methods: ['DELETE'])]
    public function delete(SkillRepository $skillRepository, Request $request, EntityManagerInterface $entityManager, int $id): Response
    {
        $skill = $skillRepository->find($id);

        if (!$skill) {
            throw $this->createNotFoundException('Skill not found');
        }

        $entityManager->remove($skill);
        $entityManager->flush();

        $this->addFlash('success', 'Skill deleted successfully!');
        return $this->redirectToRoute('app_admin_skill_index');
    }
}
