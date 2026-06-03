# Date-Format Retrofit — Backend (Cycle 2c-1) Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Make server-rendered display dates follow the configurable `GeneralSettings::date_format` via Carbon `displayDate()`/`displayDateTime()` macros, retrofitting the display-format `->format()` sites in controllers (and one model accessor) while leaving functional date formatting alone.

**Architecture:** Register two Carbon macros in `AppServiceProvider::boot()` that format using the `date_format` setting (default `'d M Y'`). Mechanically swap the unambiguous display-format literals (`'d M Y'`, `'d M, Y'`, `'d F Y'`, `'d M Y H:i'`, `'d F, Y'`) to the macros in `app/Http/Controllers` + `app/Models/InstitutionPerson.php`. Audit the 28 `'Y-m-d'` controller sites (all expected to be functional form/raw values → left unchanged).

**Tech Stack:** Laravel 11, PHP 8.4, Carbon, spatie/laravel-settings, PHPUnit.

**Testing note:** `tests/TestCase.php` sets `$seed = true` + `RefreshDatabase` (settings migration seeds `date_format = 'd M Y'`). The full suite OOMs at the host's 128MB limit — run broad sweeps in the Sail container: `docker exec hr-laravel.test-1 php artisan test`. Filtered host runs (`php artisan test --filter=...`) work fine.

**Branch:** `feature/date-format-retrofit` (created; design committed there).

**Key property:** `date_format` default is `'d M Y'`, so the ~52 `'d M Y'` sites are output-identical after retrofit — their tests stay green. Only `'d M, Y'`/`'d F Y'`/`'d F, Y'` sites change appearance to the default `'d M Y'`.

---

## File Structure

- Modify: `app/Providers/AppServiceProvider.php` — register the macros in `boot()`.
- Create: `tests/Feature/DisplayDateMacroTest.php` — macro behavior.
- Modify: ~display-format controllers under `app/Http/Controllers/` + `app/Models/InstitutionPerson.php` — swap display `->format()` to macros.
- Audit (mostly no-op): the 28 `'Y-m-d'` controller sites.

---

## Task 1: Carbon display macros

**Files:**
- Modify: `app/Providers/AppServiceProvider.php`
- Test: `tests/Feature/DisplayDateMacroTest.php`

- [ ] **Step 1: Write the failing test** — create `tests/Feature/DisplayDateMacroTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Models\User;
use App\Settings\GeneralSettings;
use Carbon\Carbon as BaseCarbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class DisplayDateMacroTest extends TestCase
{
    use RefreshDatabase;

    public function test_display_date_uses_configured_default_format(): void
    {
        $date = Carbon::parse('2024-06-28');
        $this->assertSame('28 Jun 2024', $date->displayDate());
    }

    public function test_display_date_reflects_a_changed_setting(): void
    {
        $settings = app(GeneralSettings::class);
        $settings->date_format = 'Y-m-d';
        $settings->save();

        $this->assertSame('2024-06-28', Carbon::parse('2024-06-28')->displayDate());
    }

    public function test_display_datetime_appends_time(): void
    {
        $date = Carbon::parse('2024-06-28 14:30:00');
        $this->assertSame('28 Jun 2024 14:30', $date->displayDateTime());
    }

    public function test_macro_resolves_on_a_model_cast_date(): void
    {
        $user = User::factory()->create(['created_at' => '2024-06-28 09:00:00']);
        $this->assertSame('28 Jun 2024', $user->created_at->displayDate());
    }

    public function test_macro_resolves_on_base_carbon_parse(): void
    {
        $this->assertSame('28 Jun 2024', BaseCarbon::parse('2024-06-28')->displayDate());
    }
}
```

- [ ] **Step 2: Run the test, verify it FAILS**

Run: `php artisan test --filter=DisplayDateMacroTest`
Expected: FAIL — `Method displayDate does not exist` (macros not registered).

- [ ] **Step 3: Register the macros**

In `app/Providers/AppServiceProvider.php`, at the END of the `boot()` method body (after the `Qualification::observe(...)` line), add:

