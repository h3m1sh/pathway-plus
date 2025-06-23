<?php 

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Repository\StudentProgressRepository;
use App\Repository\SkillRepository;
use App\Repository\JobRoleRepository;
use Symfony\Component\HttpFoundation\Request;
use App\Service\GeminiAiService;
use Symfony\Component\HttpFoundation\JsonResponse;

#[Route('/career-assistant')]
#[IsGranted('ROLE_STUDENT')]
class CareerAssistantController extends AbstractController
{
    public function __construct(
        private StudentProgressRepository $studentProgressRepository,
        private SkillRepository $skillRepository,
        private JobRoleRepository $jobRoleRepository,
        private GeminiAiService $aiService
    ) {
    }

    #[Route('', name: 'app_career_assistant', methods: ['GET'])]
    public function index(): Response
    {
        $user = $this->getUser();
        
        // Get student data for personalization
        $studentProgress = $this->studentProgressRepository->findBy(['student' => $user], ['dateEarned' => 'DESC']);
        $userSkills = $user->getSkills()->toArray();
        $careerInterests = $user->getJobRoleInterests()->toArray();
        $earnedCredentials = $user->getMicroCredentials()->toArray();
        
        // Calculate basic stats
        $stats = [
            'totalCredentials' => count($earnedCredentials),
            'totalSkills' => count($userSkills),
            'careerInterests' => count($careerInterests),
            'recentActivity' => count(array_filter($studentProgress, fn($p) => 
                $p->getDateEarned() > new \DateTimeImmutable('-30 days')
            ))
        ];

        return $this->render('career_assistant/index.html.twig', [
            'user' => $user,
            'studentProgress' => $studentProgress,
            'userSkills' => $userSkills,
            'careerInterests' => $careerInterests,
            'earnedCredentials' => $earnedCredentials,
            'stats' => $stats,
        ]);
    }

    #[Route('/chat', name: 'app_career_assistant_chat', methods: ['POST'])]
    public function chat(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            $message = $data['message'] ?? '';
            $usePersonalization = $data['personalized'] ?? false;
            
            if (empty($message)) {
                return $this->json([
                    'success' => false,
                    'error' => 'Message is required'
                ], 400);
            }

            $user = $this->getUser();
            
            // Build context-aware prompt
            $prompt = $this->buildCareerAssistantPrompt($message, $user, $usePersonalization);
            
            // Get AI response
            $response = $this->aiService->generateContent($prompt);
            $aiResponse = $response['candidates'][0]['content']['parts'][0]['text'] ?? 'I apologize, but I\'m unable to provide a response at the moment.';

            return $this->json([
                'success' => true,
                'response' => $aiResponse,
                'timestamp' => (new \DateTimeImmutable())->format('Y-m-d H:i:s')
            ]);

        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => 'An error occurred while processing your request.'
            ], 500);
        }
    }

    private function buildCareerAssistantPrompt(string $message, $user, bool $personalized): string
    {
        $basePrompt = "You are a helpful, supportive career guidance assistant for higher education students. " .
                    "Your goal is to provide practical, actionable advice that helps students progress towards their career goals. " .
                    "Keep your tone positive, encouraging, and approachable.";

        if ($personalized) {
            // Get student data for personalization
            $userSkills = array_map(fn($skill) => $skill->getName(), $user->getSkills()->toArray());
            $careerInterests = array_map(fn($interest) => $interest->getTitle(), $user->getJobRoleInterests()->toArray());
            $earnedCredentials = array_map(fn($credential) => $credential->getName(), $user->getMicroCredentials()->toArray());

            $skillsList = implode(', ', $userSkills);
            $interestsList = implode(', ', $careerInterests);
            $credentialsList = implode(', ', $earnedCredentials);

            $basePrompt .= "\n\nHere is some information about the student you are helping:\n" .
                        "Name: {$user->getFullName()}\n" .
                        "Skills: {$skillsList}\n" .
                        "Career Interests: {$interestsList}\n" .
                        "Earned Credentials: {$credentialsList}\n\n" .
                        "Please tailor your advice based on this student's profile.";
        } else {
            $basePrompt .= "\n\nYou do not have personal information about the student. Please provide general career guidance that would be useful to any student.";
        }

        $basePrompt .= "\n\nStudent's question: \"{$message}\"\n\n" .
    "Please provide a helpful, encouraging, and actionable response. " .
    "Keep your reply short — limit your response to no more than 3 sentences, or 1 key actionable tip. " .
    "Do not overwhelm the student with too many steps or too much information. " .
    "Think of this as giving the student one simple, motivating next step.";

        return $basePrompt;
    }
}