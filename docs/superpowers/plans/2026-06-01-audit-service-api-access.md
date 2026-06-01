# Audit Service API Access + API Request Logging — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Give the external Audit Service Website token-based access scoped to only `GET /api/staff-statistics`, and log every `/api/*` request to a dedicated table.

**Architecture:** A seeder creates a passwordless-in-practice service user; an artisan command mints a Sanctum personal access token carrying the `staff-statistics:read` ability. The route enforces that ability via Sanctum's `abilities` middleware. A terminable middleware on the `api` group records every request (including failed auth) into an `api_logs` table after the response is sent.

**Tech Stack:** Laravel 11 (legacy L10 structure), Laravel Sanctum v4, PHPUnit 11, MySQL.

---

## Key codebase facts (read before starting)

- **IMPORTANT (discovered during Task 3):** This project actually uses the
  streamlined Laravel 11 structure. The live middleware config is
  `bootstrap/app.php` (`->withMiddleware(...)`). `app/Http/Kernel.php` exists but
  is **dead code** — `public/index.php` loads `bootstrap/app.php`, and
  `App\Http\Kernel` is referenced nowhere. CLAUDE.md's "Laravel 10 structure"
  claim is inaccurate. Register all aliases and middleware-group changes in
  `bootstrap/app.php`, NOT `app/Http/Kernel.php`.
- Middleware aliases live in `bootstrap/app.php` inside `$middleware->alias([...])`.
- The `api` middleware group is configured via `$middleware->api(prepend: [...])`
  in `bootstrap/app.php`.
- `routes/api.php` already defines the `auth:sanctum` staff-statistics route.
- `User` model already uses `HasApiTokens` (`app/Models/User.php`).
- Tests extend `Tests\TestCase` with `protected $seed = true`; feature tests that touch the DB add `use RefreshDatabase;`.
- `User::factory()` exists. Use `Laravel\Sanctum\Sanctum::actingAs($user, [abilities])` to simulate token auth in tests.
- **Important:** Existing `StaffStatisticsApiTest` uses `$this->actingAs($user)` (web guard). Sanctum assigns those requests a `TransientToken` whose `can()` always returns `true`, so the new `abilities` middleware does NOT break them. The logging middleware must therefore guard against `TransientToken` (no `name`) when reading the token name.

---

## File Structure

- Create: `database/seeders/AuditServiceUserSeeder.php` — creates the service user.
- Modify: `database/seeders/DatabaseSeeder.php` — register the new seeder.
- Create: `app/Console/Commands/IssueApiToken.php` — mint a scoped token.
- Modify: `bootstrap/app.php` — register `abilities`/`ability` aliases; add logging middleware to `api` group. (NOT `app/Http/Kernel.php`, which is dead code.)
- Modify: `routes/api.php` — add `abilities:staff-statistics:read` to the route.
- Create: `database/migrations/XXXX_create_api_logs_table.php` — log table.
- Create: `app/Models/ApiLog.php` — log model.
- Create: `app/Http/Middleware/LogApiRequests.php` — terminable logging middleware.
- Create: `tests/Feature/AuditServiceApiAccessTest.php` — access + logging tests.
- Create: `tests/Feature/IssueApiTokenCommandTest.php` — command tests.

---

## Task 1: Service user seeder

**Files:**
- Create: `database/seeders/AuditServiceUserSeeder.php`
- Modify: `database/seeders/DatabaseSeeder.php`
- Test: `tests/Feature/AuditServiceApiAccessTest.php`

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/AuditServiceApiAccessTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\AuditServiceUserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditServiceApiAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_creates_audit_service_user(): void
    {
        $this->seed(AuditServiceUserSeeder::class);

        $this->assertDatabaseHas('users', [
            'email' => 'audit-service@audit.gov.gh',
            'name' => 'Audit Service Website',
        ]);
    }

    public function test_seeder_is_idempotent(): void
    {
        $this->seed(AuditServiceUserSeeder::class);
        $this->seed(AuditServiceUserSeeder::class);

        $this->assertSame(1, User::where('email', 'audit-service@audit.gov.gh')->count());
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=AuditServiceApiAccessTest`
Expected: FAIL — class `Database\Seeders\AuditServiceUserSeeder` not found.

- [ ] **Step 3: Create the seeder**

Create `database/seeders/AuditServiceUserSeeder.php`:

```php
<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class AuditServiceUserSeeder extends Seeder
{
    /**
     * Create the Audit Service Website service account.
     *
     * The account authenticates only via a Sanctum personal access token
     * (issued out-of-band with `php artisan app:issue-api-token`), so the
     * password is randomized and never used for interactive login.
     */
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'audit-service@audit.gov.gh'],
            [
                'name' => 'Audit Service Website',
                'password' => bcrypt(Str::random(40)),
            ]
        );
    }
}
```

- [ ] **Step 4: Register the seeder in DatabaseSeeder**

In `database/seeders/DatabaseSeeder.php`, add this line inside `run()` after the existing `$this->call(SuperAdminSeeder::class);` line:

```php
        $this->call(AuditServiceUserSeeder::class);
