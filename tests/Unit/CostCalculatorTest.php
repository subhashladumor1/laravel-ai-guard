<?php

declare(strict_types=1);

namespace Subhashladumor1\LaravelAiGuard\Tests\Unit;

use Subhashladumor1\LaravelAiGuard\Cost\CostCalculator;
use Subhashladumor1\LaravelAiGuard\Tests\TestCase;

class CostCalculatorTest extends TestCase
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
}
