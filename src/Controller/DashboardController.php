<?php
// src/Controller/DashboardController.php

declare(strict_types=1);

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
        /** @var User $user */
        $user = $this->getUser();

        if (in_array('ROLE_ADMIN', $user->getRoles())) {
            return $this->redirectToRoute('admin_dashboard');
        }

        $studentProgress = $studentProgressRepository->findBy(['student' => $user], ['dateEarned' => 'DESC']);
        $careerInterests = $user->getJobRoleInterests()->toArray();
        $recentActivities = $this->getRecentActivities($user, $studentProgressRepository, $skillRepository, $userRepository);
        $careerPaths = $this->buildCareerPaths($studentProgress, $careerInterests);
        $stats = $this->calculateStats($studentProgress, $careerInterests);

        return $this->render('dashboard/student.html.twig', [
            'user' => $user,
            'recentProgress' => $recentActivities,
            'studentProgress' => $studentProgress,
            'careerInterests' => $careerInterests,
            'careerPaths' => $careerPaths,
            'aiSuggestions' => $this->generateAiSuggestions($user, $studentProgress, $careerInterests),
            'skillRecommendations' => $this->generateSkillRecommendations($user, $studentProgress, $careerInterests),
            'stats' => $stats
        ]);
    }

    #[Route('/dashboard/suggestions/refresh', name: 'app_dashboard_refresh_suggestions', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function refreshSuggestions(
        Request $request,
        StudentProgressRepository $studentProgressRepository
    ): JsonResponse {
        /** @var User $user */
        $user = $this->getUser();
        $studentProgress = $studentProgressRepository->findBy(['student' => $user], ['dateEarned' => 'DESC']);
        $careerInterests = $user->getJobRoleInterests()->toArray();

        $suggestions = $this->generateAiSuggestions($user, $studentProgress, $careerInterests);

        return new JsonResponse([
            'success' => true,
            'suggestions' => $suggestions
        ]);
    }

    #[Route('/dashboard/preferences/save', name: 'app_dashboard_save_preferences', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function savePreferences(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            // Store preferences in session for now
            $request->getSession()->set('dashboard_preferences', $data);

            return new JsonResponse(['success' => true]);
        } catch (\Exception $e) {
            return new JsonResponse(['success' => false, 'message' => 'Failed to save preferences']);
        }
    }

    #[Route('/dashboard/preferences/load', name: 'app_dashboard_load_preferences', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function loadPreferences(Request $request): JsonResponse
    {
        try {
            $preferences = $request->getSession()->get('dashboard_preferences', []);

            return new JsonResponse([
                'success' => true,
                'preferences' => $preferences
            ]);
        } catch (\Exception $e) {
            return new JsonResponse(['success' => false, 'message' => 'Failed to load preferences']);
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
        $stats = [
            'users' => [
                'total' => $userRepository->count([]),
                'students' => $userRepository->count(['roles' => ['ROLE_STUDENT']]),
                'admins' => $userRepository->count(['roles' => ['ROLE_ADMIN']]),
                'active' => $userRepository->count(['isActive' => true]),
            ],
            'content' => [
                'skills' => $skillRepository->count([]),
                'jobRoles' => $jobRoleRepository->count([]),
                'microCredentials' => $microCredentialRepository->count([]),
            ],
        ];
        $users = $userRepository->findBy([], ['createdAt' => 'DESC'], 10);
        $skills = $skillRepository->findBy([], ['createdAt' => 'DESC'], 10);
        $recent = [
            'users' => $users,
            'skills' => $skills,
        ];
        return $this->render('admin/dashboard/index.html.twig', [
            'stats' => $stats,
            'user' => $this->getUser(),
            'recent' => $recent,
        ]);
    }

    #[Route('/profile', name: 'app_profile')]
    #[IsGranted('ROLE_USER')]
    public function profile(): Response
    {
        return $this->redirectToRoute('app_profile');
    }

    private function getRecentActivities(User $user, StudentProgressRepository $progressRepo, SkillRepository $skillRepo, UserRepository $userRepo): array
    {
        $activities = [
            'credentials' => $progressRepo->findRecentProgress($user, 30),
            'skills' => $skillRepo->findRecentSkills($user, 30),
            'profile' => $userRepo->findRecentProfileUpdates($user, 30),
            'goals' => $userRepo->findRecentCareerGoals($user, 30),
        ];

        $allActivities = array_merge(
            array_map(fn($p) => [
                'id' => $p->getId(),
                'type' => 'credential',
                'name' => $p->getMicroCredential()->getName(),
                'dateEarned' => $p->getDateEarned(),
                'status' => $p->getStatus(),
                'microCredential' => [
                    'name' => $p->getMicroCredential()->getName(),
                    'category' => $p->getMicroCredential()->getCategory(),
                ],
                'verifiedBy' => $p->getVerifiedBy(),
            ], $activities['credentials']),
            array_map(fn($s) => [
                'id' => $s->getId(),
                'type' => 'skill',
                'name' => $s->getName(),
                'dateEarned' => $s->getCreatedAt(),
                'category' => $s->getCategory(),
            ], $activities['skills']),
            array_map(fn($u) => [
                'id' => $user->getId(),
                'type' => 'profile',
                'name' => 'Profile Update',
                'dateEarned' => $u['dateEarned'],
                'updateDescription' => $u['updateDescription'] ?? 'Profile updated',
            ], $activities['profile']),
            array_map(fn($g) => [
                'id' => $user->getId(),
                'type' => 'goal',
                'name' => 'Career Goal Update',
                'dateEarned' => $g['dateEarned'],
                'goalDescription' => $g['goalDescription'] ?? 'Career goal updated',
            ], $activities['goals'])
        );

        usort($allActivities, fn($a, $b) => $b['dateEarned'] <=> $a['dateEarned']);
        return $allActivities;
    }

    private function buildCareerPaths(array $studentProgress, array $careerInterests): array
    {
        $careerPaths = [];
        $earnedSkills = $this->getEarnedSkills($studentProgress);

        foreach ($careerInterests as $jobRole) {
            $requiredSkills = $jobRole->getSkills();
            $totalSkills = count($requiredSkills);
            $completedSkills = array_filter($requiredSkills->toArray(), fn($skill) => isset($earnedSkills[$skill->getId()]));
            $completionPercentage = $totalSkills > 0 ? round((count($completedSkills) / $totalSkills) * 100) : 0;

            $careerPaths[] = [
                'jobRole' => $jobRole,
                'totalSkills' => $totalSkills,
                'completedSkills' => count($completedSkills),
                'completionPercentage' => $completionPercentage,
                'missingSkills' => array_filter($requiredSkills->toArray(), fn($skill) => !isset($earnedSkills[$skill->getId()])),
                'industry' => $jobRole->getIndustry(),
                'salaryRange' => $jobRole->getSalaryRange(),
                'yearsOfTraining' => $jobRole->getYearsOfTraining(),
            ];
        }

        return $careerPaths;
    }

    private function getEarnedSkills(array $studentProgress): array
    {
        $earnedSkills = [];
        foreach ($studentProgress as $progress) {
            if ($progress->isCompleted()) {
                foreach ($progress->getMicroCredential()->getSkills() as $skill) {
                    $earnedSkills[$skill->getId()] = true;
                }
            }
        }
        return $earnedSkills;
    }

    private function calculateStats(array $studentProgress, array $careerInterests): array
    {
        $totalCredentials = count($studentProgress);
        $completedCredentials = count(array_filter($studentProgress, fn($p) => $p->isCompleted()));
        $completionRate = $totalCredentials > 0 ? round(($completedCredentials / $totalCredentials) * 100) : 0;

        return [
            'totalCredentials' => $totalCredentials,
            'completedCredentials' => $completedCredentials,
            'completionRate' => $completionRate,
            'careerGoals' => count($careerInterests),
        ];
    }

    private function generateAiSuggestions(User $user, array $studentProgress, array $careerInterests): array
    {
        try {
            $userSkills = $this->extractUserSkills($studentProgress);
            $interests = array_map(fn($interest) => [
                'title' => $interest->getTitle(),
                'industry' => $interest->getIndustry(),
                'salaryRange' => $interest->getSalaryRange()
            ], $careerInterests);
            $earnedCredentials = $this->extractEarnedCredentials($studentProgress);

            $suggestions = $this->aiService->generateCareerSuggestions($userSkills, $interests, $earnedCredentials);

            return isset($suggestions['error']) && $suggestions['error']
                ? $this->getDefaultSuggestions()
                : $suggestions;
        } catch (\Exception $e) {
            return $this->getDefaultSuggestions();
        }
    }

    private function extractUserSkills(array $studentProgress): array
    {
        $userSkills = [];
        foreach ($studentProgress as $progress) {
            if ($progress->isCompleted()) {
                foreach ($progress->getMicroCredential()->getSkills() as $skill) {
                    $userSkills[] = [
                        'name' => $skill->getName(),
                        'category' => $skill->getCategory(),
                        'difficulty' => $skill->getDifficulty()
                    ];
                }
            }
        }
        return $userSkills;
    }

    private function extractEarnedCredentials(array $studentProgress): array
    {
        $earnedCredentials = [];
        foreach ($studentProgress as $progress) {
            if ($progress->isCompleted()) {
                $microCredential = $progress->getMicroCredential();
                $earnedCredentials[] = [
                    'title' => $microCredential->getName(),
                    'category' => $microCredential->getCategory(),
                    'dateEarned' => $progress->getDateEarned()->format('Y-m-d')
                ];
            }
        }
        return $earnedCredentials;
    }

    private function getDefaultSuggestions(): array
    {
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
            'next_steps' => [
                'Complete at least 3 more micro-credentials',
                'Focus on skills that align with your career interests',
                'Consider taking advanced level credentials'
            ],
            'reasoning' => 'Based on your current skill set and career interests, we recommend focusing on continuous learning and skill development.'
        ];
    }

    private function generateSkillRecommendations(User $user, array $studentProgress, array $careerInterests): array
    {
        try {
            $userSkills = $this->extractUserSkills($studentProgress);
            $interests = array_map(fn($interest) => [
                'title' => $interest->getTitle(),
                'industry' => $interest->getIndustry(),
                'salaryRange' => $interest->getSalaryRange()
            ], $careerInterests);
            $earnedCredentials = $this->extractEarnedCredentials($studentProgress);

            $recommendations = $this->aiService->generateSkillRecommendations($userSkills, $interests, $earnedCredentials);

            return isset($recommendations['error']) && $recommendations['error']
                ? $this->getDefaultSkillRecommendations()
                : $recommendations;
        } catch (\Exception $e) {
            return $this->getDefaultSkillRecommendations();
        }
    }

    private function getDefaultSkillRecommendations(): array
    {
        return [
            'recommendations' => [
                [
                    'skill' => 'Project Management',
                    'importance' => 'High',
                    'reasoning' => 'Essential for career advancement and leadership roles.',
                    'estimated_time' => '3-6 months',
                    'difficulty' => 'Intermediate',
                    'market_demand' => 'High',
                    'salary_impact' => '+15-25%',
                    'related_credentials' => ['Project Management Fundamentals', 'Agile Methodology']
                ],
                [
                    'skill' => 'Data Analysis',
                    'importance' => 'Medium',
                    'reasoning' => 'Increasingly valuable across all industries.',
                    'estimated_time' => '2-4 months',
                    'difficulty' => 'Beginner',
                    'market_demand' => 'Very High',
                    'salary_impact' => '+10-20%',
                    'related_credentials' => ['Data Analysis Basics', 'Excel Advanced']
                ]
            ],
            'priority_skills' => ['Project Management', 'Data Analysis', 'Communication', 'Leadership', 'Problem Solving'],
            'learning_paths' => [
                [
                    'path_name' => 'Leadership Development',
                    'description' => 'Progressive path from team member to leader',
                    'total_duration' => '12 months',
                    'difficulty_progression' => 'Beginner to Advanced'
                ],
                [
                    'path_name' => 'Technical Excellence',
                    'description' => 'Deep dive into technical skills and methodologies',
                    'total_duration' => '18 months',
                    'difficulty_progression' => 'Intermediate to Expert'
                ]
            ]
        ];
    }


}
