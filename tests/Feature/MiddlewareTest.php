<?php

declare(strict_types=1);

namespace Subhashladumor1\LaravelAiGuard\Tests\Feature;

use Illuminate\Contracts\Auth\Authenticatable;
use Subhashladumor1\LaravelAiGuard\Models\AiBudget;
use Subhashladumor1\LaravelAiGuard\Tests\TestCase;
use Illuminate\Support\Facades\Route;

class StubUser implements Authenticatable
{
    public function __construct(public int $id = 1) {}

    public function getAuthIdentifierName(): string { return 'id'; }
    public function getAuthIdentifier(): int { return $this->id; }
    public function getAuthPassword(): string { return ''; }
    public function getRememberToken(): ?string { return null; }
    public function setRememberToken($value): void {}
    public function getRememberTokenName(): ?string { return null; }
}

class MiddlewareTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config([
            'ai-guard.budgets.user' => ['limit' => 1.0, 'period' => 'monthly'],
            'ai-guard.ai_disabled' => false,
        ]);
    }

    public function test_middleware_allows_request_when_budget_ok(): void
    {
        Route::get('/test-ai', fn () => 'ok')->middleware('ai.guard');
        $response = $this->get('/test-ai');
        $response->assertStatus(200);
        $response->assertSee('ok');
    }

    public function test_middleware_returns_402_when_budget_exceeded(): void
    {
        AiBudget::create([
            'scope' => 'user',
            'scope_id' => '1',
            'limit' => 0.1,
            'used' => 0.2,
            'period' => 'monthly',
            'resets_at' => now()->addMonth(),
        ]);

        Route::get('/test-ai', fn () => 'ok')->middleware('ai.guard');
        $response = $this->actingAs(new StubUser(1))->get('/test-ai');
        $response->assertStatus(402);
        $response->assertJsonFragment(['message' => 'AI budget exceeded']);
    }

    public function test_middleware_returns_503_when_ai_disabled(): void
    {
        config(['ai-guard.ai_disabled' => true]);
        Route::get('/test-ai', fn () => 'ok')->middleware('ai.guard');
        $response = $this->get('/test-ai');
        $response->assertStatus(503);
    }
}
