<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\UserAuthenticatorInterface;

final class RegistrationController extends AbstractController
{
    #[Route('/registration', name: 'app_registration')]
    public function register(
        Request $request,
        UserPasswordHasherInterface $userPasswordHasher,
        UserAuthenticatorInterface $userAuthenticator,
        EntityManagerInterface $entityManager
    ): Response {
        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Get the plain password from the form
            $plainPassword = $form->get('plainPassword')->getData();

            // Hash the password
            $user->setPassword($userPasswordHasher->hashPassword($user, $plainPassword));

            // Set the role
            $role = $form->get('role')->getData();
            $user->setRoles([$role]);

            // Persist the user
            $entityManager->persist($user);
            $entityManager->flush();

            // Add success message and redirect to login
            $this->addFlash('success', 'Registration successful! You can now log in.');
            return $this->redirectToRoute('app_login');
        }

        return $this->render('registration/index.html.twig', [
            'registrationForm' => $form,
        ]);
    }
}
