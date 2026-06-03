# Pagination Retrofit (Cycle 2b) Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Replace the ~40 hardcoded `paginate(N)` / bare `paginate()` call sites across the controllers with a `per_page()` helper that returns the configured `pagination_size` (default 10), honoring a clamped `?per_page=` override.

**Architecture:** A new autoloaded global helper `per_page()` reads `GeneralSettings::pagination_size` (shipped in Cycle 2a) or a clamped `?per_page=` query value. All active `->paginate(...)` sites in `app/Http/Controllers` are rewritten to `->paginate(per_page())`, preserving extra arguments. Tests asserting the old fixed sizes are updated.

**Tech Stack:** Laravel 11, PHP 8.4, spatie/laravel-settings, Inertia, PHPUnit.

**Testing note:** `tests/TestCase.php` sets `$seed = true` + `RefreshDatabase`, so the Cycle 2a settings migration seeds `pagination_size = 10` before each test. The full suite OOMs at the default 128MB during bootstrap — use `php -d memory_limit=512M artisan test ...` for broad runs. `php artisan migrate`/`composer` run inside Sail (`docker exec hr-laravel.test-1 ...`); plain `php artisan test` works on the host via `phpunit.xml` (127.0.0.1).

**Branch:** `feature/pagination-retrofit` (created; design committed there).

---

## File Structure

- Create: `app/helpers.php` — the `per_page()` global helper.
- Modify: `composer.json` — add `app/helpers.php` to `autoload.files`; then `composer dump-autoload`.
- Modify: ~26 controllers under `app/Http/Controllers/` — rewrite active `->paginate(...)` calls.
- Create: `tests/Feature/PerPageHelperTest.php` — unit-style tests for the helper.
- Modify: `tests/Feature/Unit/StaffDirectoryTest.php` — update the per-page assertion + add an override test.

---

## Task 1: The `per_page()` helper + autoload

**Files:**
- Create: `app/helpers.php`
- Modify: `composer.json`
- Test: `tests/Feature/PerPageHelperTest.php`

- [ ] **Step 1: Write the failing test** — create `tests/Feature/PerPageHelperTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Settings\GeneralSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class PerPageHelperTest extends TestCase
{
    use RefreshDatabase;

    private function withRequest(string $uri): void
    {
        $this->app->instance('request', Request::create($uri));
    }

    public function test_defaults_to_the_configured_pagination_size(): void
    {
        $this->withRequest('/');
        $this->assertSame(10, per_page());
    }

    public function test_reflects_a_changed_setting(): void
    {
        $settings = app(GeneralSettings::class);
        $settings->pagination_size = 30;
        $settings->save();

        $this->withRequest('/');
        $this->assertSame(30, per_page());
    }

    public function test_uses_a_valid_per_page_override(): void
    {
        $this->withRequest('/?per_page=25');
        $this->assertSame(25, per_page());
    }

    public function test_clamps_high_override(): void
    {
        $this->withRequest('/?per_page=1000');
        $this->assertSame(100, per_page());
    }

    public function test_clamps_low_override(): void
    {
        $this->withRequest('/?per_page=2');
        $this->assertSame(5, per_page());
    }

    public function test_ignores_non_numeric_override(): void
    {
        $this->withRequest('/?per_page=abc');
        $this->assertSame(10, per_page());
    }
}
```

- [ ] **Step 2: Run the test, verify it FAILS**

Run: `php artisan test --filter=PerPageHelperTest`
Expected: FAIL — `Call to undefined function per_page()`.

- [ ] **Step 3: Create the helper** — `app/helpers.php`:

```php
<?php

if (! function_exists('per_page')) {
    /**
     * Resolve the page size for a paginated list: a clamped `?per_page=`
     * query override (5–100), otherwise the configured pagination size.
     */
    function per_page(): int
    {
        $requested = request()->query('per_page');

        if (is_numeric($requested)) {
            return (int) max(5, min(100, (int) $requested));
        }

        return app(\App\Settings\GeneralSettings::class)->pagination_size;
    }
}
```

- [ ] **Step 4: Register the helper in `composer.json`**

In `composer.json`, change the `autoload.files` array from:
```json
        "files": [
            "bootstrap/mbstring-polyfill.php"
        ],
```
to:
```json
        "files": [
            "bootstrap/mbstring-polyfill.php",
            "app/helpers.php"
        ],
```

- [ ] **Step 5: Regenerate the autoloader**

