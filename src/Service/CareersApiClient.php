<?php

declare(strict_types=1);

namespace App\Service;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class CareersApiClient
{
    private const API_BASE_URL = 'https://api.careers.govt.nz/api/v2';

    public function __construct(
        private HttpClientInterface $httpClient,
        #[Autowire('%env(CAREERS_API_USERNAME)%')]
        private string $apiUsername,
        #[Autowire('%env(CAREERS_API_PASSWORD)%')]
        private string $apiPassword
    ) {}

    /**
     * Fetch all jobs with pagination support
     */
    public function fetchAllJobs(int $offset = 0, int $limit = 100): array
    {
        try {
            $response = $this->httpClient->request('GET', self::API_BASE_URL . '/JobPage', [
                'auth_basic' => [$this->apiUsername, $this->apiPassword],
                'query' => [
                    'offset' => $offset,
                    'limit' => min($limit, 100) // API max is 100
                ],
                'timeout' => 60
            ]);

            return $response->toArray();
        } catch (\Exception $e) {
            throw new \RuntimeException('Failed to fetch jobs from API: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Fetch a single job by its job code
     */
    public function fetchJobByCode(string $jobCode): ?array
    {
        try {
            $response = $this->httpClient->request('GET', self::API_BASE_URL . '/JobPage', [
                'auth_basic' => [$this->apiUsername, $this->apiPassword],
                'query' => ['JobCode' => $jobCode],
                'timeout' => 60
            ]);

            $data = $response->toArray();
            return $data['items'][0] ?? null;
        } catch (\Exception $e) {
            throw new \RuntimeException("Failed to fetch job {$jobCode} from API: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Get total count of jobs available in the API
     */
    public function getJobsCount(): int
    {
        try {
            $response = $this->httpClient->request('GET', self::API_BASE_URL . '/JobPage', [
                'auth_basic' => [$this->apiUsername, $this->apiPassword],
                'query' => ['limit' => 1],
                'timeout' => 60
            ]);

            $data = $response->toArray();
            return $data['total'] ?? 0;
        } catch (\Exception $e) {
            throw new \RuntimeException('Failed to get jobs count from API: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Test API connection - useful for health checks
     */
    public function testConnection(): bool
    {
        try {
            $this->fetchAllJobs(0, 1);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function fetchAllJobsBatched(int $batchSize = 100): \Generator
    {
        $offset = 0;

        do {
            $response = $this->fetchAllJobs($offset, $batchSize);
            $jobs = $response['items'] ?? [];

            if (!empty($jobs)) {
                yield $jobs;
            }

            $offset += $batchSize;
            $total = $response['total'] ?? 0;

        } while ($offset < $total && !empty($jobs));
    }
}
