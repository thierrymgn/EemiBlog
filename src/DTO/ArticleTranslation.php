<?php

namespace App\DTO;

class ArticleTranslation
{
    public function __construct(
        public readonly string $title,
        public readonly string $content,
        public readonly string $metaTitle,
        public readonly string $metaDescription,
        public readonly string $language
    ) {}
}
