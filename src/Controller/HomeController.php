<?php

namespace App\Controller;

use App\Repository\ArticleRepository;
use App\Repository\CategoryRepository;
use App\Repository\TagRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(
        Request $request,
        ArticleRepository $articleRepository,
        CategoryRepository $categoryRepository,
        TagRepository $tagRepository
    ): Response {
        $page = $request->query->getInt('page', 1);
        $limit = $request->query->getInt('limit', 10);
        $category = $request->query->get('category');
        $search = $request->query->get('search');
        $tagSlug = $request->query->get('tag');

        $articles = $articleRepository->findArticlesPaginated(
            page: $page,
            categorySlug: $category,
            tagSlug: $tagSlug,
            search: $search
        );

        $categories = $categoryRepository->findBy(['is_visible' => true], ['position' => 'ASC']);
        $tags = $tagRepository->findAll();

        return $this->render('home/index.html.twig', [
            'articles' => $articles,
            'categories' => $categories,
            'currentCategory' => $category,
            'search' => $search,
            'limit' => $limit,
            'page' => $page,
            'tags' => $tags,
            'currentTag' => $tagSlug,
        ]);
    }
}
