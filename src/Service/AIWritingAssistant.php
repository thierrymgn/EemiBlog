<?php
namespace App\Service;

use OpenAI;

class AIWritingAssistant
{
    private OpenAI\Client $client;

    public function __construct(string $apiKey)
    {
        $this->client = OpenAI::client($apiKey);
    }

    public function suggestTitles(string $content): array
    {
        $response = $this->client->chat()->create([
            'model' => 'gpt-4o-mini',
            'messages' => [[
                'role' => 'system',
                'content' => 'Tu es un expert en rédaction de titres. Je veux que tu me retournes UNIQUEMENT un JSON avec un tableau "titles" contenant 5 suggestions de titres pour cet article. Format attendu: {"titles": ["titre 1", "titre 2", "titre 3", "titre 4", "titre 5"]}'
            ], [
                'role' => 'user',
                'content' => $content
            ]]
        ]);

        $jsonResponse = json_decode($response->choices[0]->message->content, true);
        dump($jsonResponse);
        return $jsonResponse['titles'] ?? [];
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
               4. Longueur : Conserve la longueur originale mais optimise chaque phrase
    
               Retourne UNIQUEMENT un JSON structuré avec :
               - "content": le texte amélioré
               - "summary": un résumé concis en 2-3 phrases
               - "suggestions": un tableau de 3-4 suggestions d\'améliorations spécifiques
               - "seo_score": une note sur 100 avec une brève explication
               - "readability_tips": des conseils pour améliorer la lisibilité
    
               Format JSON attendu:
               {
                   "content": "texte amélioré",
                   "summary": "résumé concis",
                   "suggestions": ["suggestion 1", "suggestion 2", "suggestion 3"],
                   "seo_score": {"score": 85, "explanation": "explication"},
                   "readability_tips": ["conseil 1", "conseil 2"]
               }'
            ], [
                'role' => 'user',
                'content' => $content
            ]]
        ]);

        dump($response->choices);

        return json_decode($response->choices[0]->message->content, true);
    }
}
