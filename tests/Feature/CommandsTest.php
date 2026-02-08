<?php

declare(strict_types=1);

namespace Subhashladumor1\LaravelAiGuard\Tests\Feature;

use Subhashladumor1\LaravelAiGuard\Models\AiUsage;
use Subhashladumor1\LaravelAiGuard\Tests\TestCase;

class CommandsTest extends TestCase
{
    public function test_report_command_runs(): void
    {
        AiUsage::create([
            'provider' => 'openai',
            'model' => 'gpt-4o',
            'input_tokens' => 100,
            'output_tokens' => 50,
            'cost' => 0.01,
            'tag' => 'chat',
        ]);

        $this->artisan('ai-guard:report')
            ->assertSuccessful();
    }

    public function test_report_command_shows_cost(): void
    {
        AiUsage::create([
            'provider' => 'openai',
            'model' => 'gpt-4o',
            'input_tokens' => 100,
            'output_tokens' => 50,
            'cost' => 0.5,
            'tag' => 'chat',
        ]);

        $this->artisan('ai-guard:report')
            ->expectsOutputToContain('0.5000')
            ->assertSuccessful();
    }

    public function test_reset_budgets_command_runs(): void
    {
        $this->artisan('ai-guard:reset-budgets')
            ->assertSuccessful();
    }

    public function test_reset_budgets_dry_run(): void
    {
        $this->artisan('ai-guard:reset-budgets', ['--dry-run' => true])
            ->assertSuccessful();
    }
}
