<?php

declare(strict_types=1);

namespace Subhashladumor1\LaravelAiGuard\Tests\Unit;

use Subhashladumor1\LaravelAiGuard\Cost\CostCalculator;
use Subhashladumor1\LaravelAiGuard\Tests\Unit\UnitTestCase;

class CostCalculatorTest extends UnitTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config([
            'ai-guard.pricing.openai.gpt-4o' => [
                'input' => 0.0025,
                'output' => 0.01,
            ],
        ]);
    }

    public function test_calculates_cost_correctly(): void
    {
        $calc = new CostCalculator();
        $cost = $calc->calculate('openai', 'gpt-4o', 1000, 500);
        $expected = 0.0025 * 1 + 0.01 * 0.5;
        $this->assertEqualsWithDelta($expected, $cost, 0.000001);
    }

    public function test_returns_zero_for_unknown_model(): void
    {
        $calc = new CostCalculator();
        $cost = $calc->calculate('openai', 'unknown-model', 1000, 500);
        $this->assertSame(0.0, $cost);
    }

    public function test_estimate_cost_matches_calculate(): void
    {
        $calc = new CostCalculator();
        $estimated = $calc->estimateCost('openai', 'gpt-4o', 400, 250);
        $direct = $calc->calculate('openai', 'gpt-4o', 400, 250);
        $this->assertEqualsWithDelta($direct, $estimated, 0.000001);
    }

    public function test_pricing_override_per_call(): void
    {
        $calc = new CostCalculator();
        $override = ['input' => 0.001, 'output' => 0.002];
        $cost = $calc->calculate('openai', 'any-model', 1000, 1000, $override);
        $this->assertEqualsWithDelta(0.001 + 0.002, $cost, 0.000001);
    }

    public function test_runtime_pricing_registry(): void
    {
        $calc = new CostCalculator();
        $calc->setPricing('custom', 'my-model', ['input' => 0.0005, 'output' => 0.001]);
        $cost = $calc->calculate('custom', 'my-model', 2000, 1000);
        $this->assertEqualsWithDelta(0.0005 * 2 + 0.001 * 1, $cost, 0.000001);
    }

    public function test_runtime_pricing_overrides_config(): void
    {
        $calc = new CostCalculator();
        $calc->setPricing('openai', 'gpt-4o', ['input' => 0.001, 'output' => 0.002]);
        $cost = $calc->calculate('openai', 'gpt-4o', 1000, 1000);
        $this->assertEqualsWithDelta(0.003, $cost, 0.000001);
    }

    public function test_remove_pricing_falls_back_to_config(): void
    {
        $calc = new CostCalculator();
        $calc->setPricing('openai', 'gpt-4o', ['input' => 0.001, 'output' => 0.002]);
        $this->assertEqualsWithDelta(0.003, $calc->calculate('openai', 'gpt-4o', 1000, 1000), 0.000001);

        $calc->removePricing('openai', 'gpt-4o');
        $cost = $calc->calculate('openai', 'gpt-4o', 1000, 500);
        $this->assertEqualsWithDelta(0.0025 * 1 + 0.01 * 0.5, $cost, 0.000001);
    }

    public function test_remove_pricing_returns_zero_for_unknown_model(): void
    {
        $calc = new CostCalculator();
        $calc->setPricing('custom', 'my-model', ['input' => 0.001, 'output' => 0.002]);
        $this->assertEqualsWithDelta(0.003, $calc->calculate('custom', 'my-model', 1000, 1000), 0.000001);

        $calc->removePricing('custom', 'my-model');
        $cost = $calc->calculate('custom', 'my-model', 1000, 1000);
        $this->assertSame(0.0, $cost);
    }

    public function test_calculates_cost_with_cached_input(): void
    {
        config([
            'ai-guard.pricing.openai.gpt-4o-cached' => [
                'input' => 0.0025,
                'output' => 0.01,
                'cached_input' => 0.00125,
            ],
        ]);
        $calc = new CostCalculator();
        // 1k input (miss), 1k input (hit), 1k output
        $cost = $calc->calculate('openai', 'gpt-4o-cached', 1000, 1000, null, 1000);

        $expected = (0.0025 * 1) + (0.01 * 1) + (0.00125 * 1);
        $this->assertEqualsWithDelta($expected, $cost, 0.000001);
    }

    public function test_calculates_cost_with_cache_write(): void
    {
        config([
            'ai-guard.pricing.anthropic.claude-3-5-sonnet' => [
                'input' => 0.003,
                'output' => 0.015,
                'cache_write' => 0.00375,
                'cached_input' => 0.0003,
            ],
        ]);
        $calc = new CostCalculator();
        // 1k input (miss), 1k cache write, 1k cache read, 1k output
        $cost = $calc->calculate('anthropic', 'claude-3-5-sonnet', 1000, 1000, null, 1000, 1000);

        $expected = (0.003 * 1) + (0.015 * 1) + (0.00375 * 1) + (0.0003 * 1);
        $this->assertEqualsWithDelta($expected, $cost, 0.000001);
    }

    public function test_calculate_with_usage_modality_image_and_web_search(): void
    {
        config([
            'ai-guard.pricing.openai.gpt-4o' => [
                'input' => 0.0025,
                'output' => 0.01,
                'per_image' => 0.04,
                'per_1k_web_search' => 10.0,
            ],
        ]);
        $calc = new CostCalculator();
        $cost = $calc->calculateWithUsage('openai', 'gpt-4o', [
            'input_tokens' => 1000,
            'output_tokens' => 500,
            'images_generated' => 2,
            'web_search_calls' => 100,
        ]);
        $expected = (0.0025 * 1) + (0.01 * 0.5) + (2 * 0.04) + (100 / 1000 * 10);
        $this->assertEqualsWithDelta($expected, $cost, 0.000001);
    }

    public function test_calculate_with_usage_long_context_uses_premium_rates(): void
    {
        config([
            'ai-guard.pricing.anthropic.claude-sonnet-4-5' => [
                'input' => 0.003,
                'output' => 0.015,
                'input_long' => 0.006,
                'output_long' => 0.0225,
            ],
        ]);
        $calc = new CostCalculator();
        $cost = $calc->calculateWithUsage('anthropic', 'claude-sonnet-4-5', [
            'input_tokens' => 250000,
            'output_tokens' => 1000,
            'long_context' => true,
        ]);
        $expected = (0.006 * 250) + (0.0225 * 1);
        $this->assertEqualsWithDelta($expected, $cost, 0.000001);
    }

    public function test_openai_gpt4o_audio_calculation(): void
    {
        config([
            'ai-guard.pricing.openai.gpt-4o' => [
                'input' => 0.005,
                'output' => 0.02,
                'audio_in' => 0.01,
                'audio_out' => 0.04,
            ],
        ]);
        $calc = new CostCalculator();
        $cost = $calc->calculateWithUsage('openai', 'gpt-4o', [
            'input_tokens' => 1500, // 500 input text + 1000 audio input
            'output_tokens' => 700, // 200 output text + 500 audio output
            'audio_tokens_in' => 1000,
            'audio_tokens_out' => 500,
        ]);
        // Text Input: 1500 - 1000 = 500. 0.5 * 0.005 = 0.0025
        // Text Output: 700 - 500 = 200. 0.2 * 0.02 = 0.004
        // Audio In: 1.0 * 0.01 = 0.01
        // Audio Out: 0.5 * 0.04 = 0.02
        // Total: 0.0365
        $this->assertEqualsWithDelta(0.0365, $cost, 0.000001);
    }

    public function test_anthropic_claude_3_5_sonnet_caching(): void
    {
        config([
            'ai-guard.pricing.anthropic.claude-3-5-sonnet' => [
                'input' => 0.003,
                'output' => 0.015,
                'cache_write' => 0.00375,
                'cached_input' => 0.0003,
            ],
        ]);
        $calc = new CostCalculator();
        // Total Input = Standard + Cached + Write
        // Standard Input = Total (5000) - Cached (2000) - Write (1000) = 2000
        $cost = $calc->calculateWithUsage('anthropic', 'claude-3-5-sonnet', [
            'input_tokens' => 5000,
            'output_tokens' => 1000,
            'cached_input_tokens' => 2000,
            'cache_write_tokens' => 1000,
        ]);

        // Standard: 2 * 0.003 = 0.006
        // Cached: 2 * 0.0003 = 0.0006
        // Write: 1 * 0.00375 = 0.00375
        // Output: 1 * 0.015 = 0.015
        // Total: 0.006 + 0.0006 + 0.00375 + 0.015 = 0.02535
        $this->assertEqualsWithDelta(0.02535, $cost, 0.000001);
    }

    public function test_gemini_long_context(): void
    {
        config([
            'ai-guard.pricing.gemini.gemini-3-pro' => [
                'input' => 0.002,
                'output' => 0.012,
                'input_long' => 0.004,
                'output_long' => 0.018,
            ],
        ]);
        $calc = new CostCalculator();
        $cost = $calc->calculateWithUsage('gemini', 'gemini-3-pro', [
            'input_tokens' => 250000,
            'output_tokens' => 5000,
            'long_context' => true,
        ]);
        // Long context triggered
        // 250 * 0.004 = 1.0
        // 5 * 0.018 = 0.09
        // Total: 1.09
        $this->assertEqualsWithDelta(1.09, $cost, 0.000001);
    }

    public function test_mistral_standard_text(): void
    {
        config([
            'ai-guard.pricing.mistral.mistral-large-latest' => [
                'input' => 0.003,
                'output' => 0.009,
            ],
        ]);
        $calc = new CostCalculator();
        $cost = $calc->calculate('mistral', 'mistral-large-latest', 1000, 1000);
        // 1 * 0.003 + 1 * 0.009 = 0.012
        $this->assertEqualsWithDelta(0.012, $cost, 0.000001);
    }

    public function test_deepseek_reasoner_caching(): void
    {
        config([
            'ai-guard.pricing.deepseek.deepseek-reasoner' => [
                'input' => 0.00028,
                'output' => 0.00042,
                'cached_input' => 0.000028,
            ],
        ]);
        $calc = new CostCalculator();
        $cost = $calc->calculateWithUsage('deepseek', 'deepseek-reasoner', [
            'input_tokens' => 10000,
            'output_tokens' => 2000,
            'cached_input_tokens' => 8000,
        ]);
        // Standard input: 10000 - 8000 = 2000
        // 2 * 0.00028 = 0.00056
        // Cached: 8 * 0.000028 = 0.000224
        // Output: 2 * 0.00042 = 0.00084
        // Total: 0.00056 + 0.000224 + 0.00084 = 0.001624
        $this->assertEqualsWithDelta(0.001624, $cost, 0.000001);
    }

    public function test_xai_grok_web_search(): void
    {
        config([
            'ai-guard.pricing.xai.grok-4' => [
                'input' => 0.003,
                'output' => 0.015,
                'per_1k_web_search' => 5.0,
            ],
        ]);
        $calc = new CostCalculator();
        $cost = $calc->calculateWithUsage('xai', 'grok-4', [
            'input_tokens' => 1000,
            'output_tokens' => 500,
            'web_search_calls' => 200, // 0.2k calls
        ]);
        // Text: 0.003 * 1 + 0.015 * 0.5 = 0.003 + 0.0075 = 0.0105
        // Search: 0.2 * 5.0 = 1.0
        // Total: 1.0105
        $this->assertEqualsWithDelta(1.0105, $cost, 0.000001);
    }
}
