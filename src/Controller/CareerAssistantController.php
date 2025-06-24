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
use App\Repository\ConversationRepository;
use App\Entity\Conversation;
use Symfony\Component\HttpFoundation\Request;
use App\Service\GeminiAiService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Doctrine\ORM\EntityManagerInterface;

#[Route('/career-assistant')]
#[IsGranted('ROLE_STUDENT')]
class CareerAssistantController extends AbstractController
{
    public function __construct(
        private StudentProgressRepository $studentProgressRepository,
        private SkillRepository $skillRepository,
        private JobRoleRepository $jobRoleRepository,
        private ConversationRepository $conversationRepository,
        private GeminiAiService $aiService,
        private EntityManagerInterface $entityManager
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
        
        // Get recent conversation history
        $recentConversations = $this->conversationRepository->findRecentByUser($user, 10);
        
        // Calculate basic stats
        $stats = [
            'totalCredentials' => count($earnedCredentials),
            'totalSkills' => count($userSkills),
            'careerInterests' => count($careerInterests),
            'recentActivity' => count(array_filter($studentProgress, fn($p) => 
                $p->getDateEarned() > new \DateTimeImmutable('-30 days')
            )),
            'totalConversations' => count($recentConversations)
        ];

        return $this->render('career_assistant/index.html.twig', [
            'user' => $user,
            'studentProgress' => $studentProgress,
            'userSkills' => $userSkills,
            'careerInterests' => $careerInterests,
            'earnedCredentials' => $earnedCredentials,
            'stats' => $stats,
            'recentConversations' => $recentConversations,
        ]);
    }

