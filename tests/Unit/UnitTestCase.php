<?php

declare(strict_types=1);

namespace Subhashladumor1\LaravelAiGuard\Tests\Unit;

use Orchestra\Testbench\TestCase as Orchestra;
use Subhashladumor1\LaravelAiGuard\AiGuardServiceProvider;

abstract class UnitTestCase extends Orchestra
{
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
}
