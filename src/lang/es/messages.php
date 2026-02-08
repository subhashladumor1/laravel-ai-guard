<?php

declare(strict_types=1);

return [
    'exception' => [
        'budget_exceeded' => 'Presupuesto de IA excedido para el ámbito ":scope":scope_id_display. Límite: :limit, Usado: :used',
        'ai_disabled' => 'Las funciones de IA están actualmente deshabilitadas.',
    ],
    'middleware' => [
        'ai_disabled' => 'La IA está deshabilitada',
        'budget_exceeded' => 'Presupuesto de IA excedido',
    ],
    'report' => [
        'title' => '=== Informe Laravel AI Guard ===',
        'metric' => 'Métrica',
        'value' => 'Valor',
        'total_cost' => 'Costo total (USD)',
        'total_input_tokens' => 'Total de tokens de entrada',
        'total_output_tokens' => 'Total de tokens de salida',
        'total_requests' => 'Total de solicitudes',
        'by_model' => 'Por modelo:',
        'by_tag' => 'Por etiqueta:',
        'top_users' => 'Principales usuarios por costo:',
        'untagged' => 'sin etiqueta',
        'requests_count' => ':count solicitudes',
    ],
    'command' => [
        'report_description' => 'Mostrar resumen de uso y costo de IA',
        'reset_description' => 'Restablecer el uso del presupuesto de IA para períodos vencidos',
        'would_reset' => 'Se restablecería: :scope/:scope_id/:period',
        'dry_run_suffix' => ' (simulación)',
        'dry_run_message' => 'Simulación: se restablecerían :count presupuesto(s).',
        'reset_success' => 'Se restablecieron :count presupuesto(s).',
    ],
];
