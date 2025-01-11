<?php

namespace App\Controller;

use App\Entity\Comment;
use App\Form\CommentType;
use App\Repository\CommentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/comment')]
final class CommentController extends AbstractController
{
    #[Route(name: 'app_comment_index', methods: ['GET'])]
    public function index(CommentRepository $commentRepository): Response
    {
        return $this->render('comment/index.html.twig', [
            'comments' => $commentRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_comment_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $comment = new Comment();

        $comment->setLevel(0);
        $comment->setLikesCount(0);
        $comment->setIpAddress($request->getClientIp());
        $comment->setUserAgent($request->headers->get('User-Agent'));
        $comment->setApproved(true);
        $comment->setCreatedAt(new \DateTimeImmutable());
        $comment->setUpdatedAt(new \DateTimeImmutable());

        $form = $this->createForm(CommentType::class, $comment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            if ($comment->getParent()) {
                $comment->setLevel($comment->getParent()->getLevel() + 1);
            }

            $entityManager->persist($comment);
            $entityManager->flush();

            return $this->redirectToRoute('app_comment_index');
        }

        return $this->render('comment/new.html.twig', [
            'comment' => $comment,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_comment_show', methods: ['GET'])]
    public function show(Comment $comment): Response
    {
        return $this->render('comment/show.html.twig', [
            'comment' => $comment,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_comment_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Comment $comment, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(CommentType::class, $comment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_comment_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('comment/edit.html.twig', [
            'comment' => $comment,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_comment_delete', methods: ['POST'])]
    public function delete(Request $request, Comment $comment, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$comment->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($comment);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_comment_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}/reply', name: 'app_comment_reply', methods: ['POST'])]
    public function reply(Comment $parentComment, Request $request, EntityManagerInterface $entityManager): Response
    {
        if (!$this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        $content = $request->request->get('content');
        if (!$content) {
            $this->addFlash('error', 'Le contenu du commentaire ne peut pas être vide');
            return $this->redirectToRoute('app_article_show', ['slug' => $parentComment->getArticle()->getSlug()]);
        }

        $reply = new Comment();
        $reply->setContent($content);
        $reply->setArticle($parentComment->getArticle());
        $reply->setPublisher($this->getUser());
        $reply->setParent($parentComment);
        $reply->setLevel($parentComment->getLevel() + 1);
        $reply->setApproved(true);
        $reply->setCreatedAt(new \DateTimeImmutable());
        $reply->setUpdatedAt(new \DateTimeImmutable());
        $reply->setIpAddress($request->getClientIp());
        $reply->setUserAgent($request->headers->get('User-Agent'));
        $reply->setLikesCount(0);
        $reply->setStatus('approved');

        $entityManager->persist($reply);
        $entityManager->flush();

        $this->addFlash('success', 'Votre réponse a été ajoutée');
        return $this->redirectToRoute('app_article_show', ['slug' => $parentComment->getArticle()->getSlug()]);
    }
}
