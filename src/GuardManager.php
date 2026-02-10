<?php

declare(strict_types=1);

namespace Subhashladumor1\LaravelAiGuard;

use Subhashladumor1\LaravelAiGuard\Budget\BudgetEnforcer;
use Subhashladumor1\LaravelAiGuard\Budget\BudgetResolver;
use Subhashladumor1\LaravelAiGuard\Cost\CostCalculator;
use Subhashladumor1\LaravelAiGuard\Cost\TokenEstimator;
use Subhashladumor1\LaravelAiGuard\Models\AiUsage;
use Illuminate\Support\Facades\Config;

class GuardManager
{
    public function __construct(
        protected CostCalculator $costCalculator,
        protected TokenEstimator $tokenEstimator,
        protected BudgetResolver $budgetResolver,
        protected BudgetEnforcer $budgetEnforcer
    ) {
    }

    public function isDisabled(): bool
    {
        return (bool) Config::get('ai-guard.ai_disabled', false);
    }

    public function record(array $data): AiUsage
    {
        $provider = $data['provider'] ?? config('ai-guard.default_provider', 'openai');
        $model = $data['model'] ?? config('ai-guard.default_model', 'gpt-4o');
        $inputTokens = (int) ($data['input_tokens'] ?? 0);
        $outputTokens = (int) ($data['output_tokens'] ?? 0);

        $cost = array_key_exists('cost', $data)
            ? (float) $data['cost']
            : $this->computeCost($provider, $model, $data, $inputTokens, $outputTokens);

        return AiUsage::create([
            'provider' => $provider,
            'model' => $model,
            'input_tokens' => $inputTokens,
            'output_tokens' => $outputTokens,
            'cost' => $cost,
            'user_id' => $data['user_id'] ?? null,
            'tenant_id' => $data['tenant_id'] ?? null,
            'tag' => $data['tag'] ?? $data['feature'] ?? null,
            'meta' => $data['meta'] ?? null,
        ]);
    }

    /**
     * Compute cost from data: uses extended usage when present (audio, video, image, tools, etc.), else standard tokens.
     */
    protected function computeCost(string $provider, string $model, array $data, int $inputTokens, int $outputTokens): float
    {
        $usage = $data['usage'] ?? null;
        if (is_array($usage)) {
            $usage['input_tokens'] = $usage['input_tokens'] ?? $inputTokens;
            $usage['output_tokens'] = $usage['output_tokens'] ?? $outputTokens;
            $usage['cached_input_tokens'] = $usage['cached_input_tokens'] ?? $data['cached_input_tokens'] ?? 0;
            $usage['cache_write_tokens'] = $usage['cache_write_tokens'] ?? $data['cache_write_tokens'] ?? 0;
            return $this->costCalculator->calculateWithUsage($provider, $model, $usage, $data['pricing'] ?? null);
        }
        $cached = (int) ($data['cached_input_tokens'] ?? 0);
        $cacheWrite = (int) ($data['cache_write_tokens'] ?? 0);
        return $this->costCalculator->calculate($provider, $model, $inputTokens, $outputTokens, $data['pricing'] ?? null, $cached, $cacheWrite);
    }

    public function recordAndApplyBudget(array $data): AiUsage
    {
        $usage = $this->record($data);
        $cost = $usage->cost;
        if ($cost > 0) {
            $this->budgetEnforcer->addUsage(
                $usage->user_id,
                $usage->tenant_id,
                $cost
            );
        }
        return $usage;
    }

    public function checkBudget(string $scope, string|int|null $id = null): void
    {
        if ($this->isDisabled()) {
            return;
        }
        $this->budgetEnforcer->checkBudget($scope, $id !== null ? (string) $id : null);
    }

