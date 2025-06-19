<?php
// src/Controller/DashboardController.php

namespace App\Controller;

use App\Entity\User;
use App\Repository\StudentProgressRepository;
use App\Repository\UserRepository;
use App\Repository\SkillRepository;
use App\Repository\JobRoleRepository;
use App\Repository\MicroCredentialRepository;
use App\Service\GeminiAiService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class DashboardController extends AbstractController
{
    public function __construct(
        private GeminiAiService $aiService
    ) {
    }

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
        $careerInterests = $user->getJobRoleInterests()->toArray();

        $totalCredentials = count($studentProgress);
        $completedCredentials = count(array_filter($studentProgress, fn($p) => $p->isCompleted()));
        $completionRate = $totalCredentials > 0 ? round(($completedCredentials / $totalCredentials) * 100) : 0;

        $careerPaths = [];
        foreach ($careerInterests as $jobRole) {
            $requiredSkills = $jobRole->getSkills();
            $totalSkills = count($requiredSkills);

            $earnedSkills = [];
            foreach ($studentProgress as $progress) {
                if ($progress->isCompleted()) {
                    foreach ($progress->getMicroCredential()->getSkills() as $skill) {
                        $earnedSkills[$skill->getId()] = true;
                    }
                }
            }
            $completedSkills = array_filter($requiredSkills->toArray(), function($skill) use ($earnedSkills) {
                return isset($earnedSkills[$skill->getId()]);
            });

            $completionPercentage = $totalSkills > 0 ? round((count($completedSkills) / $totalSkills) * 100) : 0;

            $missingSkills = array_filter($requiredSkills->toArray(), function($skill) use ($earnedSkills) {
                return !isset($earnedSkills[$skill->getId()]);
            });

            $careerPaths[] = [
                'jobRole' => $jobRole,
                'totalSkills' => $totalSkills,
                'completedSkills' => count($completedSkills),
                'completionPercentage' => $completionPercentage,
                'missingSkills' => $missingSkills,
                'industry' => $jobRole->getIndustry(),
                'salaryRange' => $jobRole->getSalaryRange(),
                'yearsOfTraining' => $jobRole->getYearsOfTraining(),
            ];
        }

        // Generate AI suggestions
        $aiSuggestions = $this->generateAiSuggestions($user, $studentProgress, $careerInterests);

        return $this->render('dashboard/student.html.twig', [
            'user' => $user,
            'recentProgress' => $allActivities,
            'studentProgress' => $studentProgress,
            'careerInterests' => $careerInterests,
            'careerPaths' => $careerPaths,
            'aiSuggestions' => $aiSuggestions,
            'stats' => [
                'totalCredentials' => $totalCredentials,
                'completedCredentials' => $completedCredentials,
                'completionRate' => $completionRate,
                'careerGoals' => count($careerInterests),
            ]
        ]);
    }

    /**
     * Generate AI-powered suggestions for the user
     */
    private function generateAiSuggestions(User $user, array $studentProgress, array $careerInterests): array
    {
        try {
            // Extract user skills from completed credentials
            $userSkills = [];
            $earnedCredentials = [];
            
            foreach ($studentProgress as $progress) {
                if ($progress->isCompleted()) {
                    $microCredential = $progress->getMicroCredential();
                    $earnedCredentials[] = [
                        'title' => $microCredential->getName(),
                        'category' => $microCredential->getCategory(),
                        'dateEarned' => $progress->getDateEarned()->format('Y-m-d')
                    ];
                    
                    foreach ($microCredential->getSkills() as $skill) {
                        $userSkills[] = [
                            'name' => $skill->getName(),
                            'category' => $skill->getCategory(),
                            'level' => $skill->getLevel()
                        ];
                    }
                }
            }

            // Extract career interests
            $interests = array_map(function($interest) {
                return [
                    'title' => $interest->getTitle(),
                    'industry' => $interest->getIndustry(),
                    'salaryRange' => $interest->getSalaryRange()
                ];
            }, $careerInterests);

            // Generate AI suggestions
            $suggestions = $this->aiService->generateCareerSuggestions($userSkills, $interests, $earnedCredentials);
            
            // If AI fails, return default suggestions
            if (isset($suggestions['error']) && $suggestions['error']) {
                return $this->getDefaultSuggestions($userSkills, $interests, $earnedCredentials);
            }

            return $suggestions;
        } catch (\Exception $e) {
            // Log error and return default suggestions
            return $this->getDefaultSuggestions([], [], []);
        }
    }

    /**
     * Generate default suggestions when AI is unavailable
     */
    private function getDefaultSuggestions(array $userSkills, array $interests, array $earnedCredentials): array
    {
        $skillCount = count($userSkills);
        $interestCount = count($interests);
        $credentialCount = count($earnedCredentials);

        return [
            'suggestions' => [
                [
                    'role' => 'Continue Learning',
                    'match_score' => 90,
                    'reasoning' => 'Based on your current progress, focus on completing more micro-credentials to strengthen your profile.',
                    'required_skills' => ['Continuous Learning', 'Time Management'],
                    'salary_range' => 'Varies',
                    'growth_potential' => 'High'
                ],
                [
                    'role' => 'Skill Development',
                    'match_score' => 85,
                    'reasoning' => 'Consider developing complementary skills to enhance your career prospects.',
                    'required_skills' => ['Communication', 'Problem Solving'],
                    'salary_range' => 'Varies',
                    'growth_potential' => 'High'
                ]
            ],
            'reasoning' => 'Focus on building a strong foundation of skills and credentials to advance your career goals.',
            'next_steps' => [
                'Complete 2-3 more micro-credentials this month',
                'Explore new skill areas related to your interests',
                'Network with professionals in your target industry'
            ]
        ];
    }

    #[Route('/dashboard/suggestions/refresh', name: 'app_dashboard_refresh_suggestions', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function refreshSuggestions(Request $request): JsonResponse
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        
        try {
            $studentProgressRepository = $this->container->get(StudentProgressRepository::class);
            $studentProgress = $studentProgressRepository->findBy(['student' => $user], ['dateEarned' => 'DESC']);
            $careerInterests = $user->getJobRoleInterests()->toArray();
            
            $suggestions = $this->generateAiSuggestions($user, $studentProgress, $careerInterests);
            
            return $this->json([
                'success' => true,
                'suggestions' => $suggestions
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => 'Failed to refresh suggestions'
            ], 500);
        }
    }

    #[Route('/dashboard/preferences/save', name: 'app_dashboard_save_preferences', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function savePreferences(Request $request): JsonResponse
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        
        $data = json_decode($request->getContent(), true);
        
        if (!$data) {
            return $this->json(['success' => false, 'message' => 'Invalid data'], 400);
        }
        
        try {
            // Store preferences in user entity (you might want to add a preferences field)
            // For now, we'll store in session
            $request->getSession()->set('dashboard_preferences_' . $user->getId(), $data);
            
            return $this->json([
                'success' => true,
                'message' => 'Preferences saved successfully'
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Failed to save preferences'
            ], 500);
        }
    }

    #[Route('/dashboard/preferences/load', name: 'app_dashboard_load_preferences', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function loadPreferences(Request $request): JsonResponse
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        
        try {
            $preferences = $request->getSession()->get('dashboard_preferences_' . $user->getId(), null);
            
            return $this->json([
                'success' => true,
                'preferences' => $preferences
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Failed to load preferences'
            ], 500);
        }
    }

    #[Route('/admin', name: 'admin_dashboard')]
    #[IsGranted('ROLE_ADMIN')]
    public function adminDashboard(
        UserRepository $userRepository,
        SkillRepository $skillRepository,
        JobRoleRepository $jobRoleRepository,
        MicroCredentialRepository $microCredentialRepository
    ): Response {
        $totalUsers = $userRepository->count([]);
        $totalSkills = $skillRepository->count([]);
        $totalJobRoles = $jobRoleRepository->count([]);
        $totalMicroCredentials = $microCredentialRepository->count([]);

        $recentUsers = $userRepository->findBy([], ['createdAt' => 'DESC'], 5);
        $recentSkills = $skillRepository->findBy([], ['createdAt' => 'DESC'], 5);
        $recentJobRoles = $jobRoleRepository->findBy([], ['createdAt' => 'DESC'], 5);
        $recentMicroCredentials = $microCredentialRepository->findBy([], ['createdAt' => 'DESC'], 5);

        return $this->render('admin/dashboard/index.html.twig', [
            'totalUsers' => $totalUsers,
            'totalSkills' => $totalSkills,
            'totalJobRoles' => $totalJobRoles,
            'totalMicroCredentials' => $totalMicroCredentials,
            'recentUsers' => $recentUsers,
            'recentSkills' => $recentSkills,
            'recentJobRoles' => $recentJobRoles,
            'recentMicroCredentials' => $recentMicroCredentials,
        ]);
    }

    #[Route('/profile', name: 'app_profile')]
    #[IsGranted('ROLE_USER')]
    public function profile(): Response
    {
        return $this->redirectToRoute('app_profile_index');
    }
}
