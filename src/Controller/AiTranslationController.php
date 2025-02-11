<?php
namespace App\Controller;

use App\Entity\Article;
use App\Repository\ArticleRepository;
use App\Service\AITranslationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api')]
class AiTranslationController extends AbstractController
{
    public function __construct(private AITranslationService $translationService)
    {
    }

    #[Route('/translate', name: 'app_translate', methods: ['POST'])]
    public function translate(Request $request, ArticleRepository $articleRepository): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $articleId = $data['articleId'] ?? null;
        $language = $data['language'] ?? null;

        if (!$articleId || !$language) {
            return $this->json(['error' => 'Article ID et langue requis'], 400);
        }

        $article = $articleRepository->find($articleId);

        try {
            $translation = $this->translationService->translateArticle(
                $article->getTitle(),
                $article->getContent(),
                $article->getMetaTitle(),
                $article->getMetaDescription(),
                $language
            );

            return $this->json([
                'title' => $translation->title,
                'content' => $translation->content,
                'metaTitle' => $translation->metaTitle,
                'metaDescription' => $translation->metaDescription
            ]);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 500);
        }
    }
}
