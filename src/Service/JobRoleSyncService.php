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

        $industry = $this->extractIndustryFromData($data);
        $jobRole->setIndustry($industry);

        $salaryRange = $this->extractSalaryRange($data['PayDetails'] ?? '');
        $jobRole->setSalaryRange($salaryRange);

        $jobRole->setSource('careers.govt.nz');
        $jobRole->setEntryRequirements($data['EntryRequirements'] ?? '');


    }

    /**
     * Sync skills for a job role
     */
    private function syncJobSkills(JobRole $jobRole, array $jobData): void
    {

        // TODO: Implement skill extraction in future version
//        $skillsText = $jobData['SkillsAndKnowledge'] ?? '';
//
//        if (empty($skillsText)) {
//            return;
//        }
//
//        $extractedSkills = $this->extractSkillsFromText($skillsText);
//
//        foreach ($extractedSkills as $skillName) {
//            $skill = $this->findOrCreateSkill($skillName);
//
//            if (!$jobRole->getRequiredSkills()->contains($skill)) {
//                $jobRole->addRequiredSkill($skill);
//            }
//        }
    }

    /**
     * Extract skills from text (basic implementation)
     */
    private function extractSkillsFromText(string $text): array
    {
        $skills = [];

        // Look for common skill patterns
        $patterns = [
            '/knowledge of ([^,\n\.]+)/i',
            '/skills in ([^,\n\.]+)/i',
            '/experience with ([^,\n\.]+)/i',
            '/ability to ([^,\n\.]+)/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match_all($pattern, $text, $matches)) {
                foreach ($matches[1] as $match) {
                    $skill = trim($match);
                    if (strlen($skill) > 3 && strlen($skill) < 100) {
                        $skills[] = $skill;
                    }
                }
            }
        }

        return array_unique($skills);
    }

    /**
     * Find existing skill or create new one
     */
    private function findOrCreateSkill(string $skillName): Skill
    {
        $skill = $this->skillRepository->findOneBy(['name' => $skillName]);

        if (!$skill) {
            $skill = new Skill();
            $skill->setName($skillName);
            $skill->setCategory('General'); // Default
            $skill->setDifficulty('Intermediate'); // Default

            $this->entityManager->persist($skill);
        }

        return $skill;
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
     * Extract industry from job data
     */
    private function extractIndustryFromData(array $data): string
    {

        return 'General'; // Default for now
    }

    /**
     * Extract salary range from pay details text
     */
    private function extractSalaryRange(string $payDetails): ?string
    {
        if (empty($payDetails)) {
            return null;
        }

        // Look for salary patterns like "$60,000 and $70,000"
        if (preg_match('/\$[\d,]+\s+(?:and|to)\s+\$[\d,]+/i', $payDetails, $matches)) {
            return $matches[0];
        }

        // Look for single salary mentions
        if (preg_match('/\$[\d,]+/i', $payDetails, $matches)) {
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
