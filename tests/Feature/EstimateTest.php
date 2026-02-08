<?php

declare(strict_types=1);

namespace Subhashladumor1\LaravelAiGuard\Tests\Feature;

use Subhashladumor1\LaravelAiGuard\Tests\TestCase;

class EstimateTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config([
            'ai-guard.pricing.openai.gpt-4o' => ['input' => 0.0025, 'output' => 0.01],
            'ai-guard.estimation.default_output_multiplier' => 0.5,
        ]);
    }

    public function test_estimate_returns_structure(): void
    {
        $result = \Subhashladumor1\LaravelAiGuard\Facades\AIGuard::estimate('Explain Laravel AI SDK');
        $this->assertArrayHasKey('estimated_tokens', $result);
        $this->assertArrayHasKey('estimated_cost', $result);
        $this->assertArrayHasKey('model', $result);
        $this->assertArrayHasKey('provider', $result);
        $this->assertArrayHasKey('estimated_input_tokens', $result);
        $this->assertArrayHasKey('estimated_output_tokens', $result);
        $this->assertIsInt($result['estimated_input_tokens']);
        $this->assertIsFloat($result['estimated_cost']);
    }

    public function test_estimate_token_count_reflects_prompt_length(): void
    {
        $short = \Subhashladumor1\LaravelAiGuard\Facades\AIGuard::estimate('Hi');
        $long = \Subhashladumor1\LaravelAiGuard\Facades\AIGuard::estimate(str_repeat('x', 400));
        $this->assertGreaterThan($short['estimated_input_tokens'], $long['estimated_input_tokens']);
    }
}
