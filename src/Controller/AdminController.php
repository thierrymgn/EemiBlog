<?php

namespace App\Controller;

use App\Repository\ArticleRepository;
use App\Repository\CommentRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
#[Route('/admin')]
class AdminController extends AbstractController
{
    #[Route('/', name: 'app_admin_dashboard')]
    public function dashboard(
        ArticleRepository $articleRepository,
        CommentRepository $commentRepository,
        UserRepository $userRepository
    ): Response
    {
        $stats = [
            'total_articles' => $articleRepository->count([]),
            'published_articles' => $articleRepository->count(['published_at' => ['not' => null]]),
            'total_comments' => $commentRepository->count([]),
            'pending_comments' => $commentRepository->count(['is_approved' => false]),
            'total_users' => $userRepository->count([]),
            'banned_users' => $userRepository->count(['is_banned' => true]),
        ];

        $recentArticles = $articleRepository->findBy(
            [],
            ['createdAt' => 'DESC'],
            5
        );

        $recentComments = $commentRepository->findBy(
            [],
            ['createdAt' => 'DESC'],
            5
        );

        return $this->render('admin/index.html.twig', [
            'stats' => $stats,
            'recent_articles' => $recentArticles,
            'recent_comments' => $recentComments,
        ]);
    }
}