```

- [ ] **Step 5: Run test to verify it passes**

Run: `php artisan test --filter=AuditServiceApiAccessTest`
Expected: PASS (both methods).

- [ ] **Step 6: Commit**

```bash
git add database/seeders/AuditServiceUserSeeder.php database/seeders/DatabaseSeeder.php tests/Feature/AuditServiceApiAccessTest.php
git commit -m "feat: add Audit Service Website service account seeder"
```

---

## Task 2: Token issuance command

**Files:**
- Create: `app/Console/Commands/IssueApiToken.php`
- Test: `tests/Feature/IssueApiTokenCommandTest.php`

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/IssueApiTokenCommandTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IssueApiTokenCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_issues_a_token_with_the_requested_ability(): void
    {
        $user = User::factory()->create(['email' => 'svc@example.test']);

        $this->artisan('app:issue-api-token', ['email' => 'svc@example.test'])
            ->assertExitCode(0);

        $token = $user->fresh()->tokens()->first();

        $this->assertNotNull($token);
        $this->assertSame('Audit Service Website', $token->name);
        $this->assertContains('staff-statistics:read', $token->abilities);
    }

    public function test_it_respects_custom_name_and_ability_options(): void
    {
        $user = User::factory()->create(['email' => 'svc2@example.test']);

        $this->artisan('app:issue-api-token', [
            'email' => 'svc2@example.test',
            '--name' => 'My Token',
            '--ability' => ['reports:read', 'staff-statistics:read'],
        ])->assertExitCode(0);

        $token = $user->fresh()->tokens()->first();

        $this->assertSame('My Token', $token->name);
        $this->assertContains('reports:read', $token->abilities);
        $this->assertContains('staff-statistics:read', $token->abilities);
    }

    public function test_it_fails_for_unknown_email(): void
    {
        $this->artisan('app:issue-api-token', ['email' => 'nobody@example.test'])
            ->assertExitCode(1);

        $this->assertSame(0, \Laravel\Sanctum\PersonalAccessToken::count());
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=IssueApiTokenCommandTest`
Expected: FAIL — command `app:issue-api-token` not found.

- [ ] **Step 3: Create the command**

Create `app/Console/Commands/IssueApiToken.php`:

```php
<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class IssueApiToken extends Command
{
    protected $signature = 'app:issue-api-token
        {email : Email of the user to issue the token for}
        {--name=Audit Service Website : A label for the token}
        {--ability=* : Abilities to grant (repeatable); defaults to staff-statistics:read}';

    protected $description = 'Issue a scoped Sanctum personal access token for an external API consumer.';

    public function handle(): int
    {
        $user = User::where('email', $this->argument('email'))->first();

        if ($user === null) {
            $this->error("No user found with email {$this->argument('email')}.");

            return self::FAILURE;
        }

        $abilities = $this->option('ability');

        if (empty($abilities)) {
            $abilities = ['staff-statistics:read'];
        }

        $token = $user->createToken($this->option('name'), $abilities);

        $this->info('Token issued. Copy it now — it will not be shown again:');
        $this->line($token->plainTextToken);
        $this->newLine();
        $this->info('Abilities: '.implode(', ', $abilities));

        return self::SUCCESS;
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test --filter=IssueApiTokenCommandTest`
Expected: PASS (all three methods).

- [ ] **Step 5: Commit**

```bash
git add app/Console/Commands/IssueApiToken.php tests/Feature/IssueApiTokenCommandTest.php
git commit -m "feat: add app:issue-api-token command for scoped API tokens"
```

---

## Task 3: Enforce the ability on the route

**Files:**
- Modify: `app/Http/Kernel.php`
- Modify: `routes/api.php`
- Test: `tests/Feature/AuditServiceApiAccessTest.php`

- [ ] **Step 1: Add the access-control tests**

Append these methods to the `AuditServiceApiAccessTest` class created in Task 1:

