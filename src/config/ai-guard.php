<?php

declare(strict_types=1);

return [

    'ai_disabled' => env('AI_GUARD_DISABLED', false),

    'pricing' => [
        'openai' => [
            'gpt-4o' => [
                'input' => 0.0025,
                'output' => 0.01,
            ],
            'gpt-4o-mini' => [
                'input' => 0.00015,
                'output' => 0.0006,
            ],
            'gpt-4-turbo' => [
                'input' => 0.01,
                'output' => 0.03,
            ],
            'gpt-3.5-turbo' => [
                'input' => 0.0005,
                'output' => 0.0015,
            ],
        ],
        'anthropic' => [
            'claude-3-5-sonnet-20241022' => [
                'input' => 0.003,
                'output' => 0.015,
            ],
            'claude-3-opus-20240229' => [
                'input' => 0.015,
                'output' => 0.075,
            ],
        ],
        'gemini' => [
            'gemini-1.5-pro' => [
                'input' => 0.0035, // Price for <= 128k context
                'output' => 0.0105,
            ],
            'gemini-1.5-flash' => [
                'input' => 0.000075,
                'output' => 0.0003,
            ],
        ],
        'mistral' => [
            'mistral-large-latest' => [
                'input' => 0.002,
                'output' => 0.006,
            ],
            'mistral-small-latest' => [
                'input' => 0.0002,
                'output' => 0.0006,
            ],
        ],
        'deepseek' => [
            'deepseek-chat' => [
                'input' => 0.00028, // Using cache miss price (conservative)
                'output' => 0.00028,
            ],
            'deepseek-reasoner' => [
                'input' => 0.00055, // R1 pricing
                'output' => 0.00219,
            ],
        ],
    ],

    'default_model' => 'gpt-4o',

    'default_provider' => 'openai',

    'budgets' => [
        'global' => [
            'limit' => env('AI_GUARD_GLOBAL_LIMIT', 100.0),
            'period' => 'monthly',
        ],
        'user' => [
            'limit' => env('AI_GUARD_USER_LIMIT', 10.0),
            'period' => 'monthly',
        ],
        'tenant' => [
            'limit' => env('AI_GUARD_TENANT_LIMIT', 50.0),
            'period' => 'monthly',
        ],
    ],

    'periods' => [
        'daily' => [
            'interval' => 'P1D',
            'resets_at' => 'midnight',
        ],
        'monthly' => [
            'interval' => 'P1M',
            'resets_at' => 'first_of_month',
        ],
    ],

    'estimation' => [
        'chars_per_token' => 4,
        'default_input_multiplier' => 1.0,
        'default_output_multiplier' => 0.5,
    ],

    'middleware' => [
        'alias' => 'ai.guard',
    ],

];
