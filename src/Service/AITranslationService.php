<?php

namespace App\Service;

use App\DTO\ArticleTranslation;
use OpenAI;

class AITranslationService
{
    private OpenAI\Client $client;

    public function __construct(string $apiKey)
    {
        $this->client = OpenAI::client($apiKey);
    }

    public function translateArticle(string $title, string $content, ?string $metaTitle, ?string $metaDescription, string $targetLanguage): ArticleTranslation
    {
        $response = $this->client->chat()->create([
            'model' => 'gpt-4o-mini',
            'messages' => [[
                'role' => 'system',
                'content' => "Tu es un traducteur professionnel. Traduis cet article en {$targetLanguage} en gardant le style et le ton d'origine."
            ], [
                'role' => 'user',
                'content' => json_encode([
                    'title' => $title,
                    'content' => $content,
                    'metaTitle' => $metaTitle,
                    'metaDescription' => $metaDescription
                ])
            ]],
            'response_format' => [
                'type' => 'json_schema',
                'json_schema' => [
                    'name' => 'title_suggestions',
                    'strict' => true,
                    'schema' => [
                        'type' => 'object',
                        'properties' => [
                            'title' => ['type' => 'string'],
                            'content' => ['type' => 'string'],
                            'metaTitle' => ['type' => 'string'],
                            'metaDescription' => ['type' => 'string']
                        ],
                        'required' => ['title', 'content', 'metaTitle', 'metaDescription'],
                        'additionalProperties' => false
                    ]
                ]
            ]
        ]);

        $data = json_decode($response->choices[0]->message->content, true);

        return new ArticleTranslation(
            title: $data['title'],
            content: $data['content'],
            metaTitle: $data['metaTitle'],
            metaDescription: $data['metaDescription'],
            language: $targetLanguage
        );
    }
}