    #[Route('/chat', name: 'app_career_assistant_chat', methods: ['POST'])]
    public function chat(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            $message = $data['message'] ?? '';
            $usePersonalization = $data['personalized'] ?? false;
            $mode = $data['mode'] ?? 'general';
            $context = $data['context'] ?? [];
            
            if (empty($message)) {
                return $this->json([
                    'success' => false,
                    'error' => 'Message is required'
                ], 400);
            }

            $user = $this->getUser();
            
            // Build context-aware prompt based on mode
            $prompt = $this->buildCareerAssistantPrompt($message, $user, $usePersonalization, $mode, $context);
            
            // Get AI response
            $response = $this->aiService->generateContent($prompt);
            $aiResponse = $response['candidates'][0]['content']['parts'][0]['text'] ?? 'I apologize, but I\'m unable to provide a response at the moment.';

            // Save conversation to database
            $conversation = new Conversation();
            $conversation->setUser($user);
            $conversation->setMode($mode);
            $conversation->setMessage($message);
            $conversation->setResponse($aiResponse);
            $conversation->setPersonalized($usePersonalization);
            
            $this->entityManager->persist($conversation);
            $this->entityManager->flush();

            return $this->json([
                'success' => true,
                'response' => $aiResponse,
                'timestamp' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
                'mode' => $mode,
                'conversationId' => $conversation->getId()
            ]);

        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => 'An error occurred while processing your request.'
            ], 500);
        }
    }

    #[Route('/skill-analysis', name: 'app_career_assistant_skill_analysis', methods: ['POST'])]
    public function skillAnalysis(Request $request): JsonResponse
    {
        try {
            $user = $this->getUser();
            $userSkills = array_map(fn($skill) => $skill->getName(), $user->getSkills()->toArray());
            $careerInterests = array_map(fn($interest) => $interest->getTitle(), $user->getJobRoleInterests()->toArray());
            $earnedCredentials = array_map(fn($credential) => $credential->getName(), $user->getMicroCredentials()->toArray());

            $prompt = $this->buildSkillAnalysisPrompt($userSkills, $careerInterests, $earnedCredentials);
            
            $response = $this->aiService->generateContent($prompt);
            $analysis = $response['candidates'][0]['content']['parts'][0]['text'] ?? 'Unable to generate skill analysis.';

            return $this->json([
                'success' => true,
                'analysis' => $analysis
            ]);

        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => 'Failed to generate skill analysis.'
            ], 500);
        }
    }


    #[Route('/detailed-skill-analysis', name: 'app_career_assistant_detailed_skill_analysis', methods: ['POST'])]
    public function detailedSkillAnalysis(Request $request): JsonResponse
    {
        try {
            $user = $this->getUser();
            $userSkills = array_map(fn($skill) => $skill->getName(), $user->getSkills()->toArray());
            $careerInterests = array_map(fn($interest) => $interest->getTitle(), $user->getJobRoleInterests()->toArray());
            $earnedCredentials = array_map(fn($credential) => $credential->getName(), $user->getMicroCredentials()->toArray());

            $prompt = $this->buildDetailedSkillAnalysisPrompt($userSkills, $careerInterests, $earnedCredentials);
            
            $response = $this->aiService->generateContent($prompt);
            $analysis = $response['candidates'][0]['content']['parts'][0]['text'] ?? 'Unable to generate detailed skill analysis.';

            return $this->json([
                'success' => true,
                'analysis' => $analysis
            ]);

        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => 'Failed to generate detailed skill analysis.'
            ], 500);
        }
    }

    private function buildSkillAnalysisPrompt(array $userSkills, array $careerInterests, array $earnedCredentials): string
    {
        $skillsList = implode(', ', $userSkills);
        $interestsList = implode(', ', $careerInterests);
        $credentialsList = implode(', ', $earnedCredentials);

        return $this->applyGlobalFormattingRules("You are a career development expert analyzing a student's skill profile. Please provide a comprehensive analysis including:

Student Profile:
- Skills: {$skillsList}
- Career Interests: {$interestsList}
- Earned Credentials: {$credentialsList}

        Please provide:
        1. A quick summary of the student's strengths (2-3 points max)
        2. The most obvious skill gaps to work on next (2-3 items max)
        3. 1-2 simple actionable suggestions for immediate improvement

        Be concise — no more than 6-8 sentences total. Present the output as a bullet list or short paragraphs. The goal is to give the student friendly, motivating feedback — not an exhaustive report.

        At the end of your reply, add this line exactly:  
        'Type \"More detail\" to get a full analysis.'");
    }

    private function buildDetailedSkillAnalysisPrompt(array $userSkills, array $careerInterests, array $earnedCredentials): string
    {
        $skillsList = implode(', ', $userSkills);
        $interestsList = implode(', ', $careerInterests);
        $credentialsList = implode(', ', $earnedCredentials);

        return $this->applyGlobalFormattingRules("You are a senior career development expert conducting an in-depth analysis of a student's skill profile. Provide a comprehensive, detailed analysis including:

        Student Profile:
        - Skills: {$skillsList}
        - Career Interests: {$interestsList}
        - Earned Credentials: {$credentialsList}

        Please provide a detailed analysis covering:

        1. Comprehensive Skills Assessment:
        - Technical skills evaluation and proficiency levels
        - Soft skills analysis and development areas
        - Transferable skills identification
        - Industry-specific skill relevance

        2. Detailed Skill Gap Analysis:
        - Critical missing skills for each career interest
        - Skill development timeline recommendations
        - Priority matrix (High/Medium/Low impact)
        - Industry-specific skill requirements

        3. Advanced Development Strategy
        - Personalized learning path recommendations
        - Skill development milestones and timelines
        - Resource recommendations (courses, certifications, projects)
        - Networking and mentorship opportunities

        4. Market Intelligence:
        - Current job market trends for their skills
        - Salary potential analysis
        - Industry growth projections
        - Geographic market considerations

        5. Actionable Implementation Plan:
        - 3-month, 6-month, and 1-year development goals
        - Specific project recommendations
        - Certification and course suggestions
        - Portfolio development strategies

        6. Career Path Optimization:
        - Alternative career paths based on current skills
        - Skill combination strategies
        - Industry transition opportunities
        - Long-term career trajectory planning

        Provide specific, actionable insights with concrete examples and measurable goals. Be encouraging while being realistic about development timelines and requirements.");
    }

    #[Route('/interview-questions', name: 'app_career_assistant_interview_questions', methods: ['POST'])]
    public function interviewQuestions(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            $industry = $data['industry'] ?? 'general';
            $role = $data['role'] ?? 'entry-level';
            $experience = $data['experience'] ?? 'student';

            $user = $this->getUser();
            $userSkills = array_map(fn($skill) => $skill->getName(), $user->getSkills()->toArray());

            $prompt = $this->buildInterviewQuestionsPrompt($industry, $role, $experience, $userSkills);
            
            $response = $this->aiService->generateContent($prompt);
            $questions = $response['candidates'][0]['content']['parts'][0]['text'] ?? 'Unable to generate interview questions.';

            return $this->json([
                'success' => true,
                'questions' => $questions
            ]);

        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => 'Failed to generate interview questions.'
            ], 500);
        }
    }

    #[Route('/career-recommendations', name: 'app_career_assistant_recommendations', methods: ['POST'])]
    public function careerRecommendations(Request $request): JsonResponse
    {
        try {
            $user = $this->getUser();
            $userSkills = array_map(fn($skill) => $skill->getName(), $user->getSkills()->toArray());
            $careerInterests = array_map(fn($interest) => $interest->getTitle(), $user->getJobRoleInterests()->toArray());
            $earnedCredentials = array_map(fn($credential) => $credential->getName(), $user->getMicroCredentials()->toArray());

            $prompt = $this->buildCareerRecommendationsPrompt($userSkills, $careerInterests, $earnedCredentials);
            
            $response = $this->aiService->generateContent($prompt);
            $recommendations = $response['candidates'][0]['content']['parts'][0]['text'] ?? 'Unable to generate career recommendations.';

            return $this->json([
                'success' => true,
                'recommendations' => $recommendations
            ]);

        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => 'Failed to generate career recommendations.'
            ], 500);
        }
    }

    #[Route('/history', name: 'app_career_assistant_history', methods: ['GET'])]
    public function history(Request $request): JsonResponse
    {
        $user = $this->getUser();
        $mode = $request->query->get('mode', 'all');
        $limit = (int) $request->query->get('limit', 20);

        if ($mode === 'all') {
            $conversations = $this->conversationRepository->findRecentByUser($user, $limit);
        } else {
            $conversations = $this->conversationRepository->findConversationsByUserAndMode($user, $mode, $limit);
        }

        $history = array_map(function($conv) {
            return [
                'id' => $conv->getId(),
                'message' => $conv->getMessage(),
                'response' => $conv->getResponse(),
                'mode' => $conv->getMode(),
                'personalized' => $conv->isPersonalized(),
                'timestamp' => $conv->getTimestamp()->format('Y-m-d H:i:s')
            ];
        }, $conversations);

        return $this->json([
            'success' => true,
            'history' => $history
        ]);
    }

    #[Route('/clear-history', name: 'app_career_assistant_clear_history', methods: ['POST'])]
    public function clearHistory(): JsonResponse
    {
        try {
            $user = $this->getUser();
            $deletedCount = $this->conversationRepository->deleteConversationsByUser($user);

            return $this->json([
                'success' => true,
                'message' => "Cleared {$deletedCount} conversations from history."
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => 'Failed to clear conversation history.'
            ], 500);
        }
    }

    private function buildCareerAssistantPrompt(string $message, $user, bool $personalized, string $mode = 'general', array $context = []): string
    {
        $basePrompt = "You are a helpful, supportive career guidance assistant for higher education students. " .
                      "Your goal is to provide practical, actionable advice that helps students progress towards their career goals. " .
                      "Keep your tone positive, encouraging, and approachable.";

        // Add mode-specific instructions
        switch ($mode) {
            case 'interview':
                $basePrompt .= " You are now in INTERVIEW SIMULATOR mode. Simulate a realistic job interview with the student. " .
                               "Ask one interview question at a time. After the student responds, provide brief constructive feedback (no more than 3 sentences) and suggest ways they can improve. " .
                               "Then proceed to the next question. Focus on common interview questions, behavioral questions (STAR method), and industry-specific scenarios. " .
                               "Maintain a professional but supportive tone.";
                
                // Add interview context if available
                if (!empty($context['industry'])) {
                    $basePrompt .= " The student is interviewing for a position in the {$context['industry']} industry.";
                }
                if (!empty($context['role'])) {
                    $basePrompt .= " The target role is: {$context['role']}.";
                }
                break;
            
            case 'discovery':
                $basePrompt .= " You are now in CAREER DISCOVERY mode. Act as a curious and supportive career counselor. " .
                               "Ask the student open-ended questions to help them reflect on their interests, strengths, and goals. " .
                               "Guide them to explore possible career paths and identify skill gaps. " .
                               "In each reply, ask no more than 2-3 open-ended questions to keep the conversation natural and engaging.";
                break;
            
            case 'coach':
                $basePrompt .= " You are now in DEVELOPMENT COACH mode. Act as a personal career coach. " .
                               "Provide clear, actionable advice on professional development tasks such as resume writing, LinkedIn optimization, goal setting, networking strategies, and personal branding. " .
                               "Break advice into small, manageable steps. In each reply, provide no more than 3 practical tips or actions. " .
                               "Be motivational and encouraging.";
                break;
            
            default:
                $basePrompt .= " You are in GENERAL CAREER GUIDANCE mode. Provide practical and encouraging career advice. " .
                               "Answer the student's questions clearly and concisely. Keep responses friendly and supportive. " .
                               "Limit each reply to no more than 3-4 sentences.";
        }

        // Add personalization if available
        if ($personalized) {
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

        // Final message instruction
        $basePrompt .= "\n\nStudent's question: \"{$message}\"\n\n" .
                       "Please provide a helpful, encouraging, and actionable response. " .
                       "Keep your reply concise — no more than 3-4 sentences unless in INTERVIEW mode. " .
                       "Avoid overwhelming the student with too much information at once. If in INTERVIEW mode, remember to ask one question at a time and follow up naturally.";

        return $this->applyGlobalFormattingRules($basePrompt);
    }

    private function buildInterviewQuestionsPrompt(string $industry, string $role, string $experience, array $userSkills): string
    {
        $skillsList = implode(', ', $userSkills);

        return $this->applyGlobalFormattingRules("You are an interview preparation expert. Generate a set of relevant interview questions for:

        Industry: {$industry}
        Role Level: {$role}
        Experience Level: {$experience}
        Student Skills: {$skillsList}

        Please provide:
        1. Technical Questions (if applicable): 2-3 questions.
        2. Behavioral Questions (STAR method): 2-3 questions.
        3. Industry-Specific Questions: 2-3 questions.
        4. General Professional Questions: 2-3 questions.
        5. Questions the student should ask the interviewer: 2-3 suggestions.

        Present your output in a clear, numbered list with section headings. Avoid overwhelming the student — keep the list focused and relevant to their profile.");
    }

    private function buildCareerRecommendationsPrompt(array $userSkills, array $careerInterests, array $earnedCredentials): string
    {
        $skillsList = implode(', ', $userSkills);
        $interestsList = implode(', ', $careerInterests);
        $credentialsList = implode(', ', $earnedCredentials);

        return $this->applyGlobalFormattingRules("You are a career guidance expert. Based on this student's profile, provide clear and actionable career recommendations.

        Student Profile:
        - Skills: {$skillsList}
        - Career Interests: {$interestsList}
        - Earned Credentials: {$credentialsList}

        Please provide a concise, structured response with the following sections:

        1. **Career Path Recommendations**: Up to 5 specific career paths that align with their profile.
        2. **Next Steps (3-6 months)**: Up to 5 immediate actions they can take.
        3. **Skill Development**: 3-5 key skills to acquire or improve.
        4. **Networking Opportunities**: How they can connect with professionals in their field.
        5. **Learning Resources**: 3-5 recommended courses, certifications, or learning paths.
        6. **Job Search Strategy**: 3-5 practical tips to approach their job search effectively.

        Guidelines:
        - Keep the tone friendly, positive, and motivating.
        - Present the information clearly using numbered or bulleted lists.
        - Keep the response concise — no more than 10-12 sentences in total.
        - Do not overwhelm the student with too much information — focus on realistic, actionable next steps.");
    }

    private function applyGlobalFormattingRules(string $prompt): string
    {
        $globalPrefix = "Global Instructions:  
        - You are a helpful, supportive career assistant for students.  
        - Use plain text responses.  
        - Use numbered lists or bullet points only when asked to provide lists.  
        - Do not use bold (**), italics (*) or excessive formatting.  
        - Keep tone friendly and positive.  
        - Keep responses concise — avoid long paragraphs or essays.  
        ";

        $globalSuffix = "End of global instructions.";

        return $globalPrefix . "\n\n" . $prompt . "\n\n" . $globalSuffix;
    }
}