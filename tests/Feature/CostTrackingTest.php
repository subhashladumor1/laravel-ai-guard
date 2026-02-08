<?php

declare(strict_types=1);

namespace Subhashladumor1\LaravelAiGuard\Tests\Feature;

use Subhashladumor1\LaravelAiGuard\Models\AiUsage;
use Subhashladumor1\LaravelAiGuard\Tests\TestCase;

class CostTrackingTest extends TestCase
{
    public function test_record_persists_usage(): void
    {
        \Subhashladumor1\LaravelAiGuard\Facades\AIGuard::record([
            'provider' => 'openai',
            'model' => 'gpt-4o',
            'input_tokens' => 400,
            'output_tokens' => 250,
            'cost' => 0.028,
            'user_id' => 1,
            'tenant_id' => null,
            'tag' => 'chat',
        ]);

        $this->assertDatabaseCount('ai_usages', 1);
        $usage = AiUsage::first();
        $this->assertSame('openai', $usage->provider);
        $this->assertSame('gpt-4o', $usage->model);
        $this->assertSame(400, $usage->input_tokens);
        $this->assertSame(250, $usage->output_tokens);
        $this->assertEqualsWithDelta(0.028, $usage->cost, 0.0001);
        $this->assertSame(1, $usage->user_id);
        $this->assertSame('chat', $usage->tag);
    }

    public function test_record_uses_default_provider_and_model(): void
    {
        config(['ai-guard.default_provider' => 'openai', 'ai-guard.default_model' => 'gpt-4o']);
        \Subhashladumor1\LaravelAiGuard\Facades\AIGuard::record([
            'input_tokens' => 10,
            'output_tokens' => 5,
            'cost' => 0.001,
        ]);

        $usage = AiUsage::first();
        $this->assertSame('openai', $usage->provider);
        $this->assertSame('gpt-4o', $usage->model);
    }

    public function test_record_and_apply_budget_increments_budget_used(): void
    {
        \Subhashladumor1\LaravelAiGuard\Facades\AIGuard::checkAllBudgets(1, null);
        $budget = \Subhashladumor1\LaravelAiGuard\Models\AiBudget::where('scope', 'user')->where('scope_id', '1')->first();
        $this->assertNotNull($budget);
        $usedBefore = (float) $budget->used;

        \Subhashladumor1\LaravelAiGuard\Facades\AIGuard::recordAndApplyBudget([
            'provider' => 'openai',
            'model' => 'gpt-4o',
            'input_tokens' => 100,
            'output_tokens' => 50,
            'cost' => 0.01,
            'user_id' => 1,
            'tag' => 'test',
        ]);

        $budget->refresh();
        $this->assertEqualsWithDelta($usedBefore + 0.01, (float) $budget->used, 0.0001);
    }
}
