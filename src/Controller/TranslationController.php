<?php

namespace App\Controller;

use App\Entity\Translation;
use App\Form\TranslationType;
use App\Repository\TranslationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/translation')]
final class TranslationController extends AbstractController
{
    #[Route(name: 'app_translation_index', methods: ['GET'])]
    public function index(TranslationRepository $translationRepository): Response
    {
        return $this->render('translation/index.html.twig', [
            'translations' => $translationRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_translation_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $translation = new Translation();
        $form = $this->createForm(TranslationType::class, $translation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $translation->setCreatedAt(new \DateTimeImmutable());
            $translation->setUpdatedAt(new \DateTimeImmutable());
            $entityManager->persist($translation);
            $entityManager->flush();

            return $this->redirectToRoute('app_translation_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('translation/new.html.twig', [
            'translation' => $translation,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_translation_show', methods: ['GET'])]
    public function show(Translation $translation): Response
    {
        return $this->render('translation/show.html.twig', [
            'translation' => $translation,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_translation_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Translation $translation, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(TranslationType::class, $translation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_translation_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('translation/edit.html.twig', [
            'translation' => $translation,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_translation_delete', methods: ['POST'])]
    public function delete(Request $request, Translation $translation, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$translation->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($translation);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_translation_index', [], Response::HTTP_SEE_OTHER);
    }
}
