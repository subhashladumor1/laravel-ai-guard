<?php

declare(strict_types=1);

return [
    'exception' => [
        'budget_exceeded' => 'AI 预算已超出范围 ":scope":scope_id_display。限额：:limit，已用：:used',
        'ai_disabled' => 'AI 功能当前已禁用。',
    ],
    'middleware' => [
        'ai_disabled' => 'AI 已禁用',
        'budget_exceeded' => 'AI 预算已超出',
    ],
    'report' => [
        'title' => '=== Laravel AI Guard 报告 ===',
        'metric' => '指标',
        'value' => '值',
        'total_cost' => '总成本 (USD)',
        'total_input_tokens' => '输入 token 总数',
        'total_output_tokens' => '输出 token 总数',
        'total_requests' => '请求总数',
        'by_model' => '按模型：',
        'by_tag' => '按标签：',
        'top_users' => '按成本排名前几位用户：',
        'untagged' => '未标记',
        'requests_count' => ':count 次请求',
    ],
    'command' => [
        'report_description' => '显示 AI 使用与成本摘要',
        'reset_description' => '重置已过期时段的 AI 预算使用量',
        'would_reset' => '将重置：:scope/:scope_id/:period',
        'dry_run_suffix' => '（试运行）',
        'dry_run_message' => '试运行：将重置 :count 个预算。',
        'reset_success' => '已重置 :count 个预算。',
    ],
];