    /**
     * Helper to extract usage from various response objects (Laravel AI, OpenAI, etc.)
     * and record it with calculated cost. Use $pricing to override cost calculation per model.
     *
     * @param  array{input?: float, output?: float}|null  $pricing  Optional cost per 1k tokens for this provider/model
     */
    public function recordFromResponse(
        mixed $response,
        ?int $userId = null,
        ?string $tenantId = null,
        ?string $provider = null,
        ?string $model = null,
        ?string $tag = null,
        ?array $pricing = null
    ): AiUsage {
        $inputTokens = 0;
        $outputTokens = 0;

        // Try to detect usage via Reflection/Inspection
        $usage = null;

        if (is_object($response)) {
            if (property_exists($response, 'usage')) {
                $usage = $response->usage;
            } elseif (method_exists($response, 'usage')) {
                $usage = $response->usage();
            }
        } elseif (is_array($response)) {
            $usage = $response['usage'] ?? null;
        }

        if ($usage) {
            // Normalize usage to array
            $usageArray = is_object($usage) ? (array) $usage : $usage;

            // Extract basic tokens
            $totalInput = (int) ($usageArray['promptTokens']
                ?? $usageArray['prompt_tokens']
                ?? $usageArray['input_tokens']
                ?? $usageArray['promptTokenCount'] // Gemini
                ?? 0);

            $outputTokens = (int) ($usageArray['completionTokens']
                ?? $usageArray['completion_tokens']
                ?? $usageArray['output_tokens']
                ?? $usageArray['candidatesTokenCount'] // Gemini
                ?? 0);

            // Extract Cache Details
            $cachedInputTokens = 0;
            $cacheWriteTokens = 0;

            // OpenAI: prompt_tokens_details.cached_tokens
            if (isset($usageArray['prompt_tokens_details']['cached_tokens'])) {
                $cachedInputTokens = (int) $usageArray['prompt_tokens_details']['cached_tokens'];
            }
            // Also check object property access for OpenAI SDK objects
            elseif (isset($usageArray['prompt_tokens_details']) && is_object($usageArray['prompt_tokens_details'])) {
                if (isset($usageArray['prompt_tokens_details']->cached_tokens)) {
                    $cachedInputTokens = (int) $usageArray['prompt_tokens_details']->cached_tokens;
                }
            }

            // Anthropic: cache_creation_input_tokens, cache_read_input_tokens
            if (isset($usageArray['cache_creation_input_tokens'])) {
                $cacheWriteTokens = (int) $usageArray['cache_creation_input_tokens'];
            }
            if (isset($usageArray['cache_read_input_tokens'])) {
                $cachedInputTokens = (int) $usageArray['cache_read_input_tokens'];
            }

            // Gemini: cachedContentTokenCount (found in usageMetadata usually, but we might have usageMetadata flattened or passed differently)
            // If the response is raw Gemini response, usageMetadata is at root, but here we assume $usage is the usage block.
            // In Gemini PHP SDK/REST, usageMetadata contains promptTokenCount etc.
            if (isset($usageArray['cachedContentTokenCount'])) {
                $cachedInputTokens = (int) $usageArray['cachedContentTokenCount'];
            }

            // Calculate Standard Input (Non-Cached)
            // Standard = Total - Cached - Write (if Write is included in Total)
            // Note: For Anthropic, input_tokens includes creation and read.
            // For OpenAI, prompt_tokens includes cached.
            // For Gemini, promptTokenCount includes cached.
            $standardInputTokens = max(0, $totalInput - $cachedInputTokens - $cacheWriteTokens);

            // Re-assign for recording (we record total input usually, but cost needs breakdown)
            $inputTokens = $totalInput;
        }

        // Apply defaults
        $provider = $provider ?? config('ai-guard.default_provider', 'openai');
        $model = $model ?? config('ai-guard.default_model', 'gpt-4o');

        // Calculate Cost (uses $pricing override if provided, else config/runtime pricing)
        $cost = $this->costCalculator->calculate(
            $provider,
            $model,
            (int) ($standardInputTokens ?? $inputTokens), // Pass Standard Input (cache miss)
            (int) $outputTokens,
            $pricing,
            (int) $cachedInputTokens,
            (int) $cacheWriteTokens
        );

        return $this->recordAndApplyBudget([
            'provider' => $provider,
            'model' => $model,
            'input_tokens' => (int) $inputTokens,
            'output_tokens' => (int) $outputTokens,
            'cost' => $cost,
            'user_id' => $userId,
            'tenant_id' => $tenantId,
            'tag' => $tag,
            'meta' => [
                'cached_input_tokens' => $cachedInputTokens > 0 ? $cachedInputTokens : null,
                'cache_write_tokens' => $cacheWriteTokens > 0 ? $cacheWriteTokens : null,
            ],
        ]);
    }

    public function checkAllBudgets(?int $userId = null, ?string $tenantId = null): void
    {
        if ($this->isDisabled()) {
            return;
        }
        $this->budgetEnforcer->checkAll($userId, $tenantId);
    }

    /**
     * Estimate tokens and cost for a prompt. Supports multiple AI models via model/provider args
     * and optional per-call pricing override (no config change needed).
     *
     * @param  array{input?: float, output?: float}|null  $pricing  Optional cost per 1k tokens; overrides config/runtime for this call
     */
    public function estimate(
        string $prompt,
        ?string $model = null,
        ?string $provider = null,
        ?array $pricing = null
    ): array {
        $provider = $provider ?? config('ai-guard.default_provider', 'openai');
        $model = $model ?? config('ai-guard.default_model', 'gpt-4o');
        $inputTokens = $this->tokenEstimator->estimate($prompt);
        $estimation = config('ai-guard.estimation', []);
        $outputMultiplier = $estimation['default_output_multiplier'] ?? 0.5;
        $outputTokens = (int) ceil($inputTokens * $outputMultiplier);
        $estimatedCost = $this->costCalculator->estimateCost($provider, $model, $inputTokens, $outputTokens, $pricing);

        return [
            'estimated_tokens' => $inputTokens + $outputTokens,
            'estimated_input_tokens' => $inputTokens,
            'estimated_output_tokens' => $outputTokens,
            'estimated_cost' => round($estimatedCost, 6),
            'model' => $model,
            'provider' => $provider,
        ];
    }

    public function getCostCalculator(): CostCalculator
    {
        return $this->costCalculator;
    }

    public function getTokenEstimator(): TokenEstimator
    {
        return $this->tokenEstimator;
    }

    public function getBudgetResolver(): BudgetResolver
    {
        return $this->budgetResolver;
    }

    public function getBudgetEnforcer(): BudgetEnforcer
    {
        return $this->budgetEnforcer;
    }
}
