<?php

declare(strict_types=1);

namespace App\Service;

use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpClient\Exception\TransportException;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class GeminiAiService
{
    private const API_BASE_URL = 'https://generativelanguage.googleapis.com/v1beta';
    private const MODEL_NAME = 'gemini-2.0-flash';
    private const CACHE_TTL = 3600; // 1 hour cache

    public function __construct(
        private HttpClientInterface $httpClient,
        private LoggerInterface $logger,
        private CacheInterface $cache,
        private string $apiKey
    ) {
    }

    /**
     * Generate content using Gemini AI
     */
    public function generateContent(string $prompt, array $options = []): array
    {
        $cacheKey = 'gemini_' . md5($prompt . serialize($options));
        
        return $this->cache->get($cacheKey, function (ItemInterface $item) use ($prompt, $options) {
            $item->expiresAfter(self::CACHE_TTL);
            
            try {
                $response = $this->makeApiRequest($prompt, $options);
                $this->logger->info('Gemini AI request successful', ['prompt_length' => strlen($prompt)]);
                return $response;
            } catch (\Exception $e) {
                $this->logger->error('Gemini AI request failed', [
                    'error' => $e->getMessage(),
                    'prompt_length' => strlen($prompt)
                ]);
                throw $e;
            }
        });
    }

    /**
     * Generate career suggestions based on user skills and interests
     */
    public function generateCareerSuggestions(array $userSkills, array $userInterests, array $earnedCredentials = []): array
    {
        $prompt = $this->buildCareerSuggestionPrompt($userSkills, $userInterests, $earnedCredentials);
        
        try {
            $response = $this->generateContent($prompt);
            return $this->parseCareerSuggestions($response);
        } catch (\Exception $e) {
            $this->logger->error('Failed to generate career suggestions', ['error' => $e->getMessage()]);
            return [
                'suggestions' => [],
                'reasoning' => 'Unable to generate suggestions at this time.',
                'error' => true
            ];
        }
    }

    /**
     * Generate skill gap analysis
     */
    public function analyzeSkillGaps(array $userSkills, array $targetRoleSkills): array
    {
        $prompt = $this->buildSkillGapPrompt($userSkills, $targetRoleSkills);
        
        try {
            $response = $this->generateContent($prompt);
            return $this->parseSkillGapAnalysis($response);
        } catch (\Exception $e) {
            $this->logger->error('Failed to analyze skill gaps', ['error' => $e->getMessage()]);
            return [
                'gaps' => [],
                'priority' => [],
                'recommendations' => [],
                'error' => true
            ];
        }
    }

    /**
     * Generate personalized learning recommendations
     */
    public function generateLearningRecommendations(array $userProfile, array $skillGaps): array
    {
        $prompt = $this->buildLearningRecommendationPrompt($userProfile, $skillGaps);
        
        try {
            $response = $this->generateContent($prompt);
            return $this->parseLearningRecommendations($response);
        } catch (\Exception $e) {
            $this->logger->error('Failed to generate learning recommendations', ['error' => $e->getMessage()]);
            return [
                'recommendations' => [],
                'timeline' => [],
                'resources' => [],
                'error' => true
            ];
        }
    }

    /**
     * Generate personalized skill recommendations
     */
    public function generateSkillRecommendations(array $userSkills, array $userInterests, array $earnedCredentials, array $targetRoles = []): array
    {
        $prompt = $this->buildSkillRecommendationPrompt($userSkills, $userInterests, $earnedCredentials, $targetRoles);
        
        try {
            $response = $this->generateContent($prompt);
            return $this->parseSkillRecommendations($response);
        } catch (\Exception $e) {
            $this->logger->error('Failed to generate skill recommendations', ['error' => $e->getMessage()]);
            return [
                'recommendations' => [],
                'priority_skills' => [],
                'learning_paths' => [],
                'error' => true
            ];
        }
    }

    /**
     * Make the actual API request to Gemini
     */
    private function makeApiRequest(string $prompt, array $options = []): array
    {
        $url = sprintf('%s/models/%s:generateContent?key=%s', 
            self::API_BASE_URL, 
            self::MODEL_NAME, 
            $this->apiKey
        );

        $requestData = [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $prompt]
                    ]
                ]
            ]
        ];

        // Add generation config if provided
        if (!empty($options)) {
            $requestData['generationConfig'] = $options;
        }

        $response = $this->httpClient->request('POST', $url, [
            'headers' => [
                'Content-Type' => 'application/json',
            ],
            'json' => $requestData,
            'timeout' => 30
        ]);

        $data = $response->toArray();

        if (isset($data['error'])) {
            throw new \RuntimeException('Gemini API error: ' . ($data['error']['message'] ?? 'Unknown error'));
        }

        return $data;
    }

    /**
     * Build prompt for career suggestions
     */
    private function buildCareerSuggestionPrompt(array $userSkills, array $userInterests, array $earnedCredentials): string
    {
        $skillsList = implode(', ', array_map(fn($skill) => $skill['name'], $userSkills));
        $interestsList = implode(', ', array_map(fn($interest) => $interest['title'], $userInterests));
        $credentialsList = implode(', ', array_map(fn($cred) => $cred['title'], $earnedCredentials));

        return <<<PROMPT
You are a career guidance AI assistant for higher education students. Based on the following information, provide personalized career suggestions.

Student Skills: {$skillsList}
Career Interests: {$interestsList}
Earned Credentials: {$credentialsList}

IMPORTANT: You must respond with ONLY valid JSON. Do not include any other text, explanations, or formatting outside the JSON structure.

Respond with this exact JSON structure:
{
    "suggestions": [
        {
            "role": "Job Title",
            "match_score": 85,
            "reasoning": "Brief explanation of why this role fits",
            "required_skills": ["skill1", "skill2"],
            "salary_range": "$50k-$80k",
            "growth_potential": "High"
        }
    ],
    "reasoning": "Overall analysis of the student's career potential",
    "next_steps": ["action1", "action2", "action3"]
}

Focus on roles that align with the student's skills and interests, and provide actionable next steps. Ensure the response is valid JSON only.
PROMPT;
    }

    /**
     * Build prompt for skill gap analysis
     */
    private function buildSkillGapPrompt(array $userSkills, array $targetRoleSkills): string
    {
        $userSkillsList = implode(', ', array_map(fn($skill) => $skill['name'], $userSkills));
        $targetSkillsList = implode(', ', array_map(fn($skill) => $skill['name'], $targetRoleSkills));

        return <<<PROMPT
You are analyzing skill gaps for a student interested in a specific career role.

Student's Current Skills: {$userSkillsList}
Required Skills for Target Role: {$targetSkillsList}

IMPORTANT: You must respond with ONLY valid JSON. Do not include any other text, explanations, or formatting outside the JSON structure.

Respond with this exact JSON structure:
{
    "gaps": [
        {
            "skill": "Skill Name",
            "importance": "High",
            "description": "Why this skill is important",
            "learning_time": "2-3 months"
        }
    ],
    "priority": ["skill1", "skill2", "skill3"],
    "recommendations": [
        {
            "skill": "Skill Name",
            "resources": ["resource1", "resource2"],
            "estimated_time": "3 months",
            "difficulty": "Beginner"
        }
    ]
}

Focus on practical, actionable gaps and provide specific learning recommendations. Ensure the response is valid JSON only.
PROMPT;
    }

    /**
     * Build prompt for learning recommendations
     */
    private function buildLearningRecommendationPrompt(array $userProfile, array $skillGaps): string
    {
        $gapsList = implode(', ', array_map(fn($gap) => $gap['skill'], $skillGaps));

        return <<<PROMPT
You are creating a personalized learning plan for a student with the following profile:

Student Level: {$userProfile['level']}
Available Time: {$userProfile['time_available']}
Preferred Learning Style: {$userProfile['learning_style']}
Skill Gaps to Address: {$gapsList}

IMPORTANT: You must respond with ONLY valid JSON. Do not include any other text, explanations, or formatting outside the JSON structure.

Respond with this exact JSON structure:
{
    "recommendations": [
        {
            "skill": "Skill Name",
            "learning_path": [
                {
                    "step": "Step description",
                    "resource": "Resource name/URL",
                    "duration": "2 weeks",
                    "difficulty": "Beginner"
                }
            ],
            "estimated_completion": "3 months"
        }
    ],
    "timeline": {
        "short_term": ["goal1", "goal2"],
        "medium_term": ["goal1", "goal2"],
        "long_term": ["goal1", "goal2"]
    },
    "resources": {
        "free": ["resource1", "resource2"],
        "paid": ["resource1", "resource2"],
        "certifications": ["cert1", "cert2"]
    }
}

Provide a realistic, achievable learning plan that fits the student's profile. Ensure the response is valid JSON only.
PROMPT;
    }

    /**
     * Build prompt for skill recommendations
     */
    private function buildSkillRecommendationPrompt(array $userSkills, array $userInterests, array $earnedCredentials, array $targetRoles): string
    {
        $skillsList = implode(', ', array_map(fn($skill) => $skill['name'], $userSkills));
        $interestsList = implode(', ', array_map(fn($interest) => $interest['title'], $userInterests));
        $credentialsList = implode(', ', array_map(fn($cred) => $cred['title'], $earnedCredentials));
        $targetRolesList = implode(', ', array_map(fn($role) => $role['title'], $targetRoles));

        return <<<PROMPT
You are a skills development AI assistant for higher education students. Based on the following information, provide personalized skill recommendations:

Student's Current Skills: {$skillsList}
Career Interests: {$interestsList}
Earned Credentials: {$credentialsList}
Target Roles: {$targetRolesList}

IMPORTANT: You must respond with ONLY valid JSON. Do not include any other text, explanations, or formatting outside the JSON structure.

Respond with this exact JSON structure:
{
    "recommendations": [
        {
            "skill": "Skill Name",
            "category": "Technical/Business/Soft Skills",
            "importance": "High/Medium/Low",
            "reasoning": "Why this skill is recommended",
            "difficulty": "Beginner/Intermediate/Advanced",
            "estimated_time": "2-3 months",
            "related_credentials": ["credential1", "credential2"],
            "market_demand": "High/Medium/Low",
            "salary_impact": "High/Medium/Low"
        }
    ],
    "priority_skills": ["skill1", "skill2", "skill3"],
    "learning_paths": [
        {
            "path_name": "Path Name",
            "description": "Path description",
            "skills_sequence": ["skill1", "skill2", "skill3"],
            "total_duration": "6 months",
            "difficulty_progression": "Beginner to Advanced"
        }
    ],
    "skill_gaps": [
        {
            "category": "Technical Skills",
            "missing_skills": ["skill1", "skill2"],
            "impact": "High/Medium/Low"
        }
    ]
}

Focus on skills that will have the highest impact on the student's career goals and provide practical learning paths.
PROMPT;
    }

    /**
     * Parse career suggestions from AI response
     */
    private function parseCareerSuggestions(array $response): array
    {
        try {
            $content = $response['candidates'][0]['content']['parts'][0]['text'] ?? '';
            
            // Try to extract JSON from the response
            $jsonData = $this->extractJsonFromResponse($content);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \RuntimeException('Invalid JSON response from AI: ' . json_last_error_msg());
            }
            
            return $jsonData;
        } catch (\Exception $e) {
            $this->logger->error('Failed to parse career suggestions', ['error' => $e->getMessage()]);
            return [
                'suggestions' => [],
                'reasoning' => 'Unable to parse AI response.',
                'error' => true
            ];
        }
    }

    /**
     * Parse skill gap analysis from AI response
     */
    private function parseSkillGapAnalysis(array $response): array
    {
        try {
            $content = $response['candidates'][0]['content']['parts'][0]['text'] ?? '';
            
            // Try to extract JSON from the response
            $jsonData = $this->extractJsonFromResponse($content);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \RuntimeException('Invalid JSON response from AI: ' . json_last_error_msg());
            }
            
            return $jsonData;
        } catch (\Exception $e) {
            $this->logger->error('Failed to parse skill gap analysis', ['error' => $e->getMessage()]);
            return [
                'gaps' => [],
                'priority' => [],
                'recommendations' => [],
                'error' => true
            ];
        }
    }

    /**
     * Parse learning recommendations from AI response
     */
    private function parseLearningRecommendations(array $response): array
    {
        try {
            $content = $response['candidates'][0]['content']['parts'][0]['text'] ?? '';
            
            // Try to extract JSON from the response
            $jsonData = $this->extractJsonFromResponse($content);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \RuntimeException('Invalid JSON response from AI: ' . json_last_error_msg());
            }
            
            return $jsonData;
        } catch (\Exception $e) {
            $this->logger->error('Failed to parse learning recommendations', ['error' => $e->getMessage()]);
            return [
                'recommendations' => [],
                'timeline' => [],
                'resources' => [],
                'error' => true
            ];
        }
    }

    /**
     * Parse skill recommendations from AI response
     */
    private function parseSkillRecommendations(array $response): array
    {
        try {
            $content = $response['candidates'][0]['content']['parts'][0]['text'] ?? '';
            
            // Try to extract JSON from the response
            $jsonData = $this->extractJsonFromResponse($content);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \RuntimeException('Invalid JSON response from AI: ' . json_last_error_msg());
            }
            
            return $jsonData;
        } catch (\Exception $e) {
            $this->logger->error('Failed to parse skill recommendations', ['error' => $e->getMessage()]);
            return [
                'recommendations' => [],
                'priority_skills' => [],
                'learning_paths' => [],
                'error' => true
            ];
        }
    }

    /**
     * Extract JSON from AI response, handling potential formatting issues
     */
    private function extractJsonFromResponse(string $content): array
    {
        // First, try to parse the content as-is
        $data = json_decode($content, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            return $data;
        }

        // If that fails, try to extract JSON from the content
        if (preg_match('/\{.*\}/s', $content, $matches)) {
            $jsonString = $matches[0];
            $data = json_decode($jsonString, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $data;
            }
        }

        // If still no success, try to clean up the content
        $cleanedContent = $this->cleanJsonResponse($content);
        $data = json_decode($cleanedContent, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            return $data;
        }

        throw new \RuntimeException('Could not extract valid JSON from AI response');
    }

    /**
     * Clean up AI response to extract valid JSON
     */
    private function cleanJsonResponse(string $content): string
    {
        // Remove markdown code blocks
        $content = preg_replace('/```json\s*/', '', $content);
        $content = preg_replace('/```\s*$/', '', $content);
        
        // Remove any text before the first {
        $startPos = strpos($content, '{');
        if ($startPos !== false) {
            $content = substr($content, $startPos);
        }
        
        // Remove any text after the last }
        $endPos = strrpos($content, '}');
        if ($endPos !== false) {
            $content = substr($content, 0, $endPos + 1);
        }
        
        return trim($content);
    }

    /**
     * Test the AI connection
     */
    public function testConnection(): bool
    {
        try {
            $response = $this->generateContent('Hello, please respond with "AI is working"');
            $content = $response['candidates'][0]['content']['parts'][0]['text'] ?? '';
            return str_contains(strtolower($content), 'ai is working');
        } catch (\Exception $e) {
            $this->logger->error('AI connection test failed', ['error' => $e->getMessage()]);
            return false;
        }
    }
} 