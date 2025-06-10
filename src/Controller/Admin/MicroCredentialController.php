<?php

namespace App\Controller\Admin;

use App\Entity\MicroCredential;
use App\Form\MicroCredentialForm;
use App\Repository\MicroCredentialRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/micro-credential')]
final class MicroCredentialController extends AbstractController
{
    #[Route(name: 'app_admin_micro_credential_index', methods: ['GET'])]
    public function index(MicroCredentialRepository $repository, Request $request): Response
    {
        $page = $request->query->getInt('page', 1);
        $itemsPerPage = 25;

        $microCredentials = $repository->findPaginatedByCategory($page, $itemsPerPage);

        return $this->render('admin/micro_credential/index.html.twig', [
            'micro_credentials' => $microCredentials->getCurrentPageResults(),
            'currentPage' => $microCredentials->getCurrentPage(),
            'totalPages' => $microCredentials->getNbPages(),
            'totalItemsPerPage' => $microCredentials->getNbResults(),
            'itemsPerPage' => $itemsPerPage,
        ]);
    }

    #[Route('/new', name: 'app_admin_micro_credential_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $microCredential = new MicroCredential();
        $form = $this->createForm(MicroCredentialForm::class, $microCredential);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $this->handleFileUpload($form, $microCredential);

            $entityManager->persist($microCredential);
            $entityManager->flush();

            $this->addFlash('success', 'Micro-credential created successfully!');
            return $this->redirectToRoute('app_admin_micro_credential_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/micro_credential/new.html.twig', [
            'micro_credential' => $microCredential,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_admin_micro_credential_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, MicroCredential $microCredential, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(MicroCredentialForm::class, $microCredential);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Handle file upload
            $this->handleFileUpload($form, $microCredential);

            $entityManager->flush();

            $this->addFlash('success', 'Micro-credential updated successfully!');
            return $this->redirectToRoute('app_admin_micro_credential_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/micro_credential/edit.html.twig', [
            'microCredential' => $microCredential,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_admin_micro_credential_delete', methods: ['DELETE'])]
    public function delete(Request $request, MicroCredential $microCredential, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$microCredential->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($microCredential);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_admin_micro_credential_index', [], Response::HTTP_SEE_OTHER);
    }



    public function handleFileUpload(\Symfony\Component\Form\FormInterface $form, MicroCredential $microCredential): void
    {
        $badgeFile = $form->get('badgeFile')->getData();
        if ($badgeFile) {
            $originalFilename = pathinfo($badgeFile->getClientOriginalName(), PATHINFO_FILENAME);
            $safeFilename = transliterator_transliterate('Any-Latin; Latin-ASCII; [^A-Za-z0-9_] remove; Lower()', $originalFilename);
            $newFilename = $safeFilename . '-' . uniqid() . '.' . $badgeFile->guessExtension();

            try {
                $uploadDir = $this->getParameter('kernel.project_dir') . '/public/uploads/badges';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }

                $badgeFile->move($uploadDir, $newFilename);
                $microCredential->setBadgeUrl('/uploads/badges/' . $newFilename);
            } catch (FileException $e) {
                $this->addFlash('error', 'There was an error uploading the badge file.');
            }
        }
    }
}
