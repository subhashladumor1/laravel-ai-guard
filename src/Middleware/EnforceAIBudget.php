<?php

declare(strict_types=1);

namespace Subhashladumor1\LaravelAiGuard\Middleware;

use Closure;
use Illuminate\Http\Request;
use Subhashladumor1\LaravelAiGuard\Exceptions\BudgetExceededException;
use Subhashladumor1\LaravelAiGuard\Facades\AIGuard;
use Symfony\Component\HttpFoundation\Response;

class EnforceAIBudget
{
    public function handle(Request $request, Closure $next): Response
    {
        if (AIGuard::isDisabled()) {
            return response()->json(['message' => __('ai-guard::messages.middleware.ai_disabled')], 503);
        }

        $userId = $request->user()?->getAuthIdentifier();
        $tenantId = $request->attributes->get('tenant_id')
            ?? $request->header('X-Tenant-ID')
            ?? ($request->user()?->tenant_id ?? null);

        try {
            AIGuard::checkAllBudgets($userId, $tenantId !== null ? (string) $tenantId : null);
        } catch (BudgetExceededException $e) {
            return response()->json([
                'message' => __('ai-guard::messages.middleware.budget_exceeded'),
                'error' => $e->getMessage(),
            ], 402);
        }

        return $next($request);
    }
}