Run: `composer dump-autoload`
Expected: "Generated optimized autoload files". (Run inside Sail if composer isn't on the host: `docker exec hr-laravel.test-1 composer dump-autoload`.)

- [ ] **Step 6: Run the test, verify it PASSES**

Run: `php artisan test --filter=PerPageHelperTest`
Expected: PASS (6 tests).

- [ ] **Step 7: Pint and commit**

```bash
vendor/bin/pint app/helpers.php tests/Feature/PerPageHelperTest.php
git add app/helpers.php composer.json tests/Feature/PerPageHelperTest.php
git commit -m "feat: add per_page() helper for configurable pagination"
```
> `composer.lock` is unchanged by `dump-autoload` (no dependency change), so it is not staged.

---

## Task 2: Retrofit all active `paginate()` call sites

**Files:**
- Modify: every controller under `app/Http/Controllers/` that calls `->paginate(`.

There are 44 matches; 4 are commented-out and MUST stay unchanged:
`PersonUnitController.php:24`, `PersonUnitController.php:28`, `PromotionBatchController.php:181`, `PromotionController.php:164`. The sed below skips any line beginning with `//`.

- [ ] **Step 1: Rewrite the call sites with sed (skipping comments)**

Run from the repo root:
```bash
files=$(grep -rlF -e "->paginate(" app/Http/Controllers)
# 1) multi-arg form first: paginate(N, ... ) -> paginate(per_page(), ... )
sed -i '/^[[:space:]]*\/\//! s/->paginate(\([0-9][0-9]*\), /->paginate(per_page(), /g' $files
# 2) single-int form: paginate(N) -> paginate(per_page())
sed -i '/^[[:space:]]*\/\//! s/->paginate([0-9][0-9]*)/->paginate(per_page())/g' $files
# 3) bare form: paginate() -> paginate(per_page())
sed -i '/^[[:space:]]*\/\//! s/->paginate()/->paginate(per_page())/g' $files
```

- [ ] **Step 2: Verify every ACTIVE site was converted and comments untouched**

Run:
```bash
echo "active numeric/empty paginate still present (expect NONE):"
grep -rnE "->paginate\(([0-9]|\))" app/Http/Controllers | grep -vE "^\S+:[0-9]+:\s*//"
echo "per_page() occurrences (expect ~40):"
grep -rcF "per_page(" app/Http/Controllers | grep -v ':0' | awk -F: '{s+=$2} END{print s}'
echo "commented paginate lines unchanged (expect the original 4, still numeric/empty & commented):"
grep -rnE "//.*->paginate\(" app/Http/Controllers
```
Expected: the first command prints nothing; the second prints ~40; the third shows the 4 commented lines still reading `paginate()`/`paginate(15)`/etc. (unchanged). If the first command prints any non-comment line, hand-fix it with Edit and re-verify.

- [ ] **Step 3: Sanity-check the multi-arg sites kept their extra args**

Run:
```bash
grep -rnE "->paginate\(per_page\(\), \['\*'\]" app/Http/Controllers
```
Expected: shows `PermissionController.php` (`'users_page'`), `RoleController.php` (`'users_page'`, `'permissions_page'`) — confirming `paginate(per_page(), ['*'], 'users_page')` etc. are intact. Also confirm `NotificationController.php` still has `->paginate(per_page())->withQueryString()`:
```bash
grep -nF "->paginate(per_page())->withQueryString()" app/Http/Controllers/NotificationController.php
```

- [ ] **Step 4: Build the app (controllers compile) and run the helper test**

Run: `php artisan test --filter=PerPageHelperTest`
Expected: PASS. (This also proves `per_page()` is autoloaded and callable.)

- [ ] **Step 5: Pint and commit**

```bash
vendor/bin/pint app/Http/Controllers
git add app/Http/Controllers
git commit -m "refactor: paginate via per_page() across controllers"
```

---

## Task 3: Update endpoint tests for the new page size

**Files:**
- Modify: `tests/Feature/Unit/StaffDirectoryTest.php`
- Audit: `tests/Feature/Unit/ShowTest.php`, `tests/Feature/QualificationReports/ServiceAggregationsTest.php`, `tests/Feature/StaffAdvancedSearchTest.php`

- [ ] **Step 1: Update the staff-directory per-page test**

In `tests/Feature/Unit/StaffDirectoryTest.php`, replace the method `test_staff_endpoint_paginates_at_fifteen_per_page` (which asserts `staff.meta.per_page == 15` and `has('staff.data', 15)`) with these two methods:

```php
    public function test_staff_endpoint_uses_configured_page_size(): void
    {
        $unit = Unit::factory()->create();
        for ($i = 0; $i < 20; $i++) {
            $this->makeActiveStaff($unit);
        }

        $response = $this->actingAs($this->user)->get(route('unit.staff', ['unit' => $unit->id]));

        $response->assertInertia(fn ($page) => $page
            ->where('staff.meta.per_page', 10)
            ->where('staff.meta.total', 20)
            ->has('staff.data', 10)
        );
    }

    public function test_staff_endpoint_honors_per_page_override(): void
    {
        $unit = Unit::factory()->create();
        for ($i = 0; $i < 20; $i++) {
            $this->makeActiveStaff($unit);
        }

        $response = $this->actingAs($this->user)
            ->get(route('unit.staff', ['unit' => $unit->id, 'per_page' => 5]));

        $response->assertInertia(fn ($page) => $page
            ->where('staff.meta.per_page', 5)
            ->where('staff.meta.total', 20)
            ->has('staff.data', 5)
        );
    }
```

- [ ] **Step 2: Run the updated test, verify it PASSES**

Run: `php artisan test tests/Feature/Unit/StaffDirectoryTest.php`
Expected: PASS (all methods, including the two new ones). This proves the retrofit changed `unit.staff` from 15 to the configured 10 and that `?per_page=` overrides it.

- [ ] **Step 3: Audit the other three test files for page-size assumptions**

Run:
```bash
grep -nE "per_page|->has\([^,]+, (1[0-9]|20|5)\)|assertJsonCount\((1[0-9]|20|5)|meta\.total" \
  tests/Feature/Unit/ShowTest.php \
  tests/Feature/QualificationReports/ServiceAggregationsTest.php \
  tests/Feature/StaffAdvancedSearchTest.php
```
For any assertion that depends on a paginated list returning 15/20 items per page, update the expected count to 10 (the new default) — or, if the list has ≤10 items in that test, no change is needed. Then run those three files:
```bash
php artisan test tests/Feature/Unit/ShowTest.php tests/Feature/QualificationReports/ServiceAggregationsTest.php tests/Feature/StaffAdvancedSearchTest.php
```
Expected: PASS. If a failure shows an off-by-page-size count (e.g. "expected 15, got 10"), fix that assertion to 10 and re-run. Do NOT change production code in this step — only test expectations.

- [ ] **Step 4: Pint and commit**

```bash
vendor/bin/pint tests/
git add tests/
git commit -m "test: update pagination assertions for configurable page size"
```

---

## Task 4: Final verification

- [ ] **Step 1: Targeted suites green**

Run: `php artisan test --filter="PerPageHelperTest|StaffDirectoryTest|AppSettingsTest"`
Expected: PASS.

- [ ] **Step 2: Broad sweep to catch any other page-size-dependent tests**

Run: `php -d memory_limit=512M artisan test 2>&1 | tail -40`
Expected: the suite runs to completion. If any test fails specifically because a paginated list now returns 10 instead of 15/20 (an assertion on item count or `per_page`), update that test's expectation to the configured size (10) — these are test-data expectation changes, not production bugs. Re-run until green. If a failure is unrelated to pagination, note it (it may be a pre-existing flake) and report.

- [ ] **Step 3: Confirm no active hardcoded paginate remains**

Run: `grep -rnE "->paginate\(([0-9]|\))" app/Http/Controllers | grep -vE ":\s*//"`
Expected: no output.

- [ ] **Step 4: Pint**

Run: `vendor/bin/pint --dirty`
Expected: no errors. Do not stage unrelated pre-existing files.

- [ ] **Step 5: Commit any test-expectation fixes from Step 2**

```bash
git add tests/
git commit -m "test: align remaining pagination assertions with default page size"
```
(Skip if Step 2 required no changes.)

- [ ] **Step 6: Manual smoke (ask the user to run)**

With the app running, set "Records per page" on `/settings/app` to a small value (e.g. 5), then open a list page (Users, Audit Log, a unit's staff) and confirm it now paginates at that size; append `?per_page=20` to the URL and confirm the override takes effect.

---

## Self-Review

**Spec coverage:**
- `per_page()` helper (clamped override else setting), autoloaded via `composer.json` → Task 1. ✓
- Uniform retrofit of all active `paginate()` sites, preserving extra args + chained calls → Task 2 (sed rules handle bare, single-int, and multi-arg forms; comments skipped; multi-arg/withQueryString verified). ✓
- New helper tests (no-param, changed-setting, valid override, clamp high/low, non-numeric) → Task 1. ✓
- Endpoint test (configured size + `?per_page=` override) and `StaffDirectoryTest` update → Task 3. ✓
- Audit of `ShowTest`/`ServiceAggregationsTest`/`StaffAdvancedSearchTest` + broad sweep for other affected tests → Task 3 Step 3, Task 4 Step 2. ✓
- No frontend changes (out of scope) → honored (no Vue tasks). ✓

**Placeholder scan:** No TBD/TODO. Task 3 Step 3 / Task 4 Step 2 describe conditional test fixes with the exact rule (old size → 10) rather than vague "handle edge cases", and provide the discovery commands — acceptable because the affected set is data-dependent and must be discovered by running the suite. ✓

**Type/name consistency:** `per_page()` defined in Task 1, called in Task 2's sed output and asserted in Tasks 1/3/4. The default value `10` is consistent (Cycle 2a `GeneralSettings::pagination_size` default) across the helper test, the staff-directory test, and the audit guidance. The `route('unit.staff', ...)` endpoint matches the existing test's route. ✓

**Out of scope:** Cycle 2c date-format retrofit; enabling the spatie settings cache (perf note only).
