<?php

declare(strict_types=1);

namespace Subhashladumor1\LaravelAiGuard\Cost;

use Illuminate\Support\Facades\Config;

class CostCalculator
{
    /** Default input token threshold for long-context pricing (e.g. 200k). */
    public const LONG_CONTEXT_THRESHOLD = 200000;

    /**
     * Runtime pricing per provider/model. Checked before config.
     * Keys: "provider.model" => array of pricing (input, output, cached_input, cache_write, modality-specific).
     *
     * @var array<string, array<string, float>>
     */
    protected array $runtimePricing = [];

    /**
     * Standard pricing keys (per 1k tokens unless noted).
     * Modality: per_image ($/image), per_second_video ($/s), per_minute_transcription ($/min),
     * per_1m_chars_tts ($/1M chars), per_1k_web_search ($/1k calls), per_1k_embedding ($/1k tokens).
     */
    private const PRICING_KEYS = [
        'input',
        'output',
        'cached_input',
        'cache_write',
        'input_long',
        'output_long',
        'image_in',
        'image_out',
        'audio_in',
        'audio_out',
        'per_image',
        'per_second_video',
        'per_minute_transcription',
        'per_1m_chars_tts',
        'per_1k_web_search',
        'per_1k_embedding',
    ];

    /**
     * Get pricing for a provider/model: optional override first, then runtime registry, then config.
     *
     * @return array<string, float>|null
     */
    protected function getPricing(string $provider, string $model, ?array $override = null): ?array
    {
        $key = strtolower($provider) . '.' . strtolower($model);
        $base = null;

        if ($override !== null && (isset($override['input']) || isset($override['output']))) {
            $base = [];
            foreach (self::PRICING_KEYS as $k) {
                $base[$k] = isset($override[$k]) ? (float) $override[$k] : 0.0;
            }
        }

        if ($base === null && isset($this->runtimePricing[$key])) {
            $base = $this->runtimePricing[$key];
        }

        if ($base === null) {
            $pricing = Config::get("ai-guard.pricing.{$provider}.{$model}");
            $hasAny = is_array($pricing) && array_intersect_key($pricing, array_fill_keys(self::PRICING_KEYS, 1));
            if ($hasAny) {
                $base = [];
                foreach (self::PRICING_KEYS as $k) {
                    $base[$k] = isset($pricing[$k]) ? (float) $pricing[$k] : 0.0;
                }
            }
        }

        return $base;
    }

    /**
     * @param  int  $inputTokens  Standard (non-cached) input token count
     * @param  int  $outputTokens  Output token count
     * @param  int  $cachedInputTokens  Cache-hit input tokens
     * @param  int  $cacheWriteTokens  Cache creation/write tokens
     */
    public function calculate(
        string $provider,
        string $model,
        int $inputTokens,
        int $outputTokens,
        ?array $pricingOverride = null,
        int $cachedInputTokens = 0,
        int $cacheWriteTokens = 0
    ): float {
        $totalInput = $inputTokens + $cachedInputTokens + $cacheWriteTokens;
        return $this->calculateWithUsage($provider, $model, [
            'input_tokens' => $totalInput,
            'output_tokens' => $outputTokens,
            'cached_input_tokens' => $cachedInputTokens,
            'cache_write_tokens' => $cacheWriteTokens,
        ], $pricingOverride);
    }

