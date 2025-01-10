<?php

namespace App\Controller;

use App\Repository\ArticleRepository;
use App\Repository\CategoryRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{
    #[Route('/home', name: 'app_home')]
    public function index(
        Request $request,
        ArticleRepository $articleRepository,
        CategoryRepository $categoryRepository
    ): Response {
        $page = $request->query->getInt('page', 1);
        $limit = $request->query->getInt('limit', 10);
        $category = $request->query->get('category');
        $search = $request->query->get('search');

        $articles = $articleRepository->findArticlesPaginated(
            page: $page,
            categorySlug: $category,
            search: $search
        );

        $categories = $categoryRepository->findBy(['is_visible' => true], ['position' => 'ASC']);

        return $this->render('home/index.html.twig', [
            'articles' => $articles,
            'categories' => $categories,
            'currentCategory' => $category,
            'search' => $search,
            'limit' => $limit,
            'page' => $page
        ]);
    }
}
