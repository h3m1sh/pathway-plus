<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\GeminiAiService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/ai')]
class AiController extends AbstractController
{
    public function __construct(
        private GeminiAiService $aiService
    ) {
    }

    #[Route('/test', name: 'app_ai_test', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function test(): Response
    {
        try {
            $isConnected = $this->aiService->testConnection();
            
            return $this->render('ai/test.html.twig', [
                'connection_status' => $isConnected ? 'Connected' : 'Failed',
                'connection_success' => $isConnected
            ]);
        } catch (\Exception $e) {
            return $this->render('ai/test.html.twig', [
                'connection_status' => 'Error: ' . $e->getMessage(),
                'connection_success' => false
            ]);
        }
    }

    #[Route('/career-suggestions', name: 'app_ai_career_suggestions', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function careerSuggestions(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            
            $userSkills = $data['skills'] ?? [];
            $userInterests = $data['interests'] ?? [];
            $earnedCredentials = $data['credentials'] ?? [];

            $suggestions = $this->aiService->generateCareerSuggestions(
                $userSkills, 
                $userInterests, 
                $earnedCredentials
            );

            return $this->json([
                'success' => true,
                'data' => $suggestions
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    #[Route('/skill-gaps', name: 'app_ai_skill_gaps', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function skillGaps(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            
            $userSkills = $data['user_skills'] ?? [];
            $targetRoleSkills = $data['target_role_skills'] ?? [];

            $gapAnalysis = $this->aiService->analyzeSkillGaps($userSkills, $targetRoleSkills);

            return $this->json([
                'success' => true,
                'data' => $gapAnalysis
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    #[Route('/learning-recommendations', name: 'app_ai_learning_recommendations', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function learningRecommendations(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            
            $userProfile = $data['user_profile'] ?? [];
            $skillGaps = $data['skill_gaps'] ?? [];

            $recommendations = $this->aiService->generateLearningRecommendations($userProfile, $skillGaps);

            return $this->json([
                'success' => true,
                'data' => $recommendations
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    #[Route('/simple-chat', name: 'app_ai_simple_chat', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function simpleChat(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            $message = $data['message'] ?? 'Hello, how can you help me with my career?';

            $response = $this->aiService->generateContent($message);
            $content = $response['candidates'][0]['content']['parts'][0]['text'] ?? 'No response generated';

            return $this->json([
                'success' => true,
                'response' => $content
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
} 