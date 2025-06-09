<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Service\CareersApiClient;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/api-test')]
class ApiController extends AbstractController
{
    #[Route('/careers', name: 'admin_test_careers_api')]
    public function testCareersApi(CareersApiClient $apiClient): JsonResponse
    {
        try {
            $isConnected = $apiClient->testConnection();
            $jobsCount = $isConnected ? $apiClient->getJobsCount() : 0;
            $sampleJob = $isConnected ? $apiClient->fetchJobByCode('J80312') : null;

            return $this->json([
                'api_connected' => $isConnected,
                'total_jobs_available' => $jobsCount,
                'sample_job_title' => $sampleJob['Title'] ?? 'N/A',
                'sample_job_anzsco' => $sampleJob['ANZSCO'] ?? 'N/A',
                'message' => $isConnected ? 'API connection successful' : 'API connection failed'
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
