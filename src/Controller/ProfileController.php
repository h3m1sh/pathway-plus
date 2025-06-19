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
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/profile')]
#[IsGranted('IS_AUTHENTICATED_FULLY')]
class ProfileController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher,
        private JobRoleRepository $jobRoleRepository,
        private SluggerInterface $slugger
    ) {
    }

    #[Route('', name: 'app_profile', methods: ['GET', 'POST'])]
    public function index(Request $request): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        
        if ($request->isXmlHttpRequest() && $request->request->has('interests')) {
            return $this->handleCareerInterestsUpdate($request, $user);
        }
        
        $profileForm = $this->createForm(ProfileFormType::class, $user, [
            'is_admin' => $user->isAdmin()
        ]);
        $profileForm->handleRequest($request);
        
        $avatarForm = $this->createForm(ProfileFormType::class, $user, [
            'is_admin' => false,
            'avatar_only' => true
        ]);
        $avatarForm->handleRequest($request);
        
        $passwordForm = $this->createForm(PasswordChangeFormType::class);
        $passwordForm->handleRequest($request);
        
        if ($profileForm->isSubmitted() && $profileForm->isValid()) {
            $this->handleProfileUpdate($profileForm, $user);
            return $this->redirectToRoute('app_profile');
        }
        
        if ($avatarForm->isSubmitted() && $avatarForm->isValid()) {
            $this->handleAvatarUpdate($avatarForm, $user);
            return $this->redirectToRoute('app_profile');
        }
        
        if ($passwordForm->isSubmitted() && $passwordForm->isValid()) {
            $this->handlePasswordChange($passwordForm, $user);
            return $this->redirectToRoute('app_profile');
        }
        
        return $this->render('profile/index.html.twig', [
            'user' => $user,
            'profileForm' => $profileForm,
            'avatarForm' => $avatarForm,
            'passwordForm' => $passwordForm,
        ]);
    }
    
    private function handleCareerInterestsUpdate(Request $request, User $user): JsonResponse
    {
        try {
            $interestsData = json_decode($request->request->get('interests'), true);
            
            if (!is_array($interestsData)) {
                return new JsonResponse(['success' => false, 'message' => 'Invalid data format']);
            }
            
            $user->getJobRoleInterests()->clear();
            
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

    private function handleProfileUpdate($profileForm, User $user): void
    {
        $avatarFile = $profileForm->get('avatarFile')->getData();
        if ($avatarFile) {
            $this->uploadAvatar($avatarFile, $user);
        }
        
        $user->setUpdatedAt(new \DateTimeImmutable());
        $user->setLastProfileUpdate('Profile information updated');
        
        $this->entityManager->flush();
        $this->addFlash('success', 'Profile updated successfully!');
    }

    private function handleAvatarUpdate($avatarForm, User $user): void
    {
        $avatarFile = $avatarForm->get('avatarFile')->getData();
        if ($avatarFile) {
            $this->uploadAvatar($avatarFile, $user);
            $user->setUpdatedAt(new \DateTimeImmutable());
            $user->setLastProfileUpdate('Profile picture updated');
            
            $this->entityManager->flush();
            $this->addFlash('success', 'Profile picture updated successfully!');
        }
    }

    private function handlePasswordChange($passwordForm, User $user): void
    {
        $currentPassword = $passwordForm->get('currentPassword')->getData();
        $newPassword = $passwordForm->get('newPassword')->getData();
        
        if (!$this->passwordHasher->isPasswordValid($user, $currentPassword)) {
            $this->addFlash('error', 'Current password is incorrect.');
            return;
        }
        
        $hashedPassword = $this->passwordHasher->hashPassword($user, $newPassword);
        $user->setPassword($hashedPassword);
        $user->setUpdatedAt(new \DateTimeImmutable());
        $user->setLastProfileUpdate('Password changed');
        
        $this->entityManager->flush();
        $this->addFlash('success', 'Password changed successfully!');
    }

    private function uploadAvatar($avatarFile, User $user): void
    {
        $originalFilename = pathinfo($avatarFile->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename = $this->slugger->slug($originalFilename);
        $newFilename = $safeFilename.'-'.uniqid().'.'.$avatarFile->guessExtension();
        
        try {
            $avatarFile->move(
                $this->getParameter('avatars_directory'),
                $newFilename
            );
            
            $user->setAvatarUrl('/uploads/avatars/' . $newFilename);
        } catch (\Exception $e) {
            $this->addFlash('error', 'Failed to upload profile picture.');
        }
    }
} 