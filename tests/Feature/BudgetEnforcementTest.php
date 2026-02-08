<?php

declare(strict_types=1);

namespace Subhashladumor1\LaravelAiGuard\Tests\Feature;

use Subhashladumor1\LaravelAiGuard\Exceptions\BudgetExceededException;
use Subhashladumor1\LaravelAiGuard\Models\AiBudget;
use Subhashladumor1\LaravelAiGuard\Tests\TestCase;

class BudgetEnforcementTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config([
            'ai-guard.budgets.user' => ['limit' => 1.0, 'period' => 'monthly'],
            'ai-guard.budgets.global' => ['limit' => 10.0, 'period' => 'monthly'],
        ]);
    }

    public function test_check_budget_passes_when_under_limit(): void
    {
        \Subhashladumor1\LaravelAiGuard\Facades\AIGuard::checkBudget('user', 1);
        $this->addToAssertionCount(1);
    }

    public function test_check_budget_throws_when_exceeded(): void
    {
        AiBudget::create([
            'scope' => 'user',
            'scope_id' => '1',
            'limit' => 1.0,
            'used' => 1.0,
            'period' => 'monthly',
            'resets_at' => now()->addMonth(),
        ]);

        $this->expectException(BudgetExceededException::class);
        \Subhashladumor1\LaravelAiGuard\Facades\AIGuard::checkBudget('user', '1');
    }

    public function test_check_all_budgets_throws_on_first_exceeded(): void
    {
        AiBudget::create([
            'scope' => 'user',
            'scope_id' => '99',
            'limit' => 0.5,
            'used' => 0.6,
            'period' => 'monthly',
            'resets_at' => now()->addMonth(),
        ]);

        $this->expectException(BudgetExceededException::class);
        \Subhashladumor1\LaravelAiGuard\Facades\AIGuard::checkAllBudgets(99, null);
    }

    public function test_budget_exceeded_exception_message(): void
    {
        $e = new BudgetExceededException('user', '1', 10.0, 11.0);
        $this->assertStringContainsString('user', $e->getMessage());
        $this->assertStringContainsString('10', $e->getMessage());
        $this->assertSame(402, $e->getCode());
    }
}
