<?php

declare(strict_types=1);

namespace Subhashladumor1\LaravelAiGuard\Models;

use Illuminate\Database\Eloquent\Model;

class AiBudget extends Model
{
    protected $table = 'ai_budgets';

    protected $fillable = [
        'scope',
        'scope_id',
        'limit',
        'used',
        'period',
        'resets_at',
    ];

    protected $casts = [
        'limit' => 'float',
        'used' => 'float',
        'resets_at' => 'datetime',
    ];
}
