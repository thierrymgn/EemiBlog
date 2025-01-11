<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\ProfileType;
use App\Form\UserType;
use App\Repository\ArticleRepository;
use App\Repository\CommentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

class ProfileController extends AbstractController
{
    #[Route('/profile', name: 'app_profile')]
    public function index(ArticleRepository $articleRepository, CommentRepository $commentRepository): Response
    {
        $user = $this->getUser();

        $articlesStats = [
            'total' => $articleRepository->count(['customer' => $user]),
            'published' => $articleRepository->count([
                'customer' => $user,
                'is_published' => true
            ]),
            'draft' => $articleRepository->count([
                'customer' => $user,
                'is_published' => false
            ])
        ];

        $commentsStats = [
            'total' => $commentRepository->count(['publisher' => $user]),
            'approved' => $commentRepository->count([
                'publisher' => $user,
                'is_approved' => true
            ]),
            'pending' => $commentRepository->count([
                'publisher' => $user,
                'is_approved' => false
            ])
        ];

        $latestArticles = $articleRepository->findBy(
            ['customer' => $user],
            ['createdAt' => 'DESC'],
            5
        );

        $latestComments = $commentRepository->findBy(
            ['publisher' => $user],
            ['createdAt' => 'DESC'],
            5
        );

        return $this->render('profile/index.html.twig', [
            'controller_name' => 'ProfileController',
            'user' => $user,
            'articlesStats' => $articlesStats,
            'commentsStats' => $commentsStats,
            'latestArticles' => $latestArticles,
            'latestComments' => $latestComments
        ]);
    }

    #[Route('/profile/edit', name: 'app_profile_edit')]
    public function edit(Request $request, EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher): Response
    {
        $user = $this->getUser();
        $form = $this->createForm(ProfileType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($password = $form->get('plainPassword')->getData()) {
                $user->setPassword(
                    $passwordHasher->hashPassword($user, $password)
                );
            }

            $user->setUpdatedAt(new \DateTimeImmutable());
            $entityManager->flush();

            $this->addFlash('success', 'Votre profil a été mis à jour avec succès.');
            return $this->redirectToRoute('app_profile');
        }

        return $this->render('profile/edit.html.twig', [
            'form' => $form,
        ]);
    }
}