```php
        foreach ([\Illuminate\Support\Carbon::class, \Carbon\Carbon::class, \Carbon\CarbonImmutable::class] as $carbonClass) {
            $carbonClass::macro('displayDate', function () {
                /** @var \Carbon\CarbonInterface $this */
                return $this->format(app(\App\Settings\GeneralSettings::class)->date_format);
            });

            $carbonClass::macro('displayDateTime', function () {
                /** @var \Carbon\CarbonInterface $this */
                return $this->format(app(\App\Settings\GeneralSettings::class)->date_format.' H:i');
            });
        }
```

(Regular `function () { ... }` closures are required — Carbon's Macroable binds `$this` to the date instance, which arrow functions cannot do. Registering on all three classes covers model casts (`Illuminate\Support\Carbon`), `Carbon\Carbon::parse`, and any immutable dates.)

- [ ] **Step 4: Run the test, verify it PASSES**

Run: `php artisan test --filter=DisplayDateMacroTest`
Expected: PASS (5 tests).

- [ ] **Step 5: Pint and commit**

```bash
vendor/bin/pint app/Providers/AppServiceProvider.php tests/Feature/DisplayDateMacroTest.php
git add app/Providers/AppServiceProvider.php tests/Feature/DisplayDateMacroTest.php
git commit -m "feat: add displayDate/displayDateTime Carbon macros"
```

---

## Task 2: Retrofit display-format sites in controllers + model

**Files:**
- Modify: controllers under `app/Http/Controllers/` using a display `->format(...)`
- Modify: `app/Models/InstitutionPerson.php` (line ~698)
- Test: `tests/Feature/DisplayDateMacroTest.php` (append an endpoint test)

- [ ] **Step 1: Swap display-format literals to macros with sed (comment-skipping)**

Run from the repo root:
```bash
files="$(grep -rlF -e "->format('d M Y')" -e "->format('d M, Y')" -e "->format('d F Y')" -e "->format('d M Y H:i')" -e "->format('d F, Y')" app/Http/Controllers) app/Models/InstitutionPerson.php"
# datetime variant first (exact-string, but keep ordering explicit)
sed -i "/^[[:space:]]*\/\//! s/->format('d M Y H:i')/->displayDateTime()/g" $files
sed -i "/^[[:space:]]*\/\//! s/->format('d M Y')/->displayDate()/g" $files
sed -i "/^[[:space:]]*\/\//! s/->format('d M, Y')/->displayDate()/g" $files
sed -i "/^[[:space:]]*\/\//! s/->format('d F, Y')/->displayDate()/g" $files
sed -i "/^[[:space:]]*\/\//! s/->format('d F Y')/->displayDate()/g" $files
```

- [ ] **Step 2: Verify the display family is gone (except `H:i:s` and comments) and `Y-m-d` untouched**

Run:
```bash
echo "remaining display-family literals (expect NONE, ignoring comments):"
grep -rnE "->format\('(d M Y|d M, Y|d F, Y|d F Y|d M Y H:i)'\)" app/Http/Controllers app/Models/InstitutionPerson.php | grep -vE ":[[:space:]]*//"
echo "H:i:s sites preserved (expect 3, unchanged):"
grep -rnF "->format('d M Y H:i:s')" app/Http/Controllers | wc -l
echo "Y-m-d sites still present (expect 28, untouched this task):"
grep -rnF "->format('Y-m-d')" app/Http/Controllers | wc -l
echo "macro call count (expect ~73 displayDate + ~8 displayDateTime):"
grep -rcF "->displayDate()" app/Http/Controllers app/Models | grep -v ':0' | awk -F: '{s+=$2} END{print "displayDate: "s}'
grep -rcF "->displayDateTime()" app/Http/Controllers | grep -v ':0' | awk -F: '{s+=$2} END{print "displayDateTime: "s}'
```
The first command MUST print nothing. The model accessor `app/Models/InstitutionPerson.php` should now read `return $this->retirement_date->displayDate();`.

- [ ] **Step 3: Lint the changed files**

Run:
```bash
for f in $(git diff --name-only main -- app/Http/Controllers app/Models); do php -l "$f" >/dev/null 2>&1 || echo "SYNTAX ERR: $f"; done; echo "lint ok"
```
Expected: "lint ok", no SYNTAX ERR.

- [ ] **Step 4: Append an endpoint test proving the setting drives controller output**

Add this method to `tests/Feature/DisplayDateMacroTest.php` (add `use App\Models\User;` is already imported; add `use Spatie\Permission\Models\Role;` to the imports):

```php
    public function test_controller_renders_dates_in_configured_format(): void
    {
        $settings = app(GeneralSettings::class);
        $settings->date_format = 'Y-m-d';
        $settings->save();

        $viewer = User::factory()->create();
        $viewer->givePermissionTo(['view user', 'view user roles', 'view user permissions']);

        $target = User::factory()->create();
        $role = Role::firstOrCreate(['name' => 'editor']);
        $target->assignRole($role);

        $response = $this->actingAs($viewer)->get(route('user.show', ['user' => $target->id]));

        $response->assertOk();
        $response->assertInertia(
            fn (\Inertia\Testing\AssertableInertia $page) => $page
                ->where('user.roles.0.start_date', now()->format('Y-m-d'))
        );
    }
```
(The user-show controller maps `'start_date' => $role->created_at->format('d M Y')` → now `->displayDate()`. With the setting at `'Y-m-d'`, the prop must render in `Y-m-d`.)

- [ ] **Step 5: Run the macro test file**

Run: `php artisan test --filter=DisplayDateMacroTest`
Expected: PASS (6 tests). If `test_controller_renders_dates_in_configured_format` fails, the user-show `start_date` site wasn't retrofitted (it should now be `->displayDate()`) — re-check Step 1 covered `UserController`.

- [ ] **Step 6: Pint and commit**

```bash
vendor/bin/pint $(git diff --name-only main -- app/Http/Controllers app/Models) tests/Feature/DisplayDateMacroTest.php
git add app/Http/Controllers app/Models/InstitutionPerson.php tests/Feature/DisplayDateMacroTest.php
git commit -m "refactor: render display dates via displayDate macro"
```
> IMPORTANT: `vendor/bin/pint` is run ONLY on the files this task changed (the `git diff --name-only` list), NOT the whole `app/Http/Controllers` directory — running it on the directory reformats unrelated controllers and bundles noise into the commit. After committing, verify cleanliness:
> ```bash
> for f in $(git show --name-only --format="" HEAD); do git show HEAD --format="" -- "$f" | grep -qE "displayDate|DisplayDateMacro" || echo "NO date change: $f"; done
> ```
> Expect no output (every changed file has a date-macro change). If a file is listed, it was reformatted-only — restore it with `git checkout main -- <file>` and amend.

---

## Task 3: Audit the 28 `'Y-m-d'` controller sites

**Files:**
- Read (and only edit if genuinely display): the 28 `->format('Y-m-d')` sites under `app/Http/Controllers`.

- [ ] **Step 1: List the sites**

Run: `grep -rnF "->format('Y-m-d')" app/Http/Controllers`
Expected: 28 sites across `ContactController`, `DataIntegrityController`, `NoteController`, `UnitController`, `JobCategoryController`, `PersonController`, `InstitutionPersonController`.

- [ ] **Step 2: Classify each site**

For each site, read the surrounding code and apply the rule:
- **Functional → LEAVE unchanged** if the formatted value is any of: a form-input default/value (often paired with a sibling display field, or a key like `*_raw`, `date_of_birth`, `start_date`/`end_date`/`hire_date` consumed by an edit form or `<input type="date">`), a query/`whereDate`/filter key, an API/JSON field consumed programmatically, or used in a comparison/computation.
- **Display → change to `->displayDate()`** ONLY if the value is rendered directly to a human as text and is not consumed as a machine value anywhere.

Expectation from the design audit: **all 28 are functional** — they are Inertia prop keys (`start_date`, `end_date`, `hire_date`, `dob_raw`, `hire_date_raw`, `date_of_birth`, `note_date`, `valid_end`, `separation_date`, `status_end_date`) feeding edit forms / date inputs, which require `Y-m-d`. Retrofitting them would break those inputs. So the expected outcome is **zero changes** in this task.

- [ ] **Step 3: Record the classification (no code change expected)**

Produce a short list: for each of the 28 sites, `file:line — functional (reason)` or `display → retrofitted`. If — and only if — a site is unambiguously display-only (rendered as text, never consumed as a machine value), change it to `->displayDate()` and run `php -l` on that file. If all 28 are functional (the expected case), make NO code change and report the classification.

- [ ] **Step 4: Commit (only if any site was changed)**

If any site was retrofitted:
```bash
vendor/bin/pint <changed file(s)>
git add <changed file(s)>
git commit -m "refactor: render display-only Y-m-d dates via displayDate macro"
```
If no site was changed (expected), there is nothing to commit — record the classification in the task report and proceed.

---

## Task 4: Final verification

- [ ] **Step 1: Targeted suites green**

Run: `php artisan test --filter="DisplayDateMacroTest|AppSettingsTest|UserShowTest"`
Expected: PASS.

- [ ] **Step 2: Broad sweep (catches tests asserting now-unified date strings)**

Run: `docker exec hr-laravel.test-1 php artisan test 2>&1 | tail -40`
Expected: the suite runs to completion. Any failure caused by a test asserting a date string in `'d M, Y'` / `'d F Y'` / `'d F, Y'` format (which now render as the default `'d M Y'`) should be fixed by updating that test's expected string to the `'d M Y'` form (these are display-format expectation changes, not production bugs). `'d M Y'`-asserting tests stay green (default unchanged). Re-run until green. If a failure is unrelated to date formatting, note it and report.

- [ ] **Step 3: Confirm no display-family literals remain active**

Run: `grep -rnE "->format\('(d M Y|d M, Y|d F, Y|d F Y|d M Y H:i)'\)" app/Http/Controllers app/Models | grep -vE ":[[:space:]]*//"`
Expected: no output (the `H:i:s` and any `Exports` sites are out of scope and not matched here).

- [ ] **Step 4: Pint (changed files only)**

Run: `vendor/bin/pint $(git diff --name-only main -- 'app/**/*.php' 'tests/**/*.php')`
Expected: clean. Do NOT pint whole directories (avoids bundling unrelated reformatting). Do not stage unrelated pre-existing files.

- [ ] **Step 5: Commit any test-expectation fixes from Step 2**

```bash
git add tests/
git commit -m "test: update date-format assertions for unified display format"
```
(Skip if Step 2 needed no changes.)

- [ ] **Step 6: Manual smoke (ask the user to run)**

With the app running, set "Date format" on `/settings/app` to `Y-m-d`, then open a page that shows a server-rendered date (e.g. a user's roles list `start_date`, a staff retirement date) and confirm it now renders in `Y-m-d`. (Frontend-formatted dates are unchanged — that's Cycle 2c-2.)

---

## Self-Review

**Spec coverage:**
- `displayDate()`/`displayDateTime()` macros in `AppServiceProvider`, setting-driven, registered for cast + parsed + immutable Carbon → Task 1. ✓
- Mechanical retrofit of the display family (`'d M Y'`, `'d M, Y'`, `'d F Y'`, `'d M Y H:i'`, `'d F, Y'`) in controllers + the `InstitutionPerson` accessor → Task 2 (sed skips comments; `H:i:s` and `Y-m-d` untouched). ✓
- `displayDateTime()` for the `H:i` variant; `H:i:s` left forensic → Task 2. ✓
- Manual audit of the 28 `'Y-m-d'` controller sites (display→macro, functional→leave; expected all functional) → Task 3. ✓
- Exports excluded → honored (sed targets only `app/Http/Controllers` + the one model; no `app/Exports`). ✓
- Macro tests (cast date, parsed date, changed setting, datetime) + endpoint test proving setting drives output → Tasks 1, 2. ✓
- Broad sweep + test-expectation updates for unified formats → Task 4. ✓
- Cleanliness guard against whole-directory Pint reformatting (the issue from Cycle 2b) → Task 2 Step 6, Task 4 Step 4. ✓

**Placeholder scan:** No TBD/TODO. Task 3 describes a judgment process with an explicit rule and expected outcome (not a vague "handle cases"); the `Y-m-d` site list is enumerable via the given grep. ✓

**Type/name consistency:** `displayDate()` / `displayDateTime()` defined in Task 1 and used by the Task 2 sed output and the Task 1/2 tests. The endpoint test targets `user.roles.0.start_date` which maps from `created_at->displayDate()` after Task 2. Default format `'d M Y'` is consistent across the macro test, the default-behavior note, and the audit. ✓

**Out of scope:** Cycle 2c-2 (frontend date-fns retrofit); `app/Exports`; functional `Y-m-d`; `H:i:s` forensic timestamps.
