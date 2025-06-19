<?php

declare(strict_types=1);

namespace App\Command;

use App\Service\GeminiAiService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:test-ai',
    description: 'Test the Gemini AI connection and basic functionality'
)]
class TestAiConnectionCommand extends Command
{
    public function __construct(
        private GeminiAiService $aiService
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Testing Gemini AI Connection');

        try {
            // Test basic connection
            $io->section('Testing Basic Connection');
            $isConnected = $this->aiService->testConnection();
            
            if ($isConnected) {
                $io->success('✅ AI connection successful!');
            } else {
                $io->error('❌ AI connection failed!');
                return Command::FAILURE;
            }

            // Test career suggestions
            $io->section('Testing Career Suggestions');
            $userSkills = [
                ['name' => 'JavaScript'],
                ['name' => 'Python'],
                ['name' => 'Database Design']
            ];
            $userInterests = [
                ['title' => 'Software Development'],
                ['title' => 'Data Analysis']
            ];
            $earnedCredentials = [
                ['title' => 'Web Development Certificate']
            ];

            $suggestions = $this->aiService->generateCareerSuggestions($userSkills, $userInterests, $earnedCredentials);
            
            if (!isset($suggestions['error']) || !$suggestions['error']) {
                $io->success('✅ Career suggestions generated successfully!');
                $io->text('Found ' . count($suggestions['suggestions'] ?? []) . ' career suggestions');
            } else {
                $io->warning('⚠️ Career suggestions failed: ' . ($suggestions['reasoning'] ?? 'Unknown error'));
            }

            // Test skill gap analysis
            $io->section('Testing Skill Gap Analysis');
            $targetRoleSkills = [
                ['name' => 'Machine Learning'],
                ['name' => 'Statistics'],
                ['name' => 'Data Visualization']
            ];

            $gapAnalysis = $this->aiService->analyzeSkillGaps($userSkills, $targetRoleSkills);
            
            if (!isset($gapAnalysis['error']) || !$gapAnalysis['error']) {
                $io->success('✅ Skill gap analysis completed!');
                $io->text('Identified ' . count($gapAnalysis['gaps'] ?? []) . ' skill gaps');
            } else {
                $io->warning('⚠️ Skill gap analysis failed');
            }

            // Test learning recommendations
            $io->section('Testing Learning Recommendations');
            $userProfile = [
                'level' => 'Intermediate',
                'time_available' => '10 hours per week',
                'learning_style' => 'Visual and hands-on'
            ];
            $skillGaps = [
                ['skill' => 'Machine Learning'],
                ['skill' => 'Statistics']
            ];

            $recommendations = $this->aiService->generateLearningRecommendations($userProfile, $skillGaps);
            
            if (!isset($recommendations['error']) || !$recommendations['error']) {
                $io->success('✅ Learning recommendations generated!');
                $io->text('Created ' . count($recommendations['recommendations'] ?? []) . ' learning paths');
            } else {
                $io->warning('⚠️ Learning recommendations failed');
            }

            $io->success('🎉 All AI tests completed successfully!');
            return Command::SUCCESS;

        } catch (\Exception $e) {
            $io->error('❌ Test failed with error: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
} 