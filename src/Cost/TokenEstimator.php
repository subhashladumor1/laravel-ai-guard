<?php

declare(strict_types=1);

namespace Subhashladumor1\LaravelAiGuard\Cost;

class TokenEstimator
{
    public function __construct(
        protected int $charsPerToken = 4
    ) {
    }

    public function estimate(string $text): int
    {
        if ($text === '') {
            return 0;
        }
        return (int) ceil(strlen($text) / $this->charsPerToken);
    }

    public function getCharsPerToken(): int
    {
        return $this->charsPerToken;
    }
}
