<?php

namespace App\Controller;


use App\Service\SkillPassportService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/dashboard/skill-passport')]
#[IsGranted("ROLE_STUDENT")]
class SkillPassportController extends AbstractController
{
    public function __construct(
        private SkillPassportService $skillPassportService
    )
    {
    }

    #[Route('', name: 'app_skill_passport')]
    public function index(): Response
    {
        $user = $this->getUser();
        $passportData = $this->skillPassportService->getSkillPassportData($user);
        $stats = $this->skillPassportService->getCredentialStats($user);

        return $this->render('dashboard/skill_passport.html.twig', [
            'user' => $user,
            'passportData' => $passportData,
            'stats' => $stats,
        ]);
    }

    #[Route('/filter', name: 'app_skill_passport_filter', methods: ['POST'])]
    public function filter(Request $request): Response
    {
        $user = $this->getUser();
        $filters = $request->request->all();

        $filteredCredentials = $this->skillPassportService->filterCredentials($user, $filters);

        $html = $this->renderView('dashboard/partials/_credential_grid.html.twig', [
            'credentials' => $filteredCredentials,
            'viewType' => $filters['viewType'] ?? 'grid',
        ]);

        return new JsonResponse([
            'success' => true,
            'html' => $html,
            'count' => count($filteredCredentials),
        ]);
    }


    #[Route('/credential/{id}', name: 'app_credential_detial', methods: ['GET'])]
    public function credentialDetail(int $id): JsonResponse
    {
        $user = $this->getUser();
        $progress = $this->skillPassportService->getCredentialDetail($user, $id);

        if (!$progress) {
            return new JsonResponse(['error' => 'Credential not found'], 404);
        }

        $html = $this->renderView('dashboard/partials/_credential_modal.html.twig', [
            'progress' => $progress,
        ]);

        return new JsonResponse([
            'success' => true,
            'html' => $html,
        ]);
    }

    #[Route('/export/{format}', name: 'app_skill_passport_export')]
    public function export(string $format): Response
    {
        $user = $this->getUser();
        $passportData = $this->skillPassportService->getSkillPassportData($user);

        switch ($format) {
            case 'pdf':
                return $this->exportPdf($passportData, $user);
            case 'csv':
                return $this->exportCsv($passportData, $user);
            default:
                throw $this->createNotFoundException('Export format not supported');
        }
    }

    private function exportCsv(array $data, $user): Response
    {
        $filename = sprintf('skill_passport_%s_%s.csv',
            $user->getEmail(),
            date('Y-m-d')
        );

        $response = new Response();
        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '"');

        $output = fopen('php://temp', 'w');

        fputcsv($output, [
            'Credential Name',
            'Category',
            'Level',
            'Status',
            'Date Earned',
            'Verified by',
            'Notes'
        ]);

        foreach ($data['allprogress'] as $progress) {
            fputcsv($output, [
                $progress->getMicroCredential()->getName(),
                $progress->getMicroCredential()->getCategory() ?? 'General',
                $progress->getMicroCredential()->getLevel() ?? 'N/A',
                $progress->getStatus(),
                $progress->getDateEarned()->format('Y-m-d'),
                $progress->getVerifiedBy() ?? 'N/A',
                $progress->getNote() ?? '',
            ]);
        }

        rewind($output);
        $response->setContent(stream_get_contents($output));
        fclose($output);

        return $response;
    }

    private function exportPdf(array $data, $user): Response
    {
        $filename = sprintf('skill_passport_%s_%s.pdf',
            $user->getEmail(),
            date('Y-m-d')
        );

        // Generate HTML content for PDF
        $html = $this->renderView('dashboard/export/skill_passport_pdf.html.twig', [
            'user' => $user,
            'passportData' => $data,
        ]);

        // For now, we'll return HTML that can be printed to PDF
        // In a production environment, you'd use a library like Dompdf, mPDF, or wkhtmltopdf
        $response = new Response($html);
        $response->headers->set('Content-Type', 'text/html');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '"');

        return $response;
    }

}
