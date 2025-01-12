<?php

namespace App\Controller;

use App\Entity\Comment;
use App\Entity\User;
use App\Repository\CommentRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/moderation')]
#[IsGranted('ROLE_ADMIN')]
class ModerationController extends AbstractController
{
    #[Route('/', name: 'app_admin_moderation')]
    public function index(CommentRepository $commentRepository, UserRepository $userRepository): Response
    {
        $pendingComments = $commentRepository->findBy(['status' => 'pending'], ['createdAt' => 'DESC']);
        $reportedUsers = $userRepository->findBy(['is_banned' => true]);

        return $this->render('admin/moderation/index.html.twig', [
            'pendingComments' => $pendingComments,
            'reportedUsers' => $reportedUsers
        ]);
    }

    #[Route('/comment/{id}/status', name: 'app_admin_comment_status', methods: ['POST'])]
    public function updateCommentStatus(
        Comment $comment,
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        $status = $request->request->get('status');
        if (in_array($status, ['approved', 'rejected', 'spam'])) {
            $comment->setStatus($status);
            $comment->setApproved($status === 'approved');
            $entityManager->flush();

            $this->addFlash('success', 'Statut du commentaire mis à jour');
        }

        return $this->redirectToRoute('app_admin_moderation');
    }

    #[Route('/comment/{id}/delete', name: 'app_admin_comment_delete', methods: ['POST'])]
    public function deleteComment(
        Comment $comment,
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        if ($this->isCsrfTokenValid('delete'.$comment->getId(), $request->request->get('_token'))) {
            $entityManager->remove($comment);
            $entityManager->flush();

            $this->addFlash('success', 'Commentaire supprimé');
        }

        return $this->redirectToRoute('app_admin_moderation');
    }

    #[Route('/user/{id}/ban', name: 'app_admin_user_ban', methods: ['POST'])]
    public function banUser(
        User $user,
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        if ($this->isCsrfTokenValid('ban'.$user->getId(), $request->request->get('_token'))) {
            $user->setBanned(true);
            $user->setRoles(['ROLE_BANNED']);
            $entityManager->flush();

            $this->addFlash('success', 'Utilisateur banni');
        }

        return $this->redirectToRoute('app_admin_moderation');
    }

    #[Route('/user/{id}/unban', name: 'app_admin_user_unban', methods: ['POST'])]
    public function unbanUser(
        User $user,
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        if ($this->isCsrfTokenValid('unban'.$user->getId(), $request->request->get('_token'))) {
            $user->setBanned(false);
            $user->setRoles(['ROLE_USER']);
            $entityManager->flush();

            $this->addFlash('success', 'Utilisateur débanni');
        }

        return $this->redirectToRoute('app_admin_moderation');
    }
}
