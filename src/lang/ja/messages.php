<?php

declare(strict_types=1);

return [
    'exception' => [
        'budget_exceeded' => 'スコープ「:scope」:scope_id_display のAI予算を超過しました。上限: :limit、使用: :used',
        'ai_disabled' => 'AI機能は現在無効です。',
    ],
    'middleware' => [
        'ai_disabled' => 'AIは無効です',
        'budget_exceeded' => 'AI予算を超過しました',
    ],
    'report' => [
        'title' => '=== Laravel AI Guard レポート ===',
        'metric' => '指標',
        'value' => '値',
        'total_cost' => '総コスト (USD)',
        'total_input_tokens' => '入力トークン合計',
        'total_output_tokens' => '出力トークン合計',
        'total_requests' => 'リクエスト合計',
        'by_model' => 'モデル別:',
        'by_tag' => 'タグ別:',
        'top_users' => 'コスト別トップユーザー:',
        'untagged' => 'タグなし',
        'requests_count' => ':count リクエスト',
    ],
    'command' => [
        'report_description' => 'AI利用とコストのサマリーを表示',
        'reset_description' => '期限切れ期間のAI予算利用をリセット',
        'would_reset' => 'リセット対象: :scope/:scope_id/:period',
        'dry_run_suffix' => '（ドライラン）',
        'dry_run_message' => 'ドライラン: :count 件の予算がリセットされます。',
        'reset_success' => ':count 件の予算をリセットしました。',
    ],
];
