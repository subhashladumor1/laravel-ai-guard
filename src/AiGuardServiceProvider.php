<?php

declare(strict_types=1);

namespace Subhashladumor1\LaravelAiGuard;

use Subhashladumor1\LaravelAiGuard\Budget\BudgetEnforcer;
use Subhashladumor1\LaravelAiGuard\Budget\BudgetResolver;
use Subhashladumor1\LaravelAiGuard\Cost\CostCalculator;
use Subhashladumor1\LaravelAiGuard\Cost\TokenEstimator;
use Subhashladumor1\LaravelAiGuard\Middleware\EnforceAIBudget;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\ServiceProvider;

class AiGuardServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/config/ai-guard.php', 'ai-guard');

        $this->app->singleton(TokenEstimator::class, function () {
            $chars = Config::get('ai-guard.estimation.chars_per_token', 4);
            return new TokenEstimator((int) $chars);
        });

        $this->app->singleton(CostCalculator::class);

        $this->app->singleton(BudgetResolver::class);

        $this->app->singleton(BudgetEnforcer::class, function ($app) {
            return new BudgetEnforcer($app->make(BudgetResolver::class));
        });

        $this->app->singleton(GuardManager::class, function ($app) {
            return new GuardManager(
                $app->make(CostCalculator::class),
                $app->make(TokenEstimator::class),
                $app->make(BudgetResolver::class),
                $app->make(BudgetEnforcer::class)
            );
        });
    }

    public function boot(): void
    {
        $this->loadTranslationsFrom(__DIR__ . '/lang', 'ai-guard');

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/config/ai-guard.php' => config_path('ai-guard.php'),
            ], 'ai-guard-config');

            $this->publishes([
                __DIR__ . '/database/migrations' => database_path('migrations'),
            ], 'ai-guard-migrations');

            $this->publishes([
                __DIR__ . '/lang' => lang_path('vendor/ai-guard'),
            ], 'ai-guard-lang');

            $this->loadMigrationsFrom(__DIR__ . '/database/migrations');

            $this->commands([
                Commands\AiGuardReportCommand::class,
                Commands\AiGuardResetBudgetsCommand::class,
            ]);
        }

        $this->app['router']->aliasMiddleware(
            Config::get('ai-guard.middleware.alias', 'ai.guard'),
            EnforceAIBudget::class
        );
    }
}
