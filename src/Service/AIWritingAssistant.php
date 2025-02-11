<?php
namespace App\Service;

use App\DTO\ArticleImprovement;
use App\DTO\TitleSuggestions;
use OpenAI;

class AIWritingAssistant
{
    private OpenAI\Client $client;

    public function __construct(string $apiKey)
    {
        $this->client = OpenAI::client($apiKey);
    }

    public function suggestTitles(string $content): TitleSuggestions
    {
        $response = $this->client->chat()->create([
            'model' => 'gpt-4o-mini',
            'messages' => [[
                'role' => 'system',
                'content' => 'Tu es un expert en rédaction de titres. Génère 5 suggestions de titres accrocheurs pour cet article.'
            ], [
                'role' => 'user',
                'content' => $content
            ]],
            'response_format' => [
                'type' => 'json_schema',
                'json_schema' => [
                    'name' => 'title_suggestions',
                    'strict' => true,
                    'schema' => [
                        'type' => 'object',
                        'properties' => [
                            'titles' => [
                                'type' => 'array',
                                'items' => ['type' => 'string']
                            ]
                        ],
                        'required' => ['titles'],
                        'additionalProperties' => false
                    ]
                ]
            ]
        ]);

        $data = json_decode($response->choices[0]->message->content, true);
        return new TitleSuggestions(titles: $data['titles']);
    }

    public function improveContent(string $content): mixed
    {
        $response = $this->client->chat()->create([
            'model' => 'gpt-4o-mini',
            'messages' => [[
                'role' => 'system',
                'content' => 'Tu es un expert en rédaction web et SEO. Ta tâche est d\'améliorer le contenu fourni en suivant ces critères :
               1. Optimisation SEO : Améliore le texte pour le référencement naturel sans le surcharger de mots-clés
               2. Structure : Assure une structure claire avec des transitions fluides
               3. Style : Rends le texte plus engageant et professionnel tout en gardant un ton naturel
               4. Longueur : Conserve la longueur originale mais optimise chaque phrase'
            ], [
                'role' => 'user',
                'content' => $content
            ]],
            'response_format' => [
                'type' => 'json_schema',
                'json_schema' => [
                    'name' => 'article_improvement',
                    'strict' => true,
                    'schema' => [
                        'type' => 'object',
                        'properties' => [
                            'content' => ['type' => 'string'],
                            'summary' => ['type' => 'string'],
                            'suggestions' => [
                                'type' => 'array',
                                'items' => ['type' => 'string']
                            ],
                            'seo_score' => [
                                'type' => 'object',
                                'properties' => [
                                    'score' => ['type' => 'number'],
                                    'explanation' => ['type' => 'string'],
                                ],
                                'required' => ['score', 'explanation'],
                                'additionalProperties' => false
                            ],
                            'readability_tips' => [
                                'type' => 'array',
                                'items' => ['type' => 'string']
                            ]
                        ],
                        'required' => ['content', 'summary', 'suggestions', 'seo_score', 'readability_tips'],
                        'additionalProperties' => false
                    ]
                ]
            ]
        ]);

        $data = json_decode($response->choices[0]->message->content, true);
        return new ArticleImprovement(
            content: $data['content'],
            summary: $data['summary'],
            suggestions: $data['suggestions'],
            seoScore: $data['seo_score'],
            readabilityTips: $data['readability_tips']
        );
    }
}
