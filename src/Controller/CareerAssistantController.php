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

#[Route('/career-assistant')]
#[IsGranted('ROLE_STUDENT')]
class CareerAssistantController extends AbstractController
{
    public function __construct(
        private StudentProgressRepository $studentProgressRepository,
        private SkillRepository $skillRepository,
        private JobRoleRepository $jobRoleRepository
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
}