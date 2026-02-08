<?php

declare(strict_types=1);

namespace Subhashladumor1\LaravelAiGuard\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Orchestra\Testbench\TestCase as Orchestra;
use Subhashladumor1\LaravelAiGuard\AiGuardServiceProvider;

abstract class TestCase extends Orchestra
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function getPackageProviders($app): array
    {
        return [
            AiGuardServiceProvider::class,
        ];
    }

    protected function getPackageAliases($app): array
    {
        return [
            'AIGuard' => \Subhashladumor1\LaravelAiGuard\Facades\AIGuard::class,
        ];
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../src/database/migrations');
    }
}
