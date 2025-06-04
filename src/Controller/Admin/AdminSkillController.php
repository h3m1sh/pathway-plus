<?php

namespace App\Controller\Admin;

use App\Entity\Skill;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Repository\SkillRepository;



final class AdminSkillController extends AbstractController
{
    #[Route('/admin/skill', name: 'app_admin_skill')]
    public function index(SkillRepository $repository): Response
    {

        $skills = $repository->findAll();


        return $this->render('admin/skill/skill.html.twig', [
            'skills' => $skills,
        ]);
    }

    #[Route('/admin/skill/{id}', name: 'app_admin_skill_show', methods: ['GET'])]
    public function show(Skill $skill): Response
    {
        dd($skill);

        return $this->render('admin/skill/skill_show.html.twig', [
            'skill' => $skill,
        ]);

    }

    #[Route('/admin/skill/new', name: 'app_admin_skill_new', methods: ['GET', 'POST'])]
    public function new(){

    }


    #[Route('/admin/skill/edit/{id}', name: 'app_admin_skill_edit', methods: ['GET', 'POST'])]
    public function edit(Skill $skill)
    {

    }


    #[Route('/admin/skill/delete/{id}', name: 'app_admin_skill_delete', methods: ['DELETE'])]
    public function delete(Skill $skill): Response
    {

    }


}
