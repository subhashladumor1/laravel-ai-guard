<?php

declare(strict_types=1);

return [
    'exception' => [
        'budget_exceeded' => 'Budget IA dépassé pour le périmètre ":scope":scope_id_display. Limite : :limit, Utilisé : :used',
        'ai_disabled' => 'Les fonctionnalités IA sont actuellement désactivées.',
    ],
    'middleware' => [
        'ai_disabled' => 'L\'IA est désactivée',
        'budget_exceeded' => 'Budget IA dépassé',
    ],
    'report' => [
        'title' => '=== Rapport Laravel AI Guard ===',
        'metric' => 'Métrique',
        'value' => 'Valeur',
        'total_cost' => 'Coût total (USD)',
        'total_input_tokens' => 'Total des jetons d\'entrée',
        'total_output_tokens' => 'Total des jetons de sortie',
        'total_requests' => 'Total des requêtes',
        'by_model' => 'Par modèle :',
        'by_tag' => 'Par tag :',
        'top_users' => 'Principaux utilisateurs par coût :',
        'untagged' => 'sans tag',
        'requests_count' => ':count requêtes',
    ],
    'command' => [
        'report_description' => 'Afficher le résumé d\'utilisation et de coût IA',
        'reset_description' => 'Réinitialiser l\'utilisation du budget IA pour les périodes expirées',
        'would_reset' => 'Serait réinitialisé : :scope/:scope_id/:period',
        'dry_run_suffix' => ' (simulation)',
        'dry_run_message' => 'Simulation : :count budget(s) seraient réinitialisés.',
        'reset_success' => ':count budget(s) réinitialisé(s).',
    ],
];
