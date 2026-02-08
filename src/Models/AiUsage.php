<?php

declare(strict_types=1);

namespace Subhashladumor1\LaravelAiGuard\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiUsage extends Model
{
    protected $table = 'ai_usages';

    protected $fillable = [
        'provider',
        'model',
        'input_tokens',
        'output_tokens',
        'cost',
        'user_id',
        'tenant_id',
        'tag',
        'meta',
    ];

    protected $casts = [
        'input_tokens' => 'integer',
        'output_tokens' => 'integer',
        'cost' => 'float',
        'user_id' => 'integer',
        'meta' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model', \App\Models\User::class), 'user_id');
    }
}
