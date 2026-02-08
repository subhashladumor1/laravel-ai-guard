<?php

declare(strict_types=1);

namespace Subhashladumor1\LaravelAiGuard\Cost;

use Illuminate\Support\Facades\Config;

class CostCalculator
{
    public function calculate(
        string $provider,
        string $model,
        int $inputTokens,
        int $outputTokens
    ): float {
        $pricing = Config::get("ai-guard.pricing.{$provider}.{$model}");
        if ($pricing === null) {
            return 0.0;
        }
        $inputPrice = ($pricing['input'] ?? 0) * ($inputTokens / 1000);
        $outputPrice = ($pricing['output'] ?? 0) * ($outputTokens / 1000);
        return round($inputPrice + $outputPrice, 6);
    }

    public function estimateCost(
        string $provider,
        string $model,
        int $estimatedInputTokens,
        int $estimatedOutputTokens
    ): float {
        return $this->calculate($provider, $model, $estimatedInputTokens, $estimatedOutputTokens);
    }
}
