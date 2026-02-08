<?php

declare(strict_types=1);

namespace Subhashladumor1\LaravelAiGuard\Facades;

use Illuminate\Support\Facades\Facade;
use Subhashladumor1\LaravelAiGuard\Models\AiUsage;

/**
 * @method static bool isDisabled()
 * @method static AiUsage record(array $data)
 * @method static AiUsage recordAndApplyBudget(array $data)
 * @method static AiUsage recordFromResponse(mixed $response, ?int $userId = null, ?string $tenantId = null, ?string $provider = null, ?string $model = null, ?string $tag = null)
 * @method static void checkBudget(string $scope, string|int|null $id = null)
 * @method static void checkAllBudgets(?int $userId = null, ?string $tenantId = null)
 * @method static array estimate(string $prompt, ?string $model = null, ?string $provider = null)
 * @method static \Subhashladumor1\LaravelAiGuard\Cost\CostCalculator getCostCalculator()
 * @method static \Subhashladumor1\LaravelAiGuard\Cost\TokenEstimator getTokenEstimator()
 * @method static \Subhashladumor1\LaravelAiGuard\Budget\BudgetResolver getBudgetResolver()
 * @method static \Subhashladumor1\LaravelAiGuard\Budget\BudgetEnforcer getBudgetEnforcer()
 *
 * @see \Subhashladumor1\LaravelAiGuard\GuardManager
 */
class AIGuard extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Subhashladumor1\LaravelAiGuard\GuardManager::class;
    }
}
