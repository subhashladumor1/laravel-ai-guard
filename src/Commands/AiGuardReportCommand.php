<?php

declare(strict_types=1);

namespace Subhashladumor1\LaravelAiGuard\Commands;

use Illuminate\Console\Command;
use Subhashladumor1\LaravelAiGuard\Models\AiUsage;

class AiGuardReportCommand extends Command
{
    protected $signature = 'ai-guard:report
                            {--period=day : Period (day|month)}
                            {--days=1 : Days back for day period}';

    protected $description = 'Display AI usage and cost summary';

    public function __construct()
    {
        parent::__construct();
        $this->description = __('ai-guard::messages.command.report_description');
    }

    public function handle(): int
    {
        $period = $this->option('period');
        $days = (int) $this->option('days');

        $query = AiUsage::query();
        if ($period === 'month') {
            $query->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year);
        } else {
            $query->where('created_at', '>=', now()->subDays($days));
        }

        $usages = $query->get();
        $totalCost = $usages->sum('cost');
        $totalInput = $usages->sum('input_tokens');
        $totalOutput = $usages->sum('output_tokens');

        $this->info(__('ai-guard::messages.report.title'));
        $this->newLine();
        $this->table(
            [__('ai-guard::messages.report.metric'), __('ai-guard::messages.report.value')],
            [
                [__('ai-guard::messages.report.total_cost'), sprintf('$%.4f', $totalCost)],
                [__('ai-guard::messages.report.total_input_tokens'), number_format($totalInput)],
                [__('ai-guard::messages.report.total_output_tokens'), number_format($totalOutput)],
                [__('ai-guard::messages.report.total_requests'), (string) $usages->count()],
            ]
        );

        $byModel = $usages->groupBy('model')->map(fn ($g) => [
            'cost' => $g->sum('cost'),
            'count' => $g->count(),
        ])->sortByDesc('cost');
        if ($byModel->isNotEmpty()) {
            $this->newLine();
            $this->info(__('ai-guard::messages.report.by_model'));
            foreach ($byModel as $model => $data) {
                $this->line('  ' . $model . ': $' . number_format($data['cost'], 4) . ' (' . __('ai-guard::messages.report.requests_count', ['count' => $data['count']]) . ')');
            }
        }

        $byTag = $usages->groupBy('tag')->map(fn ($g) => $g->sum('cost'))->filter()->sortByDesc(fn ($v) => $v);
        if ($byTag->isNotEmpty()) {
            $this->newLine();
            $this->info(__('ai-guard::messages.report.by_tag'));
            foreach ($byTag as $tag => $cost) {
                $this->line('  ' . ($tag ?: __('ai-guard::messages.report.untagged')) . ': $' . number_format($cost, 4));
            }
        }

        $byUser = $usages->whereNotNull('user_id')->groupBy('user_id')->map(fn ($g) => $g->sum('cost'))->sortByDesc(fn ($v) => $v)->take(5);
        if ($byUser->isNotEmpty()) {
            $this->newLine();
            $this->info(__('ai-guard::messages.report.top_users'));
            foreach ($byUser as $uid => $cost) {
                $this->line('  user_id=' . $uid . ': $' . number_format($cost, 4));
            }
        }

        $this->newLine();
        return self::SUCCESS;
    }
}
