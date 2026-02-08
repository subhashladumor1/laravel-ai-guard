<?php

declare(strict_types=1);

namespace Subhashladumor1\LaravelAiGuard\Budget;

use Subhashladumor1\LaravelAiGuard\Models\AiBudget;

class BudgetResolver
{
    public function resolve(?int $userId = null, ?string $tenantId = null): array
    {
        $budgets = [];
        $defaults = config('ai-guard.budgets', []);

        if ($userId !== null && isset($defaults['user'])) {
            $budgets[] = $this->getOrCreate('user', (string) $userId, $defaults['user']);
        }
        if ($tenantId !== null && $tenantId !== '' && isset($defaults['tenant'])) {
            $budgets[] = $this->getOrCreate('tenant', $tenantId, $defaults['tenant']);
        }
        if (isset($defaults['global'])) {
            $budgets[] = $this->getOrCreate('global', null, $defaults['global']);
        }

        return $budgets;
    }

    protected function getOrCreate(string $scope, ?string $scopeId, array $default): AiBudget
    {
        $period = $default['period'] ?? 'monthly';
        $limit = (float) ($default['limit'] ?? 0);

        $budget = AiBudget::where('scope', $scope)
            ->where('scope_id', $scopeId)
            ->where('period', $period)
            ->first();

        if ($budget === null) {
            $resetsAt = $this->computeResetsAt($period);
            $budget = AiBudget::create([
                'scope' => $scope,
                'scope_id' => $scopeId,
                'limit' => $limit,
                'used' => 0,
                'period' => $period,
                'resets_at' => $resetsAt,
            ]);
        } else {
            if ($budget->resets_at !== null && $budget->resets_at->isPast()) {
                $budget->used = 0;
                $budget->resets_at = $this->computeResetsAt($period);
                $budget->save();
            }
        }

        return $budget;
    }

    public function getForScope(string $scope, ?string $scopeId, string $period = 'monthly'): ?AiBudget
    {
        $budget = AiBudget::where('scope', $scope)
            ->where('scope_id', $scopeId)
            ->where('period', $period)
            ->first();

        if ($budget !== null && $budget->resets_at !== null && $budget->resets_at->isPast()) {
            $budget->used = 0;
            $budget->resets_at = $this->computeResetsAt($period);
            $budget->save();
        }

        return $budget;
    }

    protected function computeResetsAt(string $period): \DateTimeImmutable
    {
        $now = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
        if ($period === 'daily') {
            return $now->modify('tomorrow midnight');
        }
        return $now->modify('first day of next month midnight');
    }
}
