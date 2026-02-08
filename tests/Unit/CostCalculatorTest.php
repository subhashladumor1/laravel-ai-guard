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
}
