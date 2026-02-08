<?php

declare(strict_types=1);

return [
    'exception' => [
        'budget_exceeded' => 'Превышен бюджет ИИ для области ":scope":scope_id_display. Лимит: :limit, Использовано: :used',
        'ai_disabled' => 'Функции ИИ в настоящее время отключены.',
    ],
    'middleware' => [
        'ai_disabled' => 'ИИ отключён',
        'budget_exceeded' => 'Превышен бюджет ИИ',
    ],
    'report' => [
        'title' => '=== Отчёт Laravel AI Guard ===',
        'metric' => 'Метрика',
        'value' => 'Значение',
        'total_cost' => 'Общая стоимость (USD)',
        'total_input_tokens' => 'Всего входных токенов',
        'total_output_tokens' => 'Всего выходных токенов',
        'total_requests' => 'Всего запросов',
        'by_model' => 'По модели:',
        'by_tag' => 'По тегу:',
        'top_users' => 'Топ пользователей по затратам:',
        'untagged' => 'без тега',
        'requests_count' => ':count запросов',
    ],
    'command' => [
        'report_description' => 'Показать сводку использования и затрат ИИ',
        'reset_description' => 'Сбросить использование бюджета ИИ за истёкшие периоды',
        'would_reset' => 'Будет сброшено: :scope/:scope_id/:period',
        'dry_run_suffix' => ' (пробный запуск)',
        'dry_run_message' => 'Пробный запуск: будет сброшено :count бюджет(ов).',
        'reset_success' => 'Сброшено :count бюджет(ов).',
    ],
];