    /**
     * Calculate cost from full usage array. Supports text, cache, long-context, image/audio/video tokens,
     * per-image, per-second video, transcription, TTS, web search, embeddings for maximum accuracy.
     *
     * Usage array keys (all optional): input_tokens, output_tokens, cached_input_tokens, cache_write_tokens,
     * image_tokens_in, image_tokens_out, audio_tokens_in, audio_tokens_out, images_generated, video_seconds,
     * transcription_minutes, tts_characters, web_search_calls, embedding_tokens, long_context (bool).
     *
     * @param  array<string, int|float|bool>  $usage
     */
    public function calculateWithUsage(
        string $provider,
        string $model,
        array $usage,
        ?array $pricingOverride = null
    ): float {
        $pricing = $this->getPricing($provider, $model, $pricingOverride);
        if ($pricing === null) {
            return 0.0;
        }

        $inputTokens = (int) ($usage['input_tokens'] ?? 0);
        $outputTokens = (int) ($usage['output_tokens'] ?? 0);
        $cachedInputTokens = (int) ($usage['cached_input_tokens'] ?? 0);
        $cacheWriteTokens = (int) ($usage['cache_write_tokens'] ?? 0);
        $longContext = (bool) ($usage['long_context'] ?? $inputTokens > self::LONG_CONTEXT_THRESHOLD);

        $cost = 0.0;

        $inputRate = ($longContext && isset($pricing['input_long']) && $pricing['input_long'] > 0)
            ? $pricing['input_long'] : ($pricing['input'] ?? 0);
        $outputRate = ($longContext && isset($pricing['output_long']) && $pricing['output_long'] > 0)
            ? $pricing['output_long'] : ($pricing['output'] ?? 0);

        $standardInput = max(0, $inputTokens - $cachedInputTokens - $cacheWriteTokens);

        // Subtract multimodal tokens from standard text tokens if their pricing is defined and usage is present
        if (isset($usage['image_tokens_in']) && isset($pricing['image_in'])) {
            $standardInput = max(0, $standardInput - (int) $usage['image_tokens_in']);
        }
        if (isset($usage['audio_tokens_in']) && isset($pricing['audio_in'])) {
            $standardInput = max(0, $standardInput - (int) $usage['audio_tokens_in']);
        }
        if (isset($usage['video_tokens_in']) && isset($pricing['video_in'])) {
            $standardInput = max(0, $standardInput - (int) $usage['video_tokens_in']);
        }

        $standardOutput = $outputTokens;

        if (isset($usage['image_tokens_out']) && isset($pricing['image_out'])) {
            $standardOutput = max(0, $standardOutput - (int) $usage['image_tokens_out']);
        }
        if (isset($usage['audio_tokens_out']) && isset($pricing['audio_out'])) {
            $standardOutput = max(0, $standardOutput - (int) $usage['audio_tokens_out']);
        }
        if (isset($usage['video_tokens_out']) && isset($pricing['video_out'])) {
            $standardOutput = max(0, $standardOutput - (int) $usage['video_tokens_out']);
        }

        $cost += $inputRate * ($standardInput / 1000);
        $cost += $outputRate * ($standardOutput / 1000);

        if ($cachedInputTokens > 0 && isset($pricing['cached_input']) && $pricing['cached_input'] > 0) {
            $cost += $pricing['cached_input'] * ($cachedInputTokens / 1000);
        }
        if ($cacheWriteTokens > 0 && isset($pricing['cache_write']) && $pricing['cache_write'] > 0) {
            $cost += $pricing['cache_write'] * ($cacheWriteTokens / 1000);
        }

        if (isset($usage['image_tokens_in'], $pricing['image_in']) && $pricing['image_in'] > 0) {
            $cost += $pricing['image_in'] * ((int) $usage['image_tokens_in'] / 1000);
        }
        if (isset($usage['image_tokens_out'], $pricing['image_out']) && $pricing['image_out'] > 0) {
            $cost += $pricing['image_out'] * ((int) $usage['image_tokens_out'] / 1000);
        }
        if (isset($usage['audio_tokens_in'], $pricing['audio_in']) && $pricing['audio_in'] > 0) {
            $cost += $pricing['audio_in'] * ((int) $usage['audio_tokens_in'] / 1000);
        }
        if (isset($usage['audio_tokens_out'], $pricing['audio_out']) && $pricing['audio_out'] > 0) {
            $cost += $pricing['audio_out'] * ((int) $usage['audio_tokens_out'] / 1000);
        }
        if (isset($usage['images_generated'], $pricing['per_image']) && $pricing['per_image'] > 0) {
            $cost += $pricing['per_image'] * (int) $usage['images_generated'];
        }
        if (isset($usage['video_seconds'], $pricing['per_second_video']) && $pricing['per_second_video'] > 0) {
            $cost += $pricing['per_second_video'] * (float) $usage['video_seconds'];
        }
        if (isset($usage['transcription_minutes'], $pricing['per_minute_transcription']) && $pricing['per_minute_transcription'] > 0) {
            $cost += $pricing['per_minute_transcription'] * (float) $usage['transcription_minutes'];
        }
        if (isset($usage['tts_characters'], $pricing['per_1m_chars_tts']) && $pricing['per_1m_chars_tts'] > 0) {
            $cost += $pricing['per_1m_chars_tts'] * ((float) $usage['tts_characters'] / 1_000_000);
        }
        if (isset($usage['web_search_calls'], $pricing['per_1k_web_search']) && $pricing['per_1k_web_search'] > 0) {
            $cost += $pricing['per_1k_web_search'] * ((int) $usage['web_search_calls'] / 1000);
        }
        if (isset($usage['embedding_tokens'], $pricing['per_1k_embedding']) && $pricing['per_1k_embedding'] > 0) {
            $cost += $pricing['per_1k_embedding'] * ((int) $usage['embedding_tokens'] / 1000);
        }

        return round($cost, 7);
    }

    /**
     * Register or override pricing for a provider/model at runtime (e.g. from DB or env).
     * Use this to support multiple AI models without editing config.
     *
     * @param  array{input: float, output: float}  $pricing  Cost per 1k input tokens and per 1k output tokens
     */
    /**
     * @param  array<string, float>  $pricing  Cost per 1k tokens (input, output, cached_input, cache_write, etc.) and optional modality keys
     */
    public function setPricing(string $provider, string $model, array $pricing): self
    {
        $key = strtolower($provider) . '.' . strtolower($model);
        $merged = [];
        foreach (self::PRICING_KEYS as $k) {
            $merged[$k] = isset($pricing[$k]) ? (float) $pricing[$k] : 0.0;
        }
        $this->runtimePricing[$key] = $merged;
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
            if (!is_array($models)) {
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
