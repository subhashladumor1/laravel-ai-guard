# Laravel AI Guard

**Control AI costs and stay within budget when using Laravel AI SDK or any third-party AI API.**

---

## Table of Contents

- [What is Laravel AI Guard?](#what-is-laravel-ai-guard)
- [Why Use It?](#why-use-it)
- [How It Works](#how-it-works)
- [How to Reduce AI Costs](#how-to-reduce-ai-costs)
- [Requirements](#requirements)
- [Installation](#installation)
- [Configuration](#configuration)
- [How to Use](#how-to-use)
- [Package Structure (What Each Part Does)](#package-structure-what-each-part-does)
- [Multi-Language Support](#multi-language-support)
- [Multi-Tenant (SaaS)](#multi-tenant-saas)
- [Testing](#testing)
- [License](#license)

---

## What is Laravel AI Guard?

Laravel AI Guard is a **cost and budget layer** for your Laravel app. It sits in front of your AI calls and helps you:

| Feature | What it does |
|--------|----------------|
| **Track cost** | Save every AI call (tokens, cost, user) in the database so you can see who spent what. |
| **Set budgets** | Limit how much can be spent per user, per tenant, or for the whole app (daily or monthly). |
| **Estimate before calling** | Guess how many tokens and how much a prompt will cost *before* you call the API. |
| **Block when over budget** | Stop requests that would go over the limit, so you never overspend. |
| **Emergency off switch** | Turn off all AI in the app with one config setting. |

It works with the **Laravel AI SDK** (official Laravel 12.x AI package) or with **any other AI API** (OpenAI, Anthropic, etc.) — you just record the usage after each call.

---

## Why Use It?

- **AI APIs charge by token.** One heavy user or a bug can create a huge bill.
- **Most apps don’t track usage.** You only see the bill at the end of the month.
- **No built-in limits.** By default, nothing stops users from making unlimited expensive calls.

Laravel AI Guard adds **tracking**, **limits**, and **visibility** so you can control and reduce AI costs.

---

## How It Works

Think of it in three steps: **before**, **during**, and **after** the AI call.

```
┌─────────────────────────────────────────────────────────────────────────┐
│  BEFORE the AI call                                                      │
│  • Check: Is the user/tenant/app within budget?                          │
│  • Optional: Estimate cost for the prompt (tokens + $)                  │
│  • If over budget → block the request (no API call)                      │
└─────────────────────────────────────────────────────────────────────────┘
                                    ↓
┌─────────────────────────────────────────────────────────────────────────┐
│  DURING the AI call                                                      │
│  • Your app calls the AI (Laravel AI SDK or any API) as you normally do  │
└─────────────────────────────────────────────────────────────────────────┘
                                    ↓
┌─────────────────────────────────────────────────────────────────────────┐
│  AFTER the AI call                                                       │
│  • Record: tokens used, cost, user, tag → save to database              │
│  • Add this cost to the budget “used” amount                             │
└─────────────────────────────────────────────────────────────────────────┘
```

- **Laravel AI SDK** = the part that actually talks to AI (agents, chat, streaming).
- **Laravel AI Guard** = the part that decides “are we allowed to call?” and “how much did we spend?”

They work together; AI Guard does **not** replace the SDK.

---

## How It Works (In Detail)

### 1. How Budgets are Calculated

Budgets work on a hierarchy. When `AIGuard::checkAllBudgets($userId)` is called, it checks three layers in order:

1.  **Global Budget**: Total spend for the entire application.
2.  **Tenant Budget**: Total spend for the organization (if `tenant_id` is provided).
3.  **User Budget**: Spend for the specific user.

If **ANY** of these are exceeded, the request is blocked.

**Reset Periods:**
Budgets automatically reset based on the `period` in your config (`daily` or `monthly`).
- **Daily**: Resets at midnight (00:00 UTC).
- **Monthly**: Resets on the 1st of the month (00:00 UTC).

The `ai_budgets` table tracks the current `used` amount. When a period expires, the next request resets `used` to 0.

### 2. How Cost is Calculated

Cost is derived from the **tokens** used and the **model price** defined in `config/ai-guard.php`.

Formula:
```text
Cost = (Input Tokens / 1000 * Input Price) + (Output Tokens / 1000 * Output Price)
```

Example for `gpt-4o`:
- Input Price: $0.0025 / 1k tokens
- Output Price: $0.01 / 1k tokens
- Usage: 500 input, 200 output

```text
Input Cost  = (500 / 1000) * 0.0025 = $0.00125
Output Cost = (200 / 1000) * 0.0100 = $0.00200
Total Cost  = $0.00325
```

### 3. How Estimation Works

When you call `AIGuard::estimate($prompt)`, we don't call the AI API (which costs money). Instead, we use a simple character-based heuristic.

- **Input Tokens**: We estimate ~4 characters = 1 token (configurable in `estimation.chars_per_token`).
- **Output Tokens**: We estimate output will be 50% of input length (configurable in `estimation.default_output_multiplier`).

**Example:**
Prompt: "Write a short poem" (18 chars).
- Estimated Input: 18 / 4 = 5 tokens.
- Estimated Output: 5 * 0.5 = 3 tokens.
- Total Estimated Tokens: 8.

*Note: This is an approximation to help user UX, not a 100% accurate guaranteed price.*

### 4. How to Disable AI

You can instantly disable all AI features if you detect abuse or billing issues.

**Option A: Environment Variable (Recommended)**
Add this to your `.env` file:
```env
AI_GUARD_DISABLED=true
```

**Option B: Config**
Edit `config/ai-guard.php`:
```php
'ai_disabled' => true,
```

**What happens when disabled?**
- `EnforceAIBudget` middleware returns `503 Service Unavailable`.
- `AIGuard::checkAllBudgets()` simply returns (or you can configure it to throw exceptions).
- No API calls reach your AI provider if you wrap them properly.

---

## How to Reduce AI Costs

Laravel AI Guard helps you reduce and control costs in these ways:

### 1. **Estimate before calling**

Use `AIGuard::estimate($prompt)` to get an approximate token count and cost *before* you call the API. You can:

- Show a warning to the user (“This may cost about $0.02”).
- Block prompts that are too expensive.
- Switch to a cheaper model for long prompts.

### 2. **Set budgets**

In config you set limits (e.g. $10 per user per month). The package:

- Checks these limits before each AI call.
- Blocks the request (HTTP 402) if the budget is exceeded.
- Resets “used” amounts daily or monthly (via command or scheduler).

So no single user or tenant can blow the budget.

### 3. **Track everything**

Every call is stored in the `ai_usages` table: provider, model, input/output tokens, cost, user, tenant, tag. You can:

- Run `php artisan ai-guard:report` to see where the money goes.
- See which model or feature (“tag”) costs the most.
- Fix expensive flows or cap heavy users.

### 4. **Use the kill switch**

If something goes wrong (e.g. a spike or abuse), set `AI_GUARD_DISABLED=true` in `.env`. The middleware will return 503 and you can stop all AI until you fix it.

### 5. **Use tags**

When recording usage, pass a `tag` (e.g. `chat`, `support`, `export`). Reports break down cost by tag so you can optimize the most expensive features.

---

## Requirements

- **PHP** 8.1 or higher  
- **Laravel** 10.x, 11.x, or 12.x  
- **Laravel AI SDK** ([laravel/ai](https://laravel.com/docs/12.x/ai-sdk)) is **optional**. Use it if you want agents/streaming; otherwise use any AI API and record usage manually.

---

## Installation

**Step 1 — Install the package**

```bash
composer require subhashladumor1/laravel-ai-guard
```

**Step 2 — Publish config and migrations**

```bash
php artisan vendor:publish --tag=ai-guard-config
php artisan vendor:publish --tag=ai-guard-migrations
```

**Step 3 — Run migrations**

```bash
php artisan migrate
```

This creates two tables: `ai_usages` (each AI call) and `ai_budgets` (limits and how much is used).

**Optional — Publish language files** (to change or add languages)

```bash
php artisan vendor:publish --tag=ai-guard-lang
```

---

## Configuration

After publishing, edit **`config/ai-guard.php`**.

| Setting | What it does |
|--------|----------------|
| **`ai_disabled`** | Set to `true` (or use `.env`: `AI_GUARD_DISABLED=true`) to turn off all AI (middleware returns 503). |
| **`pricing`** | Cost per 1,000 tokens for each provider and model (input + output). Used to compute cost from token counts. |
| **`default_model`** | Model name used when you don’t pass one (e.g. `gpt-4o`). |
| **`default_provider`** | Provider name when you don’t pass one (e.g. `openai`). |
| **`budgets`** | Default limits for `global`, `user`, and `tenant`, and the period (`daily` or `monthly`). You can override with env vars (e.g. `AI_GUARD_USER_LIMIT=10`). |
| **`estimation`** | How many characters = 1 token (default 4), and a multiplier for output tokens. Used by `estimate()`. |

Example `.env` for limits:

```env
AI_GUARD_DISABLED=false
AI_GUARD_GLOBAL_LIMIT=100
AI_GUARD_USER_LIMIT=10
AI_GUARD_TENANT_LIMIT=50
```

---

## How to Use

### Option A — Using Laravel AI SDK (12.x)

If you use the official [Laravel AI SDK](https://laravel.com/docs/12.x/ai-sdk) (agents, `prompt()`, `stream()`, etc.):

**1. Before the call — check budget (and optionally estimate)**

```php
use Subhashladumor1\LaravelAiGuard\Facades\AIGuard;

// Check if the user/tenant is within budget (throws if over limit)
AIGuard::checkAllBudgets(auth()->id(), $tenantId);

// Optional: show estimated cost before calling
$estimate = AIGuard::estimate($userPrompt);
// $estimate has: estimated_tokens, estimated_cost, model, provider
```

**2. Call the AI (as you already do)**

```php
$response = (new YourAgent)->prompt($userPrompt);
```

**3. After the call — record usage (AI Guard reads tokens from the response)**

```php
AIGuard::recordFromResponse(
    $response,
    userId: auth()->id(),
    tenantId: $tenantId,
    provider: null,   // optional; uses config default
    model: null,      // optional; uses config default
    tag: 'chat'
);
```

**Streaming:** record when the stream finishes:

```php
return (new YourAgent)
    ->stream($userPrompt)
    ->then(fn ($response) => AIGuard::recordFromResponse(
        $response,
        userId: auth()->id(),
        tenantId: null,
        tag: 'stream-chat'
    ));
```

**Queued:** record in the `then()` callback when the job finishes.

---

### Option B — Using any other AI API (OpenAI, Anthropic, etc.)

You don’t use the Laravel AI SDK; you call an API yourself. After you get the response and token usage:

**1. Before the call — same as above**

```php
AIGuard::checkAllBudgets(auth()->id(), $tenantId);
$estimate = AIGuard::estimate($prompt);
```

**2. After the call — record usage manually**

```php
AIGuard::recordAndApplyBudget([
    'provider' => 'openai',
    'model' => 'gpt-4o',
    'input_tokens' => 400,
    'output_tokens' => 250,
    'cost' => 0.028,   // or let AI Guard calculate: omit 'cost' and it uses config pricing
    'user_id' => auth()->id(),
    'tenant_id' => $tenantId,
    'tag' => 'chat',
]);
```

If you omit `cost`, you must have `provider` and `model` in config pricing so the package can calculate it. Otherwise pass `cost` yourself.

---

### Protect routes with middleware

To block requests that are over budget before they hit your controller:

```php
Route::post('/chat', ChatController::class)->middleware('ai.guard');
```

- If the user/tenant is over budget → response is **402** with a JSON message.
- If `ai_disabled` is true → response is **503**.

---

### Artisan commands

**See usage and cost (report)**

```bash
php artisan ai-guard:report
php artisan ai-guard:report --period=month
php artisan ai-guard:report --days=7
```

**Reset budgets** (when a period ends, so the “used” amount goes back to 0)

```bash
php artisan ai-guard:reset-budgets
php artisan ai-guard:reset-budgets --dry-run
php artisan ai-guard:reset-budgets --scope=user
```

**Run reset on a schedule** (e.g. in `app/Console/Kernel.php`):

```php
$schedule->command('ai-guard:reset-budgets')->daily();
```

---

## Package Structure (What Each Part Does)

A short map of the package so you know where everything lives and what it does.

```
laravel-ai-guard/
├── src/
│   ├── AiGuardServiceProvider.php   # Registers config, migrations, commands, middleware, translations
│   ├── GuardManager.php             # Main logic: record, estimate, check budget, recordFromResponse
│   ├── Facades/
│   │   └── AIGuard.php              # Facade so you can use AIGuard::record(), AIGuard::estimate(), etc.
│   │
│   ├── config/
│   │   └── ai-guard.php             # Default config (pricing, budgets, estimation)
│   │
│   ├── database/migrations/         # Creates ai_usages and ai_budgets tables
│   │   ├── create_ai_usages_table.php
│   │   └── create_ai_budgets_table.php
│   │
│   ├── Budget/
│   │   ├── BudgetResolver.php      # Finds or creates budget rows (global, user, tenant)
│   │   └── BudgetEnforcer.php      # Checks limits and throws if exceeded; adds usage to budgets
│   │
│   ├── Cost/
│   │   ├── TokenEstimator.php      # Estimates token count from text (e.g. characters ÷ 4)
│   │   └── CostCalculator.php      # Computes cost from tokens using config pricing
│   │
│   ├── Models/
│   │   ├── AiUsage.php             # Eloquent model for ai_usages table
│   │   └── AiBudget.php            # Eloquent model for ai_budgets table
│   │
│   ├── Middleware/
│   │   └── EnforceAIBudget.php    # Route middleware: checks budget, returns 402/503
│   │
│   ├── Commands/
│   │   ├── AiGuardReportCommand.php      # ai-guard:report
│   │   └── AiGuardResetBudgetsCommand.php # ai-guard:reset-budgets
│   │
│   ├── Exceptions/
│   │   ├── BudgetExceededException.php   # Thrown when a budget limit is exceeded
│   │   └── AiDisabledException.php        # For when AI is disabled
│   │
│   └── lang/                       # Translations (en, ar, es, fr, de, zh, hi, bn, pt, ru, ja)
│       └── {locale}/messages.php
│
├── tests/                          # Unit and feature tests
├── composer.json
├── phpunit.xml
└── README.md
```

**Flow in one sentence:**  
You call `AIGuard::recordFromResponse()` or `AIGuard::record()` → **GuardManager** uses **CostCalculator** and **TokenEstimator** to get or compute cost → saves a row in **AiUsage** and updates **AiBudget** via **BudgetEnforcer**. Middleware and commands use the same GuardManager and budgets.

---

## Multi-Language Support

All user-facing text (errors, middleware messages, command output) can be translated. The package ships with 11 locales:

| Code | Language   | Code | Language   |
|------|------------|------|------------|
| en   | English    | ar  | Arabic     |
| es   | Spanish    | fr  | French     |
| de   | German     | zh  | Chinese    |
| hi   | Hindi      | bn  | Bengali    |
| pt   | Portuguese | ru  | Russian    |
| ja   | Japanese   |     |            |

- Your app’s locale (e.g. `config('app.locale')`) is used automatically.
- To change or add translations: `php artisan vendor:publish --tag=ai-guard-lang`, then edit files in `lang/vendor/ai-guard`.

---

## Multi-Tenant (SaaS)

Laravel AI Guard is tenant-aware:

- Store **tenant id** on each usage (`tenant_id` on `ai_usages`).
- Set **tenant-level budgets** in config; the package checks user and tenant (and global).
- Middleware can get tenant from the `X-Tenant-ID` header or a request attribute (e.g. `tenant_id`).

---

## Testing

```bash
composer install
php artisan test
```

Or run PHPUnit directly:

```bash
./vendor/bin/phpunit
```

Tests cover cost calculation, token estimation, budget enforcement, database writes, middleware, and commands.

---

## License

MIT. See the [LICENSE](LICENSE) file.
