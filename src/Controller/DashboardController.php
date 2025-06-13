<?php
// src/Controller/DashboardController.php

namespace App\Controller;

use App\Entity\User;
use App\Repository\StudentProgressRepository;
use App\Repository\UserRepository;
use App\Repository\SkillRepository;
use App\Repository\JobRoleRepository;
use App\Repository\MicroCredentialRepository;
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
    public function index(
        StudentProgressRepository $studentProgressRepository,
        SkillRepository $skillRepository,
        UserRepository $userRepository
    ): Response {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        if (in_array('ROLE_ADMIN', $user->getRoles())) {
            return $this->redirectToRoute('admin_dashboard');
        }

        $recentActivites = [
            'credentials' => $studentProgressRepository->findRecentProgress($user, 30),
            'skills' => $skillRepository->findRecentSkills($user, 30),
            'profile' => $userRepository->findRecentProfileUpdates($user, 30),
            'goals' => $userRepository->findRecentCareerGoals($user, 30),
        ];

        // Convert objects to arrays and add type information
        $allActivities = array_merge(
            array_map(function($p) {
                return [
                    'id' => $p->getId(),
                    'type' => 'credential',
                    'name' => $p->getMicroCredential()->getName(),
                    'dateEarned' => $p->getDateEarned(),
                    'status' => $p->getStatus(),
                    'badgeUrl' => $p->getMicroCredential()->getBadgeUrl(),
                    'microCredential' => [
                        'name' => $p->getMicroCredential()->getName(),
                        'category' => $p->getMicroCredential()->getCategory(),
                    ],
                    'verifiedBy' => $p->getVerifiedBy(),
                ];
            }, $recentActivites['credentials']),
            array_map(function($s) {
                return [
                    'id' => $s->getId(),
                    'type' => 'skill',
                    'name' => $s->getName(),
                    'dateEarned' => $s->getCreatedAt(),
                    'category' => $s->getCategory(),
                ];
            }, $recentActivites['skills']),
            array_map(function($u) use ($user) {
                return [
                    'id' => $user->getId(),
                    'type' => 'profile',
                    'name' => 'Profile Update',
                    'dateEarned' => $u['dateEarned'],
                    'updateDescription' => $u['updateDescription'] ?? 'Profile updated',
                ];
            }, $recentActivites['profile']),
            array_map(function($g) use ($user) {
                return [
                    'id' => $user->getId(),
                    'type' => 'goal',
                    'name' => 'Career Goal Update',
                    'dateEarned' => $g['dateEarned'],
                    'goalDescription' => $g['goalDescription'] ?? 'Career goal updated',
                ];
            }, $recentActivites['goals'])
        );

        usort($allActivities, fn($a, $b) => $b['dateEarned'] <=> $a['dateEarned']);

        $studentProgress = $studentProgressRepository->findBy(['student' => $user], ['dateEarned' => 'DESC']);
        $careerInterests = $user->getJobRoleInterests();

        $totalCredentials = count($studentProgress);
        $completedCredentials = count(array_filter($studentProgress, fn($p) => $p->isCompleted()));
        $completionRate = $totalCredentials > 0 ? round(($completedCredentials / $totalCredentials) * 100) : 0;

        return $this->render('dashboard/student.html.twig', [
            'user' => $user,
            'recentProgress' => $allActivities,
            'studentProgress' => $studentProgress,
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
