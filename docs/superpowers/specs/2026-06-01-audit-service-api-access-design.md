# Audit Service API Access + API Request Logging — Design

**Date:** 2026-06-01
**Status:** Approved (pending spec review)

## Goal

Give the **Audit Service Website** (an external app) authenticated access to the
`GET /api/staff-statistics` endpoint, scoped so it can reach only that endpoint,
and log every API request for auditing.

## Decisions (from brainstorming)

| Decision | Choice |
|----------|--------|
| Token issuance | Seeder creates the service user; artisan command mints tokens |
| Token scope | Restricted via a Sanctum ability (`staff-statistics:read`) |
| Log backend | Dedicated `api_logs` table |
| Log scope | All `/api/*` requests, including unauthenticated/failed |
| Log retention | No pruning for now (low volume; add later if needed) |

## Architecture

### 1. Service user account

New seeder `Database\Seeders\AuditServiceUserSeeder`, following the existing
`AdminUserSeeder` pattern, idempotent via `firstOrCreate`:

- `email`: `audit-service@audit.gov.gh`
- `name`: `Audit Service Website`
- `password`: a random bcrypt hash (account never logs in via browser; password
  only satisfies the non-null column)

Registered in `DatabaseSeeder::run()`. **No roles or Spatie permissions** are
attached — access is governed entirely by the token ability. This is acceptable
because `StaffStatisticsController::index` performs no `Gate` check; the ability
is the sole authorization gate, which gives least privilege.

### 2. Token issuance command

New command `App\Console\Commands\IssueApiToken`, signature:

```
app:issue-api-token {email} {--name=Audit Service Website} {--ability=staff-statistics:read}
```

Behavior:
- Resolve the user by `email`; fail with a clear message if not found.
- `--ability` is repeatable; collect into an array (default: `['staff-statistics:read']`).
- Call `$user->createToken($name, $abilities)`.
- Print the plaintext token **once** to stdout with a warning that it cannot be
  retrieved again.

Tokens are issued manually by an operator and handed to the Audit team
out-of-band. **Tokens are never seeded or committed.**

### 3. Ability enforcement on the route

`routes/api.php`, staff-statistics route:

```php
Route::middleware(['auth:sanctum', 'abilities:staff-statistics:read'])
    ->get('/staff-statistics', [StaffStatisticsController::class, 'index'])
    ->name('api.staff-statistics');
```

Requires registering Sanctum's ability middleware aliases in
`app/Http/Kernel.php` `$routeMiddleware` (this project uses the legacy Laravel 10
array name, not `$middlewareAliases`):

```php
'abilities' => \Laravel\Sanctum\Http\Middleware\CheckAbilities::class,
'ability'   => \Laravel\Sanctum\Http\Middleware\CheckForAnyAbility::class,
```

A token lacking `staff-statistics:read` receives **403**; the Audit token can
reach **only** this endpoint.

> Note: the existing session-based SPA (guard `web`) is unaffected — session
> tokens are granted all abilities (`*`) by Sanctum, so the in-app frontend
> continues to pass the `abilities` check.

### 4. API request logging

**Migration** `create_api_logs_table`:

| Column | Type | Notes |
|--------|------|-------|
| `id` | bigint PK | |
| `method` | string | HTTP verb |
| `path` | string | request path (no query string) |
| `status` | unsigned smallint | response status code |
| `user_id` | foreignId nullable | null for unauthenticated requests |
| `token_name` | string nullable | name of the access token, never the secret |
| `ip` | string nullable | |
| `user_agent` | string nullable | |
| `duration_ms` | unsigned integer nullable | request handling time |
| `created_at` | timestamp | |

Indexes: `created_at`, `user_id`. No `updated_at` (rows are immutable).
A `down()` drops the table.

**Model** `App\Models\ApiLog`:
- `$fillable` for all writable columns.
- `$timestamps = false` with an explicit `created_at` cast to `datetime`
  (only `created_at` is stored).
- `user()` belongsTo relationship with return type hint.

**Middleware** `App\Http\Middleware\LogApiRequests`:
- `handle()`: store a start timestamp on the request, pass through.
- `terminate(Request $request, Response $response)`: runs **after** the response
  is sent, so logging never delays the client. Writes one `ApiLog` row with:
  method, path, status, `user_id` (`$request->user()?->getKey()`),
  `token_name` (`$request->user()?->currentAccessToken()?->name`), ip,
  user_agent, and `duration_ms` computed from the start timestamp.
- **Never logs** request or response bodies, query strings, or the plaintext
  token — avoids storing PII and secrets.

Registered on the `api` middleware group in `app/Http/Kernel.php` so it captures
**all** `/api/*` traffic, including requests rejected by `auth:sanctum` (logged
with null `user_id`).

> Implementation detail: `currentAccessToken()` returns a
> `TransientToken` for session (web-guard) requests, which has no `name`.
> The middleware must guard against this (use `name` only when the token is a
> `PersonalAccessToken`) to avoid errors on SPA traffic.

## Data flow

```
External Audit app
  → GET /api/staff-statistics  (Authorization: Bearer <token>)
    → [api group] LogApiRequests::handle  (records start time)
    → auth:sanctum               (401 if token invalid/missing)
    → abilities:staff-statistics:read   (403 if ability absent)
    → StaffStatisticsController::index   (200, cached JSON)
  ← response sent to client
    → LogApiRequests::terminate  → INSERT api_logs row
```

## Error handling

- Missing/invalid token → 401 (Sanctum). Still logged (null user).
- Valid token without ability → 403 (CheckAbilities). Still logged.
- Command run with unknown email → command prints error, non-zero exit, no token.
- Logging failures must not break the response: `terminate()` runs post-response,
  but wrap the insert so an exception there is caught/logged and never surfaces
  to the client.

## Testing (Feature)

`tests/Feature/StaffStatisticsApiAccessTest.php`:
1. Token with `staff-statistics:read` → 200, returns expected JSON keys.
2. Token **without** the ability → 403.
3. No token → 401.
4. A successful request inserts exactly one `api_logs` row with correct
   method, path, status 200, and the token's user_id.
5. An unauthenticated (401) request is still logged with null `user_id`.

`tests/Feature/IssueApiTokenCommandTest.php`:
6. Command creates a token with the requested ability for the user.
7. Command with an unknown email fails gracefully (non-zero exit, no token created).

Tests rely on the existing per-test reseed and model factories.

## Out of scope (YAGNI)

- Login/token-rotation endpoint.
- Broad (unrestricted) tokens.
- Log pruning command / scheduled cleanup.
- In-app UI for viewing `api_logs`.
- Logging request/response bodies.
