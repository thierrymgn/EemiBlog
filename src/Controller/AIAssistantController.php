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
        $titles = $assistant->suggestTitles($content);

        return $this->json(['titles' => $titles]);
    }

    #[Route('/improve-content', name: 'app_ai_improve_content', methods: ['POST'])]
    public function improveContent(Request $request, AIWritingAssistant $assistant): JsonResponse
    {
        $content = json_decode($request->getContent(), true)['content'] ?? '';
        $result = $assistant->improveContent($content);

        return $this->json($result);
    }
}
