# Laravel AI Guard – AI Cost & Budget Optimization for Laravel AI SDK

Laravel AI Guard is a financial firewall in front of your AI calls. It provides **AI cost tracking**, **budget enforcement**, **pre-execution cost estimation**, and **safety controls** for Laravel applications using the [Laravel AI SDK](https://laravel.com/docs/12.x/ai-sdk) (Laravel 12.x). This package extends and complements the Laravel AI SDK — it does not replace it.

## Overview

- **Laravel AI SDK** = how AI works (agents, providers, streaming).
- **Laravel AI Guard** = whether AI is allowed to run and how much it costs.

Laravel AI Guard works in three phases:

1. **Before AI call** → Can we afford this? (estimation, budget check)
2. **During AI call** → Your app uses the Laravel AI SDK as usual
3. **After AI call** → Record cost and usage (and optionally apply to budgets)

## Requirements

- PHP 8.1+
- Laravel 10.x, 11.x, or 12.x
- [Laravel AI SDK](https://laravel.com/docs/12.x/ai-sdk) (optional; use for actual AI calls)

## Laravel AI SDK (12.x) compatibility

**Yes — Laravel AI Guard is built to work with the latest [Laravel AI SDK](https://laravel.com/docs/12.x/ai-sdk).**

- The SDK’s `AgentResponse` and `StreamedAgentResponse` expose a `usage` object with `promptTokens` and `completionTokens`. AI Guard reads these and records cost automatically.
- **No extra dependency:** `laravel/ai` is not required in `composer.json`. If you use the SDK, call `AIGuard::recordFromResponse($response, ...)` after each call; if you use another client, use `AIGuard::record([...])` with your own usage data.
- **Recommended flow with Laravel AI SDK:**
  1. **Before:** `AIGuard::checkAllBudgets(auth()->id(), $tenantId);` and optionally `AIGuard::estimate($prompt);`
  2. **Call:** `$response = (new YourAgent)->prompt($prompt);` (or `->stream()` / `->queue()`)
  3. **After:** `AIGuard::recordFromResponse($response, auth()->id(), $tenantId, null, null, 'chat');` — provider/model are optional (defaults from config).
- For **streaming**, use the `then()` callback to record once the response is complete:  
  `->stream(...)->then(fn ($response) => AIGuard::recordFromResponse($response, ...));`
- For **queued** jobs, record in the `then()` callback when the response is available.

## Installation

```bash
composer require subhashladumor1/laravel-ai-guard
```

Publish config, migrations, and (optional) language files:

```bash
php artisan vendor:publish --tag=ai-guard-config
php artisan vendor:publish --tag=ai-guard-migrations
php artisan vendor:publish --tag=ai-guard-lang   # optional: customize translations
php artisan migrate
```

## Configuration

Edit `config/ai-guard.php`:

- **`ai_disabled`** – Global kill switch (e.g. `AI_GUARD_DISABLED=true` in `.env`)
- **`pricing`** – Per-provider, per-model input/output price per 1K tokens (OpenAI, Anthropic style)
- **`budgets`** – Default limits for `global`, `user`, and `tenant` (limit + period: daily/monthly)
- **`estimation`** – Characters per token and output multiplier for cost estimation
- **`periods`** – Period definitions for resets

## Usage

### 1. AI Cost Tracking

**With Laravel AI SDK (12.x)** — pass the response and let AI Guard read usage and compute cost:

```php
use Subhashladumor1\LaravelAiGuard\Facades\AIGuard;

$response = (new SalesCoach)->prompt('Analyze this transcript...');

AIGuard::recordFromResponse(
    $response,
    userId: auth()->id(),
    tenantId: tenant('id'),
    provider: null,  // optional; uses config default
    model: null,    // optional; uses config default
    tag: 'sales-coach'
);
```

**Manual recording** (any provider or no SDK):

```php
AIGuard::record([
    'provider' => 'openai',
    'model' => 'gpt-4o',
    'input_tokens' => 400,
    'output_tokens' => 250,
    'cost' => 0.028,
    'user_id' => auth()->id(),
    'tenant_id' => tenant('id'),
    'tag' => 'chat',
]);
```

To also increment budget usage use `recordFromResponse` (it does this by default) or `AIGuard::recordAndApplyBudget([...])`.

### 2. Budget Enforcement (Pre-Execution)

Check before calling the AI SDK:

```php
AIGuard::checkBudget(scope: 'user', id: auth()->id());
// or check all applicable budgets (user → tenant → global)
AIGuard::checkAllBudgets(auth()->id(), $tenantId);
```

If the budget is exceeded, `BudgetExceededException` (402) is thrown.

### 3. Cost Estimation (Before AI Call)

Estimate tokens and cost from a prompt:

```php
$estimate = AIGuard::estimate('Explain Laravel AI SDK');
// [
//   'estimated_tokens' => 650,
//   'estimated_input_tokens' => ...,
//   'estimated_output_tokens' => ...,
//   'estimated_cost' => 0.021,
//   'model' => 'gpt-4o',
//   'provider' => 'openai',
// ]
```

Use this to block, warn, or downgrade requests.

### 4. Kill Switch

Disable AI globally:

```env
AI_GUARD_DISABLED=true
```

When disabled, the middleware returns 503 and budget checks can be skipped.

### 5. Middleware

Protect routes so requests that would exceed the AI budget are blocked:

```php
Route::post('/chat', ChatController::class)->middleware('ai.guard');
```

- Identifies user (and optionally tenant via `X-Tenant-ID` or `tenant_id` attribute).
- Calls `AIGuard::checkAllBudgets()`. If exceeded → HTTP 402 with JSON body.

### 6. Artisan Commands

**Report (usage and cost summary):**

```bash
php artisan ai-guard:report
php artisan ai-guard:report --period=month
php artisan ai-guard:report --days=7
```

**Reset budgets (for expired periods):**

```bash
php artisan ai-guard:reset-budgets
php artisan ai-guard:reset-budgets --dry-run
php artisan ai-guard:reset-budgets --scope=user
```

Schedule resets (e.g. in `app/Console/Kernel.php`):

```php
$schedule->command('ai-guard:reset-budgets')->daily();
```

## Multi-Language (i18n)

All user-facing strings (exceptions, middleware responses, and Artisan command output) use Laravel’s translation system with the `ai-guard` namespace. Supported locales (top worldwide languages):

| Code | Language   | Code | Language   |
|------|------------|------|------------|
| `en` | English    | `ar` | Arabic     |
| `es` | Spanish    | `fr` | French     |
| `de` | German     | `zh` | Chinese    |
| `hi` | Hindi      | `bn` | Bengali    |
| `pt` | Portuguese | `ru` | Russian    |
| `ja` | Japanese   |      |            |

- **Usage:** Set your app locale as usual (`config('app.locale')`, `App::setLocale()`, or per-request). Package messages will follow that locale.
- **Publish to customize:** `php artisan vendor:publish --tag=ai-guard-lang` copies `src/lang` to `lang/vendor/ai-guard`. Edit the files there to override or add locales.
- **Keys:** Translations live under `ai-guard::messages` (e.g. `ai-guard::messages.exception.budget_exceeded`, `ai-guard::messages.report.title`).

## Multi-Tenant (SaaS)

Laravel AI Guard is tenant-aware:

- `tenant_id` on `ai_usages`
- Budget scope `tenant` with `scope_id`
- Middleware can use `X-Tenant-ID` header or request attribute `tenant_id`

## Testing

```bash
composer install
php artisan test
```

Or with PHPUnit directly:

```bash
./vendor/bin/phpunit
```

Tests use Orchestra Testbench, run migrations, and assert cost calculation, token estimation, budget enforcement, exception throwing, database writes, and middleware behavior.

## License

MIT. See [LICENSE](LICENSE) file.
