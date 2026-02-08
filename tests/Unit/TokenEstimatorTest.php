<?php

declare(strict_types=1);

namespace Subhashladumor1\LaravelAiGuard\Tests\Unit;

use Subhashladumor1\LaravelAiGuard\Cost\TokenEstimator;
use Subhashladumor1\LaravelAiGuard\Tests\TestCase;

class TokenEstimatorTest extends TestCase
{
    public function test_estimates_tokens_by_characters(): void
    {
        $estimator = new TokenEstimator(4);
        $this->assertSame(1, $estimator->estimate('abcd'));
        $this->assertSame(2, $estimator->estimate('abcdefgh'));
        $this->assertSame(3, $estimator->estimate('abcdefghij'));
    }

    public function test_rounds_up_partial_tokens(): void
    {
        $estimator = new TokenEstimator(4);
        $this->assertSame(2, $estimator->estimate('abcde'));
        $this->assertSame(3, $estimator->estimate('abcdefghij'));
    }

    public function test_empty_string_returns_zero(): void
    {
        $estimator = new TokenEstimator(4);
        $this->assertSame(0, $estimator->estimate(''));
    }

    public function test_custom_chars_per_token(): void
    {
        $estimator = new TokenEstimator(2);
        $this->assertSame(5, $estimator->estimate('abcdefghij'));
    }
}
