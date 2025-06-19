<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Entity\JobRole;
use App\Form\ProfileFormType;
use App\Form\PasswordChangeFormType;
use App\Repository\JobRoleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/profile')]
#[IsGranted('IS_AUTHENTICATED_FULLY')]
class ProfileController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher,
        private JobRoleRepository $jobRoleRepository
    ) {
    }

    #[Route('', name: 'app_profile', methods: ['GET', 'POST'])]
    public function index(Request $request): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        
        // Handle AJAX request for career interests
        if ($request->isXmlHttpRequest() && $request->request->has('interests')) {
            return $this->handleCareerInterestsUpdate($request, $user);
        }
        
        // Create profile form
        $profileForm = $this->createForm(ProfileFormType::class, $user);
        $profileForm->handleRequest($request);
        
        // Create password change form
        $passwordForm = $this->createForm(PasswordChangeFormType::class);
        $passwordForm->handleRequest($request);
        
        // Handle profile form submission
        if ($profileForm->isSubmitted() && $profileForm->isValid()) {
            $user->setUpdatedAt(new \DateTimeImmutable());
            $user->setLastProfileUpdate('Profile information updated');
            
            $this->entityManager->flush();
            
            $this->addFlash('success', 'Profile updated successfully!');
            return $this->redirectToRoute('app_profile');
        }
        
        // Handle password change form submission
        if ($passwordForm->isSubmitted() && $passwordForm->isValid()) {
            $currentPassword = $passwordForm->get('currentPassword')->getData();
            $newPassword = $passwordForm->get('newPassword')->getData();
            
            // Verify current password
            if (!$this->passwordHasher->isPasswordValid($user, $currentPassword)) {
                $this->addFlash('error', 'Current password is incorrect.');
                return $this->redirectToRoute('app_profile');
            }
            
            // Hash and set new password
            $hashedPassword = $this->passwordHasher->hashPassword($user, $newPassword);
            $user->setPassword($hashedPassword);
            $user->setUpdatedAt(new \DateTimeImmutable());
            $user->setLastProfileUpdate('Password changed');
            
            $this->entityManager->flush();
            
            $this->addFlash('success', 'Password changed successfully!');
            return $this->redirectToRoute('app_profile');
        }
        
        // Get available job roles for career interests
        $availableJobRoles = $this->jobRoleRepository->findBy(['isArchived' => false], ['title' => 'ASC']);
        
        return $this->render('profile/index.html.twig', [
            'user' => $user,
            'profileForm' => $profileForm,
            'passwordForm' => $passwordForm,
            'availableJobRoles' => $availableJobRoles,
        ]);
    }
    
    private function handleCareerInterestsUpdate(Request $request, User $user): JsonResponse
    {
        try {
            $interestsData = json_decode($request->request->get('interests'), true);
            
            if (!is_array($interestsData)) {
                return new JsonResponse(['success' => false, 'message' => 'Invalid data format']);
            }
            
            // Clear current interests
            $user->getJobRoleInterests()->clear();
            
            // Add new interests
            foreach ($interestsData as $interestId) {
                $jobRole = $this->jobRoleRepository->find($interestId);
                if ($jobRole && !$jobRole->isArchived()) {
                    $user->addJobRoleInterest($jobRole);
                }
            }
            
            $user->setUpdatedAt(new \DateTimeImmutable());
            $user->setLastProfileUpdate('Career interests updated');
            
            $this->entityManager->flush();
            
            return new JsonResponse(['success' => true, 'message' => 'Career interests updated successfully']);
            
        } catch (\Exception $e) {
            return new JsonResponse(['success' => false, 'message' => 'Error updating career interests']);
        }
    }
} 