<?php

namespace App\Controller;

use App\Entity\Article;
use App\Entity\Comment;
use App\Entity\Media;
use App\Form\ArticleType;
use App\Form\UserCommentType;
use App\Repository\ArticleRepository;
use App\Repository\MediaRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/article')]
final class ArticleController extends AbstractController
{
    #[Route(name: 'app_article_index', methods: ['GET'])]
    public function index(ArticleRepository $articleRepository): Response
    {
        return $this->render('article/index.html.twig', [
            'articles' => $articleRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_article_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER')]
    public function new(Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        $article = new Article();
        $form = $this->createForm(ArticleType::class, $article);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $article->setCustomer($this->getUser());
            $article->setSlug($slugger->slug($article->getTitle())->lower());
            $article->setCustomer($this->getUser());
            $article->setPublished(true);
            $article->setViewCount(0);
            $article->setPublishedAt(new \DateTimeImmutable());
            $article->setCreatedAt(new \DateTimeImmutable());
            $article->setUpdatedAt(new \DateTimeImmutable());

            if ($imageFile = $form->get('imageFile')->getData()) {
                $newFilename = uniqid() . '.' . $imageFile->guessExtension();

                $media = new Media();
                $media->setName($newFilename);
                $media->setFileName($newFilename);
                $media->setMimeType($imageFile->getMimeType());
                $media->setSize($imageFile->getSize());

                try {
                    $imageFile->move(
                        $this->getParameter('media_directory'),
                        $newFilename
                    );

                    $media->setPath('/uploads/media/' . $newFilename);
                    $media->setCreatedAt(new \DateTimeImmutable());
                    $media->setUpdatedAt(new \DateTimeImmutable());

                    $entityManager->persist($media);

                    $article->setFeaturedImage($media->getPath());
                } catch (FileException $e) {
                    $this->addFlash('error', 'Une erreur est survenue lors de l\'upload du fichier : ' . $e->getMessage());
                }
            }

            $entityManager->persist($article);
            $entityManager->flush();

            return $this->redirectToRoute('app_home');
        }

        return $this->render('article/new.html.twig', [
            'article' => $article,
            'form' => $form,
        ]);
    }

    #[Route('/{slug}', name: 'app_article_show', methods: ['GET', 'POST'])]
    public function show(string $slug, ArticleRepository $articleRepository, EntityManagerInterface $entityManager, Request $request): Response
    {
        $article = $articleRepository->findOneBy(['slug' => $slug]);

        if (!$article) {
            throw $this->createNotFoundException('Article non trouvé');
        }

        $article->setViewCount($article->getViewCount() + 1);
        $entityManager->flush();

        $comment = new Comment();
        $form = $this->createForm(UserCommentType::class, $comment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $comment->setArticle($article);
            $comment->setPublisher($this->getUser());
            $comment->setCreatedAt(new \DateTimeImmutable());
            $comment->setUpdatedAt(new \DateTimeImmutable());
            $comment->setApproved(true);
            $comment->setLevel(0);
            $comment->setIpAddress($request->getClientIp());
            $comment->setUserAgent($request->headers->get('User-Agent'));
            $comment->setLikesCount(0);
            $comment->setStatus('approved');

            $entityManager->persist($comment);
            $entityManager->flush();

            $this->addFlash('success', 'Votre commentaire a été ajouté');
            return $this->redirectToRoute('app_article_show', ['slug' => $article->getSlug()]);
        }

        $similarArticles = $articleRepository->findSimilarArticles($article);

        return $this->render('article/slug.html.twig', [
            'article' => $article,
            'similarArticles' => $similarArticles,
            'commentForm' => $form,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_article_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Article $article, EntityManagerInterface $entityManager, MediaRepository $mediaRepository): Response
    {
        $form = $this->createForm(ArticleType::class, $article);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $article->setUpdatedAt(new \DateTimeImmutable());

            if ($imageFile = $form->get('imageFile')->getData()) {
                $articleMedia = $mediaRepository->findOneBy(['path' => $article->getFeaturedImage()]);
                if ($oldMedia = $articleMedia) {
                    $oldFilePath = $this->getParameter('media_directory').'/'.$oldMedia->getFileName();
                    if (file_exists($oldFilePath)) {
                        unlink($oldFilePath);
                    }
                    $entityManager->remove($oldMedia);
                }

                $newFilename = uniqid() . '.' . $imageFile->guessExtension();

                $media = new Media();
                $media->setName($newFilename);
                $media->setFileName($newFilename);
                $media->setMimeType($imageFile->getMimeType());
                $media->setSize($imageFile->getSize());

                try {
                    $imageFile->move(
                        $this->getParameter('media_directory'),
                        $newFilename
                    );

                    $media->setPath('/uploads/media/' . $newFilename);
                    $media->setCreatedAt(new \DateTimeImmutable());
                    $media->setUpdatedAt(new \DateTimeImmutable());

                    $entityManager->persist($media);

                    $article->setFeaturedImage($media->getPath());
                } catch (FileException $e) {
                    $this->addFlash('error', 'Une erreur est survenue lors de l\'upload du fichier : ' . $e->getMessage());
                }
            }

            $entityManager->flush();

            return $this->redirectToRoute('app_article_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('article/edit.html.twig', [
            'article' => $article,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_article_delete', methods: ['POST'])]
    public function delete(Request $request, Article $article, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$article->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($article);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_article_index', [], Response::HTTP_SEE_OTHER);
    }
}
