<?php

namespace App\Controller;

use App\Entity\Media;
use App\Form\MediaType;
use App\Repository\MediaRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/media')]
final class MediaController extends AbstractController
{
    #[Route(name: 'app_media_index', methods: ['GET'])]
    public function index(MediaRepository $mediaRepository): Response
    {
        if ($this->isGranted('ROLE_ADMIN')) {
            $medias = $mediaRepository->findAll();
        } else {
            $medias = $mediaRepository->findBy(['user' => $this->getUser()]);
        }

        return $this->render('media/index.html.twig', [
            'media' => $medias,
        ]);
    }

    #[Route('/new', name: 'app_media_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $media = new Media();
        $media->setCustomer($this->getUser());
        $form = $this->createForm(MediaType::class, $media);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile $uploadedFile */
            $uploadedFile = $form->get('file')->getData();

            if ($uploadedFile) {
                $originalFilename = pathinfo($uploadedFile->getClientOriginalName(), PATHINFO_FILENAME);
                $newFilename = $originalFilename.'-'.uniqid().'.'.$uploadedFile->guessExtension();

                try {
                    $mimeType = $uploadedFile->getMimeType();
                    $size = $uploadedFile->getSize();

                    $uploadedFile->move(
                        $this->getParameter('media_directory'),
                        $newFilename
                    );
                    $media->setFileName($newFilename);
                    $media->setMimeType($mimeType);
                    $media->setSize($size);
                    $media->setPath('/uploads/media/'.$newFilename);

                } catch (FileException $e) {
                    $this->addFlash('error', 'Une erreur est survenue lors de l\'upload du fichier : ' . $e->getMessage());
                }
            }

            $media->setCreatedAt(new \DateTimeImmutable());
            $media->setUpdatedAt(new \DateTimeImmutable());

            $entityManager->persist($media);
            $entityManager->flush();

            return $this->redirectToRoute('app_media_index');
        }

        return $this->render('media/new.html.twig', [
            'media' => $media,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_media_show', methods: ['GET'])]
    public function show(Media $medium): Response
    {
        return $this->render('media/show.html.twig', [
            'medium' => $medium,
        ]);
    }

    #[Route('/{id}', name: 'app_media_delete', methods: ['POST'])]
    public function delete(Request $request, Media $media, EntityManagerInterface $entityManager): Response
    {
        if (!$this->isGranted('ROLE_ADMIN') && $media->getCustomer() !== $this->getUser()) {
            throw $this->createAccessDeniedException('Vous ne pouvez pas supprimer ce média');
        }

        if ($this->isCsrfTokenValid('delete'.$media->getId(), $request->request->get('_token'))) {
            $filePath = $this->getParameter('media_directory').'/'.$media->getFileName();
            if (file_exists($filePath)) {
                unlink($filePath);
            }

            $entityManager->remove($media);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_media_index');
    }
}
