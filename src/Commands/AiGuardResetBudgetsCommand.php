<?php

declare(strict_types=1);

namespace Subhashladumor1\LaravelAiGuard\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class AiGuardResetBudgetsCommand extends Command
{
    protected $signature = 'ai-guard:reset-budgets
                            {--scope= : Scope to reset (global|user|tenant), optional}
                            {--dry-run : Show what would be reset without changing DB}';

    protected $description = 'Reset AI budget usage for expired periods';

    public function __construct()
    {
        parent::__construct();
        $this->description = __('ai-guard::messages.command.reset_description');
    }

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $scopeFilter = $this->option('scope');

        $model = \Subhashladumor1\LaravelAiGuard\Models\AiBudget::query();
        if ($scopeFilter !== null && $scopeFilter !== '') {
            $model->where('scope', $scopeFilter);
        }

        $budgets = $model->get();
        $reset = 0;

        foreach ($budgets as $budget) {
            if ($budget->resets_at !== null && $budget->resets_at->isPast()) {
                if (!$dryRun) {
                    $budget->used = 0;
                    $budget->resets_at = $this->computeResetsAt($budget->period);
                    $budget->save();
                }
                $reset++;
                $this->line(__('ai-guard::messages.command.would_reset', [
                    'scope' => $budget->scope,
                    'scope_id' => $budget->scope_id ?? '',
                    'period' => $budget->period,
                ]) . ($dryRun ? __('ai-guard::messages.command.dry_run_suffix') : ''));
            }
        }

        if ($dryRun) {
            $this->info(__('ai-guard::messages.command.dry_run_message', ['count' => $reset]));
        } else {
            $this->info(__('ai-guard::messages.command.reset_success', ['count' => $reset]));
        }

        return self::SUCCESS;
    }

    private function computeResetsAt(string $period): Carbon
    {
        if ($period === 'daily') {
            return Carbon::tomorrow()->startOfDay();
        }
        return Carbon::now()->addMonth()->firstOfMonth()->startOfDay();
    }
}
