<?php

declare(strict_types=1);

return [
    'exception' => [
        'budget_exceeded' => 'KI-Budget für Bereich ":scope":scope_id_display überschritten. Limit: :limit, Verbraucht: :used',
        'ai_disabled' => 'KI-Funktionen sind derzeit deaktiviert.',
    ],
    'middleware' => [
        'ai_disabled' => 'KI ist deaktiviert',
        'budget_exceeded' => 'KI-Budget überschritten',
    ],
    'report' => [
        'title' => '=== Laravel AI Guard Bericht ===',
        'metric' => 'Metrik',
        'value' => 'Wert',
        'total_cost' => 'Gesamtkosten (USD)',
        'total_input_tokens' => 'Eingabe-Tokens gesamt',
        'total_output_tokens' => 'Ausgabe-Tokens gesamt',
        'total_requests' => 'Anfragen gesamt',
        'by_model' => 'Nach Modell:',
        'by_tag' => 'Nach Tag:',
        'top_users' => 'Top-Nutzer nach Kosten:',
        'untagged' => 'ohne Tag',
        'requests_count' => ':count Anfragen',
    ],
    'command' => [
        'report_description' => 'KI-Nutzungs- und Kostenübersicht anzeigen',
        'reset_description' => 'KI-Budgetnutzung für abgelaufene Zeiträume zurücksetzen',
        'would_reset' => 'Würde zurücksetzen: :scope/:scope_id/:period',
        'dry_run_suffix' => ' (Probelauf)',
        'dry_run_message' => 'Probelauf: :count Budget(s) würden zurückgesetzt.',
        'reset_success' => ':count Budget(s) zurückgesetzt.',
    ],
];
