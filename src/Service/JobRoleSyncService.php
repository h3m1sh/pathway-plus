<?php

namespace App\Service;

use App\Entity\JobRole;
use App\Entity\Skill;
use App\Repository\JobRoleRepository;
use App\Repository\SkillRepository;
use App\DTO\SyncResult;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class JobRoleSyncService
{
    public function __construct(
        private CareersApiClient $apiClient,
        private EntityManagerInterface $entityManager,
        private JobRoleRepository $jobRoleRepository,
        private SkillRepository $skillRepository,
        private LoggerInterface $logger
    ) {}

    /**
     * Sync all job roles from the API
     */
    public function syncAllJobs(): SyncResult
    {
        $result = new SyncResult();
        $this->logger->info('Starting full job sync');

        try {
            $processedJobCodes = [];

            // Process jobs in batches to avoid memory issues
            foreach ($this->apiClient->fetchAllJobsBatched() as $jobBatch) {
                foreach ($jobBatch as $jobData) {
                    $jobCode = $jobData['JobCode'] ?? null;

                    if (!$jobCode) {
                        $result->incrementFailed('unknown', 'Missing job code');
                        continue;
                    }

                    $processedJobCodes[] = $jobCode;

                    try {
                        if ($this->syncSingleJobFromData($jobData)) {
                            $existingJob = $this->jobRoleRepository->findOneBy(['jobCode' => $jobCode]);
                            if ($existingJob && $existingJob->getLastSyncedAt()) {
                                $result->incrementUpdated();
                            } else {
                                $result->incrementCreated();
                            }
                        } else {
                            $result->incrementSkipped();
                        }
                    } catch (\Exception $e) {
                        $result->incrementFailed($jobCode, $e->getMessage());
                        $this->logger->error('Failed to sync job', [
                            'jobCode' => $jobCode,
                            'error' => $e->getMessage()
                        ]);
                    }
                }

                $this->entityManager->flush();
                $this->entityManager->clear();
            }

            $archivedCount = $this->archiveMissingJobs($processedJobCodes);
            $result->incrementArchived($archivedCount);

            $result->finish();
            $this->logger->info('Job sync completed', $result->getSummary());

        } catch (\Exception $e) {
            $result->finish();
            $this->logger->error('Job sync failed', [
                'error' => $e->getMessage(),
                'summary' => $result->getSummary()
            ]);
            throw $e;
        }

        return $result;
    }

    /**
     * Sync a single job by job code
     */
    public function syncSingleJob(string $jobCode): bool
    {
        try {
            $jobData = $this->apiClient->fetchJobByCode($jobCode);

            if (!$jobData) {
                $this->logger->warning('Job not found in API', ['jobCode' => $jobCode]);
                return false;
            }

            return $this->syncSingleJobFromData($jobData);

        } catch (\Exception $e) {
            $this->logger->error('Failed to sync single job', [
                'jobCode' => $jobCode,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Sync job from API data array
     */
    private function syncSingleJobFromData(array $jobData): bool
    {
        $jobCode = $jobData['JobCode'] ?? null;

        if (!$jobCode) {
            throw new \InvalidArgumentException('Job data missing JobCode');
        }

        $jobRole = $this->jobRoleRepository->findOneBy(['jobCode' => $jobCode]);
        $isNew = false;

        if (!$jobRole) {
            $jobRole = new JobRole();
            $isNew = true;
        }

        if (!$isNew && $jobRole->isManuallyEdited()) {
            $this->logger->info('Skipping manually edited job', ['jobCode' => $jobCode]);
            return false;
        }

        $this->updateJobRoleFromApiData($jobRole, $jobData);

        $this->syncJobSkills($jobRole, $jobData);

        if ($isNew) {
            $this->entityManager->persist($jobRole);
        }

        $jobRole->setLastSyncedAt(new \DateTimeImmutable());
        $jobRole->setSyncStatus('synced');
        $jobRole->setSyncError(null);

        // Flush changes to database
        $this->entityManager->flush();

        return true;
    }

    /**
     * Update JobRole entity from API data
     */
    private function updateJobRoleFromApiData(JobRole $jobRole, array $data): void
    {
        // Map API fields to entity properties
        $jobRole->setJobCode($data['JobCode'] ?? '');
        $jobRole->setTitle($data['Title'] ?? '');
        $jobRole->setDescription($data['Description'] ?? '');
        $jobRole->setAnzsco($data['ANZSCO'] ?? null);

        // Map industry from API data
        $jobRole->setIndustry($data['Industry'] ?? 'General');

        // Extract and set salary range from PayDetails or PayMetadata
        $salaryRange = $this->extractSalaryRange($data);
        $jobRole->setSalaryRange($salaryRange);

        // Map additional fields
        $jobRole->setEntryRequirements($data['EntryRequirements'] ?? null);
        $jobRole->setJobOpportunities($data['JobOpportunities'] ?? null);
        $jobRole->setYearsOfTraining($data['YearsOfTraining'] ?? null);
        $jobRole->setJobOpportunitiesCaption($data['JobOpportunitiesCaption'] ?? null);

        $jobRole->setSource('careers.govt.nz');
    }

    /**
     * Sync skills for a job role
     */
    private function syncJobSkills(JobRole $jobRole, array $jobData): void
    {
        // Extract skills from multiple text fields
        $skillsText = $this->gatherSkillsText($jobData);
        
        if (empty($skillsText)) {
            return;
        }

        $extractedSkills = $this->extractSkillsFromText($skillsText);

        foreach ($extractedSkills as $skillName) {
            $skill = $this->findOrCreateSkill($skillName);

            if (!$jobRole->getSkills()->contains($skill)) {
                $jobRole->addSkill($skill);
            }
        }
    }

    /**
     * Gather skills text from various API fields
     */
    private function gatherSkillsText(array $jobData): string
    {
        $textSources = [
            $jobData['SkillsAndKnowledge'] ?? '',
            $jobData['TasksAndDuties'] ?? '',
            $jobData['EntryRequirements'] ?? '',
            $jobData['Description'] ?? ''
        ];

        $combinedText = implode(' ', array_filter($textSources));
        
        // Clean HTML tags and decode entities
        $cleanText = strip_tags($combinedText);
        $cleanText = html_entity_decode($cleanText, ENT_QUOTES, 'UTF-8');
        
        return $cleanText;
    }

    /**
     * Extract skills from text using pattern matching and keyword detection
     */
    private function extractSkillsFromText(string $text): array
    {
        $skills = [];

        // Common technical skills and tools (case-insensitive matching)
        $technicalSkills = [
            'microsoft office', 'excel', 'word', 'powerpoint', 'outlook',
            'project management', 'leadership', 'communication', 'teamwork',
            'problem solving', 'analytical thinking', 'customer service',
            'time management', 'planning', 'budgeting', 'supervision',
            'training', 'coaching', 'mentoring', 'negotiation', 'presentation',
            'research', 'analysis', 'reporting', 'documentation', 'compliance',
            'quality assurance', 'safety', 'risk management', 'data analysis',
            'financial analysis', 'accounting', 'bookkeeping', 'procurement',
            'inventory management', 'logistics', 'supply chain', 'marketing',
            'sales', 'networking', 'relationship building', 'stakeholder management'
        ];

        // Look for direct mentions of technical skills
        foreach ($technicalSkills as $skill) {
            if (stripos($text, $skill) !== false) {
                $skills[] = ucwords($skill);
            }
        }

        // Look for common skill patterns in text
        $patterns = [
            '/(?:knowledge|understanding|expertise) (?:of|in) ([^,\n\.;]+)/i',
            '/(?:skills|experience|proficiency) (?:in|with|using) ([^,\n\.;]+)/i',
            '/(?:ability|capacity) to ([^,\n\.;]+)/i',
            '/(?:competent|proficient|skilled) (?:in|with|at) ([^,\n\.;]+)/i',
            '/(?:familiar|familiarization) with ([^,\n\.;]+)/i',
            '/(?:training|qualification|certification) in ([^,\n\.;]+)/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match_all($pattern, $text, $matches)) {
                foreach ($matches[1] as $match) {
                    $skill = $this->cleanSkillText($match);
                    if ($this->isValidSkill($skill)) {
                        $skills[] = $skill;
                    }
                }
            }
        }

        // Remove duplicates and return
        return array_values(array_unique(array_filter($skills)));
    }

    /**
     * Clean and normalize skill text
     */
    private function cleanSkillText(string $text): string
    {
        // Clean HTML and decode entities first
        $cleanText = strip_tags($text);
        $cleanText = html_entity_decode($cleanText, ENT_QUOTES, 'UTF-8');
        
        // Remove common prefixes and suffixes
        $cleanText = preg_replace('/^(the|a|an)\s+/i', '', trim($cleanText));
        $cleanText = preg_replace('/\s+(is|are|and|or|required|needed|essential|preferred)$/i', '', $cleanText);
        
        // Remove extra whitespace and normalize
        $cleanText = preg_replace('/\s+/', ' ', $cleanText);
        
        // Remove any remaining HTML artifacts like "&lt;" etc
        $cleanText = preg_replace('/&[a-zA-Z0-9#]+;/', '', $cleanText);
        
        return ucwords(strtolower(trim($cleanText)));
    }

    /**
     * Validate if extracted text is a valid skill
     */
    private function isValidSkill(string $skill): bool
    {
        $length = strlen($skill);
        
        // Check length bounds
        if ($length < 3 || $length > 80) {
            return false;
        }
        
        // Skip common non-skill words
        $skipWords = [
            'work', 'working', 'workers', 'people', 'person', 'individuals',
            'staff', 'team', 'teams', 'others', 'clients', 'customers',
            'time', 'times', 'hours', 'days', 'weeks', 'months', 'years',
            'tasks', 'duties', 'responsibilities', 'requirements', 'qualifications',
            'degree', 'diploma', 'certificate', 'certification', 'training',
            'environment', 'environments', 'conditions', 'situations'
        ];
        
        $lowerSkill = strtolower($skill);
        foreach ($skipWords as $skipWord) {
            if ($lowerSkill === $skipWord || strpos($lowerSkill, $skipWord) === 0) {
                return false;
            }
        }
        
        // Must contain at least one letter
        return preg_match('/[a-zA-Z]/', $skill);
    }

    /**
     * Find existing skill or create new one with appropriate categorization
     */
    private function findOrCreateSkill(string $skillName): Skill
    {
        // Try exact match first
        $skill = $this->skillRepository->findOneBy(['name' => $skillName]);

        if (!$skill) {
            // Try case-insensitive match
            $existingSkills = $this->skillRepository->findAll();
            foreach ($existingSkills as $existingSkill) {
                if (strtolower($existingSkill->getName()) === strtolower($skillName)) {
                    return $existingSkill;
                }
            }

            // Create new skill with appropriate category
            $skill = new Skill();
            $skill->setName($skillName);
            $skill->setCategory($this->categorizeSkill($skillName));
            $skill->setDifficulty($this->assessSkillDifficulty($skillName));
            $skill->setUpdatedAt(new \DateTimeImmutable());

            $this->entityManager->persist($skill);
        }

        return $skill;
    }

    /**
     * Categorize a skill based on its name
     */
    private function categorizeSkill(string $skillName): string
    {
        $lowerSkill = strtolower($skillName);

        $categories = [
            'Technical' => ['microsoft', 'excel', 'word', 'powerpoint', 'software', 'computer', 'data analysis', 'programming', 'coding'],
            'Management' => ['management', 'leadership', 'supervision', 'planning', 'budgeting', 'project', 'team', 'strategic'],
            'Communication' => ['communication', 'presentation', 'writing', 'speaking', 'negotiation', 'interpersonal', 'networking'],
            'Analytical' => ['analysis', 'analytical', 'research', 'problem solving', 'critical thinking', 'evaluation'],
            'Customer Service' => ['customer', 'client', 'service', 'relationship', 'stakeholder'],
            'Financial' => ['financial', 'accounting', 'bookkeeping', 'finance', 'audit', 'budget'],
            'Operations' => ['operations', 'logistics', 'supply chain', 'inventory', 'procurement', 'quality']
        ];

        foreach ($categories as $category => $keywords) {
            foreach ($keywords as $keyword) {
                if (stripos($lowerSkill, $keyword) !== false) {
                    return $category;
                }
            }
        }

        return 'General';
    }

    /**
     * Assess skill difficulty based on its nature
     */
    private function assessSkillDifficulty(string $skillName): string
    {
        $lowerSkill = strtolower($skillName);

        $advancedKeywords = ['management', 'leadership', 'strategic', 'advanced', 'expert', 'specialized'];
        $beginnerKeywords = ['basic', 'introduction', 'fundamental', 'entry level'];

        foreach ($advancedKeywords as $keyword) {
            if (stripos($lowerSkill, $keyword) !== false) {
                return 'Advanced';
            }
        }

        foreach ($beginnerKeywords as $keyword) {
            if (stripos($lowerSkill, $keyword) !== false) {
                return 'Beginner';
            }
        }

        return 'Intermediate';
    }

    /**
     * Archive jobs that are no longer in the API
     */
    private function archiveMissingJobs(array $currentJobCodes): int
    {
        $existingJobs = $this->jobRoleRepository->findBy([
            'source' => 'careers.govt.nz',
            'isArchived' => false
        ]);

        $archivedCount = 0;

        foreach ($existingJobs as $job) {
            if (!in_array($job->getJobCode(), $currentJobCodes)) {
                $job->setIsArchived(true);
                $job->setLastSyncedAt(new \DateTime());
                $archivedCount++;
            }
        }

        if ($archivedCount > 0) {
            $this->entityManager->flush();
            $this->logger->info('Archived missing jobs', ['count' => $archivedCount]);
        }

        return $archivedCount;
    }

    /**
     * Extract salary range from job data
     */
    private function extractSalaryRange(array $data): ?string
    {
        // First try PayMetadata (structured data)
        if (!empty($data['PayMetadata']) && is_array($data['PayMetadata'])) {
            $payMeta = $data['PayMetadata'][0] ?? null;
            if ($payMeta && isset($payMeta['MinimumPay'], $payMeta['MaximumPay'])) {
                $min = (int)$payMeta['MinimumPay'];
                $max = (int)$payMeta['MaximumPay'];
                
                // Format nicely
                if ($min > 0 && $max > 0 && $min !== $max) {
                    return '$' . number_format($min) . ' - $' . number_format($max);
                } elseif ($min > 0) {
                    return '$' . number_format($min) . '+';
                }
            }
        }

        // Fallback to PayDetails text parsing
        $payDetails = $data['PayDetails'] ?? '';
        if (empty($payDetails)) {
            return null;
        }

        // Look for patterns like "$55,000 to $100,000" or "$55K-$100K"
        if (preg_match('/\$[\d,]+(?:K|,\d{3})?\s*(?:to|-)\s*\$[\d,]+(?:K|,\d{3})?/i', $payDetails, $matches)) {
            return $matches[0];
        }

        // Look for patterns like "$55,000 and $100,000"
        if (preg_match('/\$[\d,]+\s+and\s+\$[\d,]+/i', $payDetails, $matches)) {
            return $matches[0];
        }

        // Look for single salary mentions like "$60,000"
        if (preg_match('/\$[\d,]+(?:K|,\d{3})?/i', $payDetails, $matches)) {
            return $matches[0];
        }

        return null;
    }

    /**
     * Get last sync status
     */
    public function getLastSyncStatus(): array
    {
        $totalJobs = $this->jobRoleRepository->count([]);
        $syncedJobs = $this->jobRoleRepository->count(['source' => 'careers.govt.nz']);
        $failedJobs = $this->jobRoleRepository->count([
            'source' => 'careers.govt.nz',
            'syncStatus' => 'failed'
        ]);

        // Get last sync time
        $lastSyncJob = $this->jobRoleRepository->findOneBy(
            ['source' => 'careers.govt.nz'],
            ['lastSyncedAt' => 'DESC']
        );

        return [
            'total_jobs' => $totalJobs,
            'synced_jobs' => $syncedJobs,
            'failed_jobs' => $failedJobs,
            'last_sync_at' => $lastSyncJob?->getLastSyncedAt(),
            'api_connection' => $this->apiClient->testConnection()
        ];
    }
}
