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

final class AdminSkillController extends AbstractController
{
    #[Route('/admin/skill', name: 'app_admin_skill')]
    public function index(SkillRepository $repository, Request $request): Response
    {
        $page = $request->query->getInt('page', 1);
        $itemsPerPage = 5;
        
        $skills = $repository->findPaginatedByCategory($page, $itemsPerPage);

        return $this->render('admin/skill/skill.html.twig', [
            'skills' => $skills->getCurrentPageResults(),
            'currentPage' => $skills->getCurrentPage(),
            'totalPages' => $skills->getNbPages(),
            'totalItems' => $skills->getNbResults(),
            'itemsPerPage' => $itemsPerPage,
        ]);
    }

    #[Route('/admin/skill/new', name: 'app_admin_skill_new', methods: ['GET', 'POST'])]
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
            return $this->redirectToRoute('app_admin_skill');
        }

        return $this->render('admin/skill/skillForm.html.twig', [
            'form' => $form->createView(),
            'skill' => $skill,
        ]);
    }

    #[Route('/admin/skill/{id}', name: 'app_admin_skill_show', methods: ['GET', 'POST'])]
    public function show(int $id, SkillRepository $skillRepository, Request $request, EntityManagerInterface $entityManager): Response
    {
        $skill = $skillRepository->find($id);

        if (!$skill) {
            throw $this->createNotFoundException('Skill not found');
        }

        $form = $this->createForm(SkillFormType::class, $skill);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($skill);
            $entityManager->flush();
            return $this->redirectToRoute('app_admin_skill');
        }



        return $this->render('admin/skill/skillForm.html.twig', [
            'skill' => $skill,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/admin/skill/edit/{id}', name: 'app_admin_skill_edit', methods: ['GET', 'POST'])]
    public function edit(int $id,SkillRepository $skillRepository, Request $request, EntityManagerInterface $entityManager): Response
    {
        $skill = $skillRepository->find($id);

        if (!$skill) {
            throw $this->createNotFoundException('Skill not found');
        }

        $form = $this->createForm(SkillFormType::class, $skill);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($skill);
            $entityManager->flush();
            return $this->redirectToRoute('app_admin_skill');
        }

        return $this->render('admin/skill/skillForm.html.twig', [
            'skill' => $skill,
            'form' => $form->createView(),
        ]);


    }

    #[Route('/admin/skill/delete/{id}', name: 'app_admin_skill_delete', methods: ['DELETE'])]
    public function delete(SkillRepository $skillRepository, Request $request, EntityManagerInterface $entityManager, int $id): Response
    {
        $skill = $skillRepository->find($id);


        if (!$skill) {
            throw $this->createNotFoundException('Skill not found');
        }


        $entityManager->remove($skill);
        $entityManager->flush();
        return $this->redirectToRoute('app_admin_skill');

    }
}