```php
    public function test_token_with_ability_can_read_statistics(): void
    {
        $user = User::factory()->create();

        \Laravel\Sanctum\Sanctum::actingAs($user, ['staff-statistics:read']);

        $this->getJson('/api/staff-statistics')
            ->assertStatus(200)
            ->assertJsonStructure([
                'total_staff',
                'regional_offices',
                'district_offices',
                'field_staff',
                'professionals',
                'professions',
            ]);
    }

    public function test_token_without_ability_is_forbidden(): void
    {
        $user = User::factory()->create();

        \Laravel\Sanctum\Sanctum::actingAs($user, ['some-other-ability']);

        $this->getJson('/api/staff-statistics')->assertStatus(403);
    }

    public function test_request_without_token_is_unauthorized(): void
    {
        $this->getJson('/api/staff-statistics')->assertStatus(401);
    }
```

Add this import to the top of the test file (with the other `use` statements):

```php
use Illuminate\Support\Facades\Cache;
```

And add a `setUp` so the cached payload never leaks between tests. Insert this method at the top of the class body:

```php
    protected function setUp(): void
    {
        parent::setUp();
        Cache::forget('staff_statistics');
    }
```

- [ ] **Step 2: Run tests to verify the new ones fail**

Run: `php artisan test --filter=AuditServiceApiAccessTest`
Expected: `test_token_without_ability_is_forbidden` FAILS (currently 200, not 403) because the ability is not yet enforced. The `with_ability` and `without_token` cases may already pass.

- [ ] **Step 3: Register Sanctum ability middleware aliases**

In `app/Http/Kernel.php`, inside the `$routeMiddleware` array, add these two entries (after the `'auth.session'` line):

```php
        'abilities' => \Laravel\Sanctum\Http\Middleware\CheckAbilities::class,
        'ability' => \Laravel\Sanctum\Http\Middleware\CheckForAnyAbility::class,
```

- [ ] **Step 4: Apply the ability middleware to the route**

In `routes/api.php`, change the staff-statistics route from:

```php
Route::middleware('auth:sanctum')
    ->get('/staff-statistics', [StaffStatisticsController::class, 'index'])
    ->name('api.staff-statistics');
```

to:

```php
Route::middleware(['auth:sanctum', 'abilities:staff-statistics:read'])
    ->get('/staff-statistics', [StaffStatisticsController::class, 'index'])
    ->name('api.staff-statistics');
```

- [ ] **Step 5: Run the affected tests**

Run: `php artisan test --filter=AuditServiceApiAccessTest`
Expected: PASS (all methods).

Run the pre-existing API test to confirm no regression (web-guard `actingAs` still works via TransientToken):

Run: `php artisan test --filter=StaffStatisticsApiTest`
Expected: PASS.

- [ ] **Step 6: Commit**

```bash
git add app/Http/Kernel.php routes/api.php tests/Feature/AuditServiceApiAccessTest.php
git commit -m "feat: scope staff-statistics API to the staff-statistics:read ability"
```

---

## Task 4: api_logs table and model

**Files:**
- Create: `database/migrations/XXXX_XX_XX_XXXXXX_create_api_logs_table.php`
- Create: `app/Models/ApiLog.php`
- Test: `tests/Feature/AuditServiceApiAccessTest.php` (covered in Task 5)

- [ ] **Step 1: Generate the migration**

Run: `php artisan make:migration create_api_logs_table --no-interaction`
This creates an anonymous-class migration file under `database/migrations/`.

- [ ] **Step 2: Define the schema**

Replace the generated file's body so `up()`/`down()` read:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('api_logs', function (Blueprint $table) {
            $table->id();
            $table->string('method', 10);
            $table->string('path');
            $table->unsignedSmallInteger('status');
            $table->foreignId('user_id')->nullable()->index();
            $table->string('token_name')->nullable();
            $table->string('ip', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->unsignedInteger('duration_ms')->nullable();
            $table->timestamp('created_at')->nullable()->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_logs');
    }
};
```

- [ ] **Step 3: Create the model**

Create `app/Models/ApiLog.php`:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApiLog extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'method',
        'path',
        'status',
        'user_id',
        'token_name',
        'ip',
        'user_agent',
        'duration_ms',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'status' => 'integer',
            'duration_ms' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
```

- [ ] **Step 4: Run the migration in the test DB and a sanity check**

Run: `php artisan test --filter=AuditServiceApiAccessTest`
Expected: PASS — `RefreshDatabase` runs the new migration; existing tests still green (table exists, nothing references it yet).

- [ ] **Step 5: Commit**

```bash
git add database/migrations/*_create_api_logs_table.php app/Models/ApiLog.php
git commit -m "feat: add api_logs table and ApiLog model"
```

---

## Task 5: Logging middleware

**Files:**
- Create: `app/Http/Middleware/LogApiRequests.php`
- Modify: `bootstrap/app.php` (live middleware config — NOT `app/Http/Kernel.php`)
- Test: `tests/Feature/AuditServiceApiAccessTest.php`

