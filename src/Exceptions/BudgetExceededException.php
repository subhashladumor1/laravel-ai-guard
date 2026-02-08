<?php

declare(strict_types=1);

namespace Subhashladumor1\LaravelAiGuard\Exceptions;

use Exception;

class BudgetExceededException extends Exception
{
    public function __construct(
        string $scope,
        ?string $scopeId = null,
        float $limit = 0.0,
        float $used = 0.0,
        ?\Throwable $previous = null
    ) {
        $scopeIdDisplay = $scopeId !== null ? ' (id: ' . $scopeId . ')' : '';
        $message = __(
            'ai-guard::messages.exception.budget_exceeded',
            [
                'scope' => $scope,
                'scope_id_display' => $scopeIdDisplay,
                'limit' => number_format((float) $limit, 2),
                'used' => number_format((float) $used, 2),
            ]
        );
        parent::__construct($message, 402, $previous);
    }
}
