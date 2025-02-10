<?php

namespace App\DTO;

class ArticleImprovement
{
    public function __construct(
        public readonly string $content,
        public readonly string $summary,
        public readonly array $suggestions,
        public readonly array $seoScore,
        public readonly array $readabilityTips
    ) {}
}
