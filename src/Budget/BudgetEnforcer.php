<?php

declare(strict_types=1);

namespace Subhashladumor1\LaravelAiGuard\Budget;

use Subhashladumor1\LaravelAiGuard\Exceptions\BudgetExceededException;
use Subhashladumor1\LaravelAiGuard\Models\AiBudget;

class BudgetEnforcer
{
    public function __construct(
        protected BudgetResolver $resolver
    ) {
    }

    public function checkBudget(string $scope, ?string $id = null): void
    {
        $scopeId = $id !== null ? (string) $id : null;
        $defaults = config('ai-guard.budgets', []);
        $period = $defaults[$scope]['period'] ?? 'monthly';

        $budget = $this->resolver->getForScope($scope, $scopeId, $period);
        if ($budget === null) {
            return;
        }

        if ((float) $budget->used >= (float) $budget->limit) {
            throw new BudgetExceededException(
                $scope,
                $scopeId,
                (float) $budget->limit,
                (float) $budget->used
            );
        }
    }

    public function checkAll(?int $userId = null, ?string $tenantId = null): void
    {
        $budgets = $this->resolver->resolve($userId, $tenantId);
        foreach ($budgets as $budget) {
            if ((float) $budget->used >= (float) $budget->limit) {
                throw new BudgetExceededException(
                    $budget->scope,
                    $budget->scope_id,
                    (float) $budget->limit,
                    (float) $budget->used
                );
            }
        }
    }

    public function addUsage(?int $userId, ?string $tenantId, float $cost): void
    {
        $budgets = $this->resolver->resolve($userId, $tenantId);
        foreach ($budgets as $budget) {
            $budget->increment('used', $cost);
        }
    }
}
