<?php

namespace App\Controller;

use App\Entity\Language;
use App\Form\LanguageType;
use App\Repository\LanguageRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/language')]
final class LanguageController extends AbstractController
{
    #[Route(name: 'app_language_index', methods: ['GET'])]
    public function index(LanguageRepository $languageRepository): Response
    {
        return $this->render('language/index.html.twig', [
            'languages' => $languageRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_language_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $language = new Language();
        $form = $this->createForm(LanguageType::class, $language);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $language->setCreatedAt(new \DateTimeImmutable());
            $language->setUpdatedAt(new \DateTimeImmutable());

            if ($language->isDefault()) {
                $this->updateDefaultLanguage($entityManager, $language);
            }

            $entityManager->persist($language);
            $entityManager->flush();

            return $this->redirectToRoute('app_language_index');
        }

        return $this->render('language/new.html.twig', [
            'language' => $language,
            'form' => $form,
        ]);
    }

    private function updateDefaultLanguage(EntityManagerInterface $entityManager, Language $newDefault): void
    {
        if ($newDefault->isDefault()) {
            $qb = $entityManager->createQueryBuilder();
            $qb->update('App:Language', 'l')
                ->set('l.default', ':default')
                ->where('l.id != :id')
                ->setParameter('default', false)
                ->setParameter('id', $newDefault->getId())
                ->getQuery()
                ->execute();
        }
    }

    #[Route('/{id}', name: 'app_language_show', methods: ['GET'])]
    public function show(Language $language): Response
    {
        return $this->render('language/show.html.twig', [
            'language' => $language,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_language_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Language $language, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(LanguageType::class, $language);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_language_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('language/edit.html.twig', [
            'language' => $language,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_language_delete', methods: ['POST'])]
    public function delete(Request $request, Language $language, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$language->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($language);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_language_index', [], Response::HTTP_SEE_OTHER);
    }
}
