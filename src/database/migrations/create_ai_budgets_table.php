<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_budgets', function (Blueprint $table) {
            $table->id();
            $table->string('scope', 32);
            $table->string('scope_id', 64)->nullable();
            $table->decimal('limit', 12, 2);
            $table->decimal('used', 12, 6)->default(0);
            $table->string('period', 32);
            $table->timestamp('resets_at')->nullable();
            $table->timestamps();

            $table->unique(['scope', 'scope_id', 'period']);
            $table->index(['scope', 'scope_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_budgets');
    }
};
