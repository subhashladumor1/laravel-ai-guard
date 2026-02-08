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
            : $this->costCalculator->calculate($provider, $model, $inputTokens, $outputTokens, $data['pricing'] ?? null);

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
            if (is_object($usage)) {
                $inputTokens = $usage->promptTokens ?? $usage->prompt_tokens ?? $usage->input_tokens ?? 0;
                $outputTokens = $usage->completionTokens ?? $usage->completion_tokens ?? $usage->output_tokens ?? 0;
            } elseif (is_array($usage)) {
                $inputTokens = $usage['promptTokens'] ?? $usage['prompt_tokens'] ?? $usage['input_tokens'] ?? 0;
                $outputTokens = $usage['completionTokens'] ?? $usage['completion_tokens'] ?? $usage['output_tokens'] ?? 0;
            }
        }

        // Apply defaults
        $provider = $provider ?? config('ai-guard.default_provider', 'openai');
        $model = $model ?? config('ai-guard.default_model', 'gpt-4o');

        // Calculate Cost (uses $pricing override if provided, else config/runtime pricing)
        $cost = $this->costCalculator->calculate(
            $provider,
            $model,
            (int) $inputTokens,
            (int) $outputTokens,
            $pricing
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
