<?php

declare(strict_types=1);

namespace Subhashladumor1\LaravelAiGuard\Cost;

use Illuminate\Support\Facades\Config;

class CostCalculator
{
    /**
     * Runtime pricing per provider/model. Checked before config.
     * Keys: "provider.model" => ['input' => float, 'output' => float] (per 1k tokens).
     *
     * @var array<string, array{input: float, output: float}>
     */
    protected array $runtimePricing = [];

    /**
     * Get pricing for a provider/model: optional override first, then runtime registry, then config.
     *
     * @return array{input: float, output: float}|null
     */
    protected function getPricing(string $provider, string $model, ?array $override = null): ?array
    {
        if ($override !== null && isset($override['input'], $override['output'])) {
            return ['input' => (float) $override['input'], 'output' => (float) $override['output']];
        }
        $key = strtolower($provider) . '.' . strtolower($model);
        if (isset($this->runtimePricing[$key])) {
            return $this->runtimePricing[$key];
        }
        $pricing = Config::get("ai-guard.pricing.{$provider}.{$model}");
        if (is_array($pricing) && (isset($pricing['input']) || isset($pricing['output']))) {
            return [
                'input' => (float) ($pricing['input'] ?? 0),
                'output' => (float) ($pricing['output'] ?? 0),
            ];
        }
        return null;
    }

    public function calculate(
        string $provider,
        string $model,
        int $inputTokens,
        int $outputTokens,
        ?array $pricingOverride = null
    ): float {
        $pricing = $this->getPricing($provider, $model, $pricingOverride);
        if ($pricing === null) {
            return 0.0;
        }
        $inputPrice = $pricing['input'] * ($inputTokens / 1000);
        $outputPrice = $pricing['output'] * ($outputTokens / 1000);
        return round($inputPrice + $outputPrice, 6);
    }

    /**
     * Register or override pricing for a provider/model at runtime (e.g. from DB or env).
     * Use this to support multiple AI models without editing config.
     *
     * @param  array{input: float, output: float}  $pricing  Cost per 1k input tokens and per 1k output tokens
     */
    public function setPricing(string $provider, string $model, array $pricing): self
    {
        $key = strtolower($provider) . '.' . strtolower($model);
        $this->runtimePricing[$key] = [
            'input' => (float) ($pricing['input'] ?? 0),
            'output' => (float) ($pricing['output'] ?? 0),
        ];
        return $this;
    }

    /**
     * Set multiple model pricings at once.
     *
     * @param  array<string, array<string, array{input: float, output: float}>>  $pricingMap  e.g. ['openai' => ['gpt-4o' => ['input' => 0.0025, 'output' => 0.01]]]
     */
    public function setPricingMap(array $pricingMap): self
    {
        foreach ($pricingMap as $provider => $models) {
            if (! is_array($models)) {
                continue;
            }
            foreach ($models as $model => $pricing) {
                if (is_array($pricing)) {
                    $this->setPricing($provider, (string) $model, $pricing);
                }
            }
        }
        return $this;
    }

    /**
     * Remove pricing for a provider/model from the runtime registry.
     * After removal, pricing will fall back to config (or null if not in config).
     */
    public function removePricing(string $provider, string $model): self
    {
        $key = strtolower($provider) . '.' . strtolower($model);
        unset($this->runtimePricing[$key]);
        return $this;
    }

    /**
     * Clear all runtime pricing (e.g. between tests).
     */
    public function clearRuntimePricing(): self
    {
        $this->runtimePricing = [];
        return $this;
    }

    public function estimateCost(
        string $provider,
        string $model,
        int $estimatedInputTokens,
        int $estimatedOutputTokens,
        ?array $pricingOverride = null
    ): float {
        return $this->calculate($provider, $model, $estimatedInputTokens, $estimatedOutputTokens, $pricingOverride);
    }
}
