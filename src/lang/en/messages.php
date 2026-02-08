<?php

declare(strict_types=1);

return [
    'exception' => [
        'budget_exceeded' => 'AI budget exceeded for scope ":scope":scope_id_display. Limit: :limit, Used: :used',
        'ai_disabled' => 'AI features are currently disabled.',
    ],
    'middleware' => [
        'ai_disabled' => 'AI is disabled',
        'budget_exceeded' => 'AI budget exceeded',
    ],
    'report' => [
        'title' => '=== Laravel AI Guard Report ===',
        'metric' => 'Metric',
        'value' => 'Value',
        'total_cost' => 'Total cost (USD)',
        'total_input_tokens' => 'Total input tokens',
        'total_output_tokens' => 'Total output tokens',
        'total_requests' => 'Total requests',
        'by_model' => 'By model:',
        'by_tag' => 'By tag:',
        'top_users' => 'Top users by cost:',
        'untagged' => 'untagged',
        'requests_count' => ':count requests',
    ],
    'command' => [
        'report_description' => 'Display AI usage and cost summary',
        'reset_description' => 'Reset AI budget usage for expired periods',
        'would_reset' => 'Would reset: :scope/:scope_id/:period',
        'dry_run_suffix' => ' (dry-run)',
        'dry_run_message' => 'Dry run: :count budget(s) would be reset.',
        'reset_success' => 'Reset :count budget(s).',
    ],
];
