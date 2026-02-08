<?php

declare(strict_types=1);

namespace Subhashladumor1\LaravelAiGuard\Exceptions;

use Exception;

class AiDisabledException extends Exception
{
    public function __construct(?string $message = null)
    {
        parent::__construct($message ?? __('ai-guard::messages.exception.ai_disabled'), 403);
    }
}