- [ ] **Step 1: Write the failing tests**

Append these methods to `AuditServiceApiAccessTest`:

```php
    public function test_successful_request_is_logged(): void
    {
        $user = User::factory()->create();

        \Laravel\Sanctum\Sanctum::actingAs($user, ['staff-statistics:read']);

        $this->getJson('/api/staff-statistics')->assertStatus(200);

        $this->assertDatabaseHas('api_logs', [
            'method' => 'GET',
            'path' => 'api/staff-statistics',
            'status' => 200,
            'user_id' => $user->id,
        ]);
    }

    public function test_unauthenticated_request_is_logged_with_null_user(): void
    {
        $this->getJson('/api/staff-statistics')->assertStatus(401);

        $this->assertDatabaseHas('api_logs', [
            'path' => 'api/staff-statistics',
            'status' => 401,
            'user_id' => null,
        ]);
    }
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `php artisan test --filter=AuditServiceApiAccessTest`
Expected: the two new tests FAIL — no `api_logs` rows are written yet.

- [ ] **Step 3: Create the middleware**

Create `app/Http/Middleware/LogApiRequests.php`:

```php
<?php

namespace App\Http\Middleware;

use App\Models\ApiLog;
use Closure;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class LogApiRequests
{
    private const START_ATTRIBUTE = 'api_log_started_at';

    public function handle(Request $request, Closure $next): Response
    {
        $request->attributes->set(self::START_ATTRIBUTE, microtime(true));

        return $next($request);
    }

    public function terminate(Request $request, Response $response): void
    {
        try {
            $start = $request->attributes->get(self::START_ATTRIBUTE);
            $durationMs = $start !== null ? (int) round((microtime(true) - $start) * 1000) : null;

            ApiLog::create([
                'method' => $request->getMethod(),
                'path' => $request->path(),
                'status' => $response->getStatusCode(),
                'user_id' => $request->user()?->getKey(),
                'token_name' => $this->tokenName($request),
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'duration_ms' => $durationMs,
            ]);
        } catch (Throwable $e) {
            report($e);
        }
    }

    private function tokenName(Request $request): ?string
    {
        $token = $request->user()?->currentAccessToken();

        return $token instanceof PersonalAccessToken ? $token->name : null;
    }
}
```

Note: `currentAccessToken()` returns a `TransientToken` (no `name`) for session/web-guard requests; the `instanceof PersonalAccessToken` guard returns `null` in that case instead of erroring.

- [ ] **Step 4: Register the middleware on the api group**

In `bootstrap/app.php` (NOT `app/Http/Kernel.php` — that file is dead code in
this project), add `LogApiRequests` to the `api` group's `prepend` list so it
wraps every `/api/*` request. The existing block looks like:

```php
        // API middleware group
        $middleware->api(prepend: [
            // \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
        ]);
```

Change it to:

```php
        // API middleware group
        $middleware->api(prepend: [
            \App\Http\Middleware\LogApiRequests::class,
            // \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
        ]);
```

- [ ] **Step 5: Run the affected tests**

Run: `php artisan test --filter=AuditServiceApiAccessTest`
Expected: PASS (all methods, including the two logging tests).

- [ ] **Step 6: Commit**

```bash
git add app/Http/Middleware/LogApiRequests.php app/Http/Kernel.php tests/Feature/AuditServiceApiAccessTest.php
git commit -m "feat: log all API requests to api_logs via terminable middleware"
```

---

## Task 6: Final verification

- [ ] **Step 1: Format**

Run: `vendor/bin/pint --dirty`
Expected: files formatted, no errors.

- [ ] **Step 2: Run the full feature suite for the touched areas**

Run: `php artisan test --filter=AuditServiceApiAccessTest`
Run: `php artisan test --filter=IssueApiTokenCommandTest`
Run: `php artisan test --filter=StaffStatisticsApiTest`
Expected: all PASS.

- [ ] **Step 3: Run the entire suite (ask the user first if it is slow)**

Run: `php artisan test`
Expected: green. Investigate any failure before proceeding.

- [ ] **Step 4: Commit any formatting changes**

```bash
git add -A
git commit -m "style: apply pint formatting" || echo "nothing to format"
```

---

## Operational note (not a code task)

After deploying, the operator issues the Audit Service token once:

```bash
php artisan db:seed --class=AuditServiceUserSeeder --force
php artisan app:issue-api-token audit-service@audit.gov.gh
```

Copy the printed token and hand it to the Audit team. They call:

```
GET /api/staff-statistics
Authorization: Bearer <token>
Accept: application/json
```
