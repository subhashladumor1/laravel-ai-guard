<?php

declare(strict_types=1);

return [
    'exception' => [
        'budget_exceeded' => 'Orçamento de IA excedido para o escopo ":scope":scope_id_display. Limite: :limit, Usado: :used',
        'ai_disabled' => 'Os recursos de IA estão atualmente desativados.',
    ],
    'middleware' => [
        'ai_disabled' => 'IA está desativada',
        'budget_exceeded' => 'Orçamento de IA excedido',
    ],
    'report' => [
        'title' => '=== Relatório Laravel AI Guard ===',
        'metric' => 'Métrica',
        'value' => 'Valor',
        'total_cost' => 'Custo total (USD)',
        'total_input_tokens' => 'Total de tokens de entrada',
        'total_output_tokens' => 'Total de tokens de saída',
        'total_requests' => 'Total de solicitações',
        'by_model' => 'Por modelo:',
        'by_tag' => 'Por tag:',
        'top_users' => 'Principais usuários por custo:',
        'untagged' => 'sem tag',
        'requests_count' => ':count solicitações',
    ],
    'command' => [
        'report_description' => 'Exibir resumo de uso e custo de IA',
        'reset_description' => 'Redefinir uso do orçamento de IA para períodos expirados',
        'would_reset' => 'Seria redefinido: :scope/:scope_id/:period',
        'dry_run_suffix' => ' (simulação)',
        'dry_run_message' => 'Simulação: :count orçamento(s) seriam redefinidos.',
        'reset_success' => ':count orçamento(s) redefinido(s).',
    ],
];
