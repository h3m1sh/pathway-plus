<?php
// src/Controller/DashboardController.php

namespace App\Controller;

use App\Repository\StudentProgressRepository;
use App\Repository\UserRepository;
use App\Repository\SkillRepository;
use App\Repository\JobRoleRepository;
use App\Repository\MicroCredentialRepository;
use App\Entity\StudentProgress;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class DashboardController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function home(): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_dashboard');
        }

        return $this->render('dashboard/home.html.twig');
    }

    #[Route('/dashboard', name: 'app_dashboard')]
    #[IsGranted('ROLE_USER')]
    public function dashboard(StudentProgressRepository $studentProgressRepository,
        JobRoleRepository $jobRoleRepository): Response
    {
        $user = $this->getUser();

        if ($user->isAdmin()) {
            return $this->redirectToRoute('app_admin_dashboard');
        }

        $studentProgress = $studentProgressRepository->findBy(['student' => $user], ['dataEarned' => 'DESC']);
        $recentProgress = $studentProgressRepository->findRecentProgress($user, 30);
        $careerInterests = $user->getJobRoleInterests();

        $totalCredentials = count($studentProgress);
        $completedCredentials = count(array_filter($studentProgress, fn($p) => $p->isCompleted()));
        $completionRate = $totalCredentials > 0 ? round(($completedCredentials / $totalCredentials) * 100) : 0;


        return $this->render('dashboard/student.html.twig', [
            'user' => $user,
            'studentProgress' => $studentProgress,
            'recentProgress' => $recentProgress,
            'careerInterests' => $careerInterests,
            'stats' => [
                'totalCredentials' => $totalCredentials,
                'completedCredentials' => $completedCredentials,
                'completionRate' => $completionRate,
                'careerGoals' => count($careerInterests),
            ]
        ]);
    }

    #[Route('/admin', name: 'admin_dashboard')]
    #[IsGranted('ROLE_ADMIN')]
    public function adminDashboard(
        UserRepository $userRepository,
        SkillRepository $skillRepository,
        JobRoleRepository $jobRoleRepository,
        MicroCredentialRepository $microCredentialRepository
    ): Response {
        // Get all users and calculate role-based statistics
        $allUsers = $userRepository->findAll();
        $totalUsers = count($allUsers);

        $totalStudents = 0;
        $totalAdmins = 0;
        $activeUsers = 0;

        foreach ($allUsers as $user) {
            if ($user->isAdmin()) {
                $totalAdmins++;
            } elseif ($user->isStudent()) {
                $totalStudents++;
            }

            if ($user->isActive()) {
                $activeUsers++;
            }
        }

        $totalSkills = count($skillRepository->findAll());
        $totalJobRoles = count($jobRoleRepository->findAll());
        $totalMicroCredentials = count($microCredentialRepository->findAll());

        // Get recent items for quick access
        $recentUsers = $userRepository->findBy([], ['createdAt' => 'DESC'], 5);
        $recentSkills = $skillRepository->findBy([], ['createdAt' => 'DESC'], 5);
        $recentJobRoles = $jobRoleRepository->findBy([], ['createdAt' => 'DESC'], 5);
        $recentMicroCredentials = $microCredentialRepository->findBy([], ['createdAt' => 'DESC'], 5);

        return $this->render('admin/dashboard/index.html.twig', [
            'user' => $this->getUser(),
            'stats' => [
                'users' => [
                    'total' => $totalUsers,
                    'students' => $totalStudents,
                    'admins' => $totalAdmins,
                    'active' => $activeUsers,
                ],
                'skills' => $totalSkills,
                'jobRoles' => $totalJobRoles,
                'microCredentials' => $totalMicroCredentials,
            ],
            'recent' => [
                'users' => $recentUsers,
                'skills' => $recentSkills,
                'jobRoles' => $recentJobRoles,
                'microCredentials' => $recentMicroCredentials,
            ],
        ]);
    }

    #[Route('/profile', name: 'app_profile')]
    #[IsGranted('ROLE_USER')]
    public function profile(): Response
    {
        return $this->render('dashboard/profile.html.twig', [
            'user' => $this->getUser(),
        ]);
    }
}
