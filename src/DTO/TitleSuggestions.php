<?php

namespace App\DTO;

class TitleSuggestions
{
    public function __construct(
        public readonly array $titles
    ) {}
}
