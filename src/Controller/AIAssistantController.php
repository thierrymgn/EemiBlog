<?php

namespace App\Controller;

use App\Service\AIWritingAssistant;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/ai-assistant')]
class AIAssistantController extends AbstractController
{
    #[Route('/suggest-titles', name: 'app_ai_suggest_titles', methods: ['POST'])]
    public function suggestTitles(Request $request, AIWritingAssistant $assistant): JsonResponse
    {
        $content = json_decode($request->getContent(), true)['content'] ?? '';

        try {
            $suggestions = $assistant->suggestTitles($content);

            return $this->json(['titles' => $suggestions->titles]);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 500);
        }
    }

    #[Route('/improve-content', name: 'app_ai_improve_content', methods: ['POST'])]
    public function improveContent(Request $request, AIWritingAssistant $assistant): JsonResponse
    {
        $content = json_decode($request->getContent(), true)['content'] ?? '';

        try {
            $improvement = $assistant->improveContent($content);

            return $this->json([
                'content' => $improvement->content,
                'summary' => $improvement->summary,
                'suggestions' => $improvement->suggestions,
                'seo_score' => $improvement->seoScore,
                'readability_tips' => $improvement->readabilityTips
            ]);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 500);
        }
    }
}
