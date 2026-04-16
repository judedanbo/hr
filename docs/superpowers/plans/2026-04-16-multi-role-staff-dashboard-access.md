# Multi-role staff dashboard access — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Fix the `/dashboard` route so users with the `staff` role plus any other role can reach both destinations, via a session-scoped chooser page and a header switcher.

**Architecture:** Extract the `/dashboard` closure into a `DashboardController`. Add two helpers to `User` (`isMultiRoleStaff`, `canAccessAdminDashboard`). Introduce a chooser page (`Dashboard/ChooseMode.vue`) reached when a multi-role staff user has no `view_mode` in session. Add a `switchMode` action writing to `session('view_mode')`. Mount a `ViewModeSwitcher.vue` dropdown in the top nav so users can flip modes anytime. Session-scoped state only — no DB changes.

**Tech Stack:** Laravel 11, PHP 8.4, PHPUnit 11, Spatie Laravel Permission, Inertia.js + Vue 3, HeadlessUI, Tailwind 3.

**Spec:** `docs/superpowers/specs/2026-04-16-multi-role-staff-dashboard-access-design.md`

**Branch:** `fix/multi-role-staff-dashboard-access`

---

## Task 1: `User::isMultiRoleStaff()` helper

**Files:**
- Modify: `app/Models/User.php`
- Test: `tests/Unit/UserMultiRoleTest.php` (new)

- [ ] **Step 1: Create the failing unit test**

Create `tests/Unit/UserMultiRoleTest.php`:

```php
<?php

namespace Tests\Unit;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserMultiRoleTest extends TestCase
{
    use RefreshDatabase;

    public function test_is_multi_role_staff_returns_true_for_staff_plus_another_role(): void
    {
        $user = User::factory()->create(['password_change_at' => now()]);
        $user->assignRole(['staff', 'super-administrator']);

        $this->assertTrue($user->fresh()->isMultiRoleStaff());
    }

    public function test_is_multi_role_staff_returns_false_for_staff_only(): void
    {
        $user = User::factory()->create(['password_change_at' => now()]);
        $user->assignRole('staff');

        $this->assertFalse($user->fresh()->isMultiRoleStaff());
    }

    public function test_is_multi_role_staff_returns_false_for_admin_only(): void
    {
        $user = User::factory()->create(['password_change_at' => now()]);
        $user->assignRole('super-administrator');

        $this->assertFalse($user->fresh()->isMultiRoleStaff());
    }

    public function test_is_multi_role_staff_returns_false_for_no_roles(): void
    {
        $user = User::factory()->create(['password_change_at' => now()]);

        $this->assertFalse($user->fresh()->isMultiRoleStaff());
    }
}
```

- [ ] **Step 2: Run the test — verify it fails**

Run:
```bash
php artisan test --filter=UserMultiRoleTest
```

Expected: all tests FAIL with `Error: Call to undefined method App\Models\User::isMultiRoleStaff()`.

- [ ] **Step 3: Add the helper to `User`**

Open `app/Models/User.php`. Find the class body and add this method (place it near the other domain helpers, after existing relationship methods):

```php
public function isMultiRoleStaff(): bool
{
    return $this->hasRole('staff') && $this->roles->count() > 1;
}
```

- [ ] **Step 4: Run the test — verify it passes**

Run:
```bash
php artisan test --filter=UserMultiRoleTest
```

Expected: 4 tests pass.

- [ ] **Step 5: Commit**

```bash
git add app/Models/User.php tests/Unit/UserMultiRoleTest.php
git commit -m "feat: add User::isMultiRoleStaff helper

Co-Authored-By: Claude Opus 4.6 (1M context) <noreply@anthropic.com>"
```

---

## Task 2: `User::canAccessAdminDashboard()` helper

**Files:**
- Modify: `app/Models/User.php`
- Test: `tests/Unit/UserMultiRoleTest.php`

- [ ] **Step 1: Append failing unit tests**

Add to `tests/Unit/UserMultiRoleTest.php` inside the class:

```php
public function test_can_access_admin_dashboard_true_for_super_administrator(): void
{
    $user = User::factory()->create(['password_change_at' => now()]);
    $user->assignRole('super-administrator');

    $this->assertTrue($user->fresh()->canAccessAdminDashboard());
}

public function test_can_access_admin_dashboard_true_for_user_with_view_dashboard_permission(): void
{
    $user = User::factory()->create(['password_change_at' => now()]);
    $user->givePermissionTo('view dashboard');

    $this->assertTrue($user->fresh()->canAccessAdminDashboard());
}

public function test_can_access_admin_dashboard_false_for_staff_only(): void
{
    $user = User::factory()->create(['password_change_at' => now()]);
    $user->assignRole('staff');

    $this->assertFalse($user->fresh()->canAccessAdminDashboard());
}
```

- [ ] **Step 2: Run tests — verify they fail**

Run:
```bash
php artisan test --filter=UserMultiRoleTest
```

Expected: the 3 new tests FAIL with `Error: Call to undefined method ... canAccessAdminDashboard()`. The 4 from Task 1 still pass.

- [ ] **Step 3: Add the helper to `User`**

In `app/Models/User.php`, just below `isMultiRoleStaff()`, add:

```php
public function canAccessAdminDashboard(): bool
{
    return $this->hasRole('super-administrator') || $this->can('view dashboard');
}
```

> **Note:** If `'view dashboard'` isn't a real permission in the current seeders, the test `test_can_access_admin_dashboard_true_for_user_with_view_dashboard_permission` will fail at `givePermissionTo(...)` with `Spatie\Permission\Exceptions\PermissionDoesNotExist`. If that happens, check `database/seeders/` to confirm the exact name (it may be `view dashboards` or similar) and update both the test and the helper. Do **not** invent a new permission — match what the seeder defines.

- [ ] **Step 4: Run tests — verify they pass**

Run:
```bash
php artisan test --filter=UserMultiRoleTest
```

Expected: 7 tests pass.

- [ ] **Step 5: Commit**

```bash
git add app/Models/User.php tests/Unit/UserMultiRoleTest.php
git commit -m "feat: add User::canAccessAdminDashboard helper

Co-Authored-By: Claude Opus 4.6 (1M context) <noreply@anthropic.com>"
```

---

## Task 3: Regression tests for existing `/dashboard` behavior

Write tests against the **current** closure so we have a safety net before refactoring.

**Files:**
- Test: `tests/Feature/DashboardTest.php` (new)

- [ ] **Step 1: Create the regression test file**

Create `tests/Feature/DashboardTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Models\Institution;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_pure_staff_user_without_person_redirects_to_staff_index(): void
    {
        $user = User::factory()->create(['password_change_at' => now()]);
        $user->assignRole('staff');

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertRedirect(route('staff.index'));
    }

    public function test_super_admin_with_institution_redirects_to_institution_show(): void
    {
        Institution::factory()->create();

        $user = User::factory()->create(['password_change_at' => now()]);
        $user->assignRole('super-administrator');

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertRedirect(route('institution.show', [1]));
    }

    public function test_super_admin_without_institution_redirects_to_institution_index(): void
    {
        Institution::query()->delete();

        $user = User::factory()->create(['password_change_at' => now()]);
        $user->assignRole('super-administrator');

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertRedirect(route('institution.index'));
    }

    public function test_user_with_no_roles_redirects_to_staff_index(): void
    {
        $user = User::factory()->create(['password_change_at' => now()]);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertRedirect(route('staff.index'));
    }
}
```

- [ ] **Step 2: Run the tests — they should pass against the existing closure**

Run:
```bash
php artisan test --filter=DashboardTest
```

Expected: all 4 tests PASS. If any fail, the test doesn't match current behavior — inspect and fix the test before continuing (do **not** change the application code yet).

> **Troubleshooting:** `Institution::factory()` may require related records (e.g., institution type). If the factory fails, open `database/factories/InstitutionFactory.php` and check its `definition()` — pass any required attributes explicitly in the test. For the third test, if `Institution::factory()` is called elsewhere via seeders, `Institution::query()->delete()` ensures the count is 0.

- [ ] **Step 3: Commit**

```bash
git add tests/Feature/DashboardTest.php
git commit -m "test: add regression tests for existing /dashboard behavior

Co-Authored-By: Claude Opus 4.6 (1M context) <noreply@anthropic.com>"
```

---

## Task 4: Extract `/dashboard` closure to `DashboardController`

Pure refactor — same behavior. Regression tests from Task 3 must still pass.

**Files:**
- Create: `app/Http/Controllers/DashboardController.php`
- Modify: `routes/web.php` (lines 123-151)

- [ ] **Step 1: Scaffold the controller**

Run:
```bash
php artisan make:controller DashboardController --no-interaction
```

Expected: `Controller [app/Http/Controllers/DashboardController.php] created successfully.`

- [ ] **Step 2: Populate the controller with the extracted logic**

Replace the entire contents of `app/Http/Controllers/DashboardController.php` with:

```php
<?php

namespace App\Http\Controllers;

use App\Models\Institution;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request): RedirectResponse
    {
        $request->session()->reflash();

        /** @var User $user */
        $user = $request->user();

        if ($user->hasRole('staff')) {
            return $this->redirectToStaffLanding($user);
        }

        if ($user->canAccessAdminDashboard()) {
            return $this->redirectToAdminDashboard();
        }

        return redirect()->route('staff.index');
    }

    private function redirectToStaffLanding(User $user): RedirectResponse
    {
        if ($user->person) {
            return redirect()->route(
                'staff.show',
                [$user->person->institution->first()->staff->id]
            );
        }

        return redirect()->route('staff.index');
    }

    private function redirectToAdminDashboard(): RedirectResponse
    {
        if (Institution::count() < 1) {
            session()->flash(
                'info',
                'No institution found. Please create an institution to proceed'
            );

            return redirect()->route('institution.index');
        }

        return redirect()->route('institution.show', [1]);
    }
}
```

- [ ] **Step 3: Wire the route to the controller**

In `routes/web.php`, add this import at the top with the other controller imports:

```php
use App\Http\Controllers\DashboardController;
```

Then replace the closure at lines 123-151 (from `Route::get('/dashboard', function () {` through `})->middleware(['auth', 'password_changed', 'verified'])->name('dashboard');`) with:

```php
Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'password_changed', 'verified'])
    ->name('dashboard');
```

Leave the trailing `// })->name('dashboard');` comment alone or delete it — either is fine.

- [ ] **Step 4: Run regression tests**

Run:
```bash
php artisan test --filter=DashboardTest
```

Expected: the 4 tests from Task 3 still PASS.

- [ ] **Step 5: Commit**

```bash
git add app/Http/Controllers/DashboardController.php routes/web.php
git commit -m "refactor: extract /dashboard closure to DashboardController

Co-Authored-By: Claude Opus 4.6 (1M context) <noreply@anthropic.com>"
```

---

## Task 5: Add `showChooser` action and chooser route

**Files:**
- Modify: `app/Http/Controllers/DashboardController.php`
- Modify: `routes/web.php`
- Modify: `tests/Feature/DashboardTest.php`

- [ ] **Step 1: Add failing feature tests for the chooser route**

Append to `tests/Feature/DashboardTest.php` inside the class:

```php
public function test_multi_role_staff_user_sees_chooser_page(): void
{
    $user = User::factory()->create(['password_change_at' => now()]);
    $user->assignRole(['staff', 'super-administrator']);

    $response = $this->actingAs($user)->get('/dashboard/choose-mode');

    $response->assertInertia(fn ($page) => $page
        ->component('Dashboard/ChooseMode')
        ->has('staffOption')
        ->has('otherOption')
        ->where('staffOption.mode', 'staff')
        ->where('otherOption.mode', 'other')
    );
}

public function test_chooser_shows_admin_option_when_user_has_admin_access(): void
{
    $user = User::factory()->create(['password_change_at' => now()]);
    $user->assignRole(['staff', 'super-administrator']);

    $response = $this->actingAs($user)->get('/dashboard/choose-mode');

    $response->assertInertia(fn ($page) => $page
        ->where('otherOption.label', 'Go to admin dashboard')
    );
}

public function test_chooser_shows_staff_list_option_when_user_has_no_admin_access(): void
{
    $user = User::factory()->create(['password_change_at' => now()]);
    // staff + some non-admin role. Use an existing seeded role without 'view dashboard' permission.
    // If 'hr-user' exists in seeders, use it. Otherwise, use another seeded role that lacks
    // the 'view dashboard' permission (check database/seeders/AssignRolePermissionSeeder.php).
    $user->assignRole(['staff', 'hr-user']);

    $response = $this->actingAs($user)->get('/dashboard/choose-mode');

    $response->assertInertia(fn ($page) => $page
        ->where('otherOption.label', 'Go to staff list')
    );
}

public function test_chooser_redirects_non_multi_role_user_to_dashboard(): void
{
    $user = User::factory()->create(['password_change_at' => now()]);
    $user->assignRole('staff');

    $response = $this->actingAs($user)->get('/dashboard/choose-mode');

    $response->assertRedirect(route('dashboard'));
}
```

> **Note:** `hr-user` is referenced in the existing `AuthorizationTest.php` (lines 296-297), so it's a seeded role. If it does grant `view dashboard`, pick a different non-admin seeded role by inspecting `database/seeders/AssignRolePermissionSeeder.php`.

- [ ] **Step 2: Run tests — verify they fail**

Run:
```bash
php artisan test --filter=DashboardTest
```

Expected: the 4 new tests FAIL with 404 Not Found (route doesn't exist). The 4 from Task 3 still pass.

- [ ] **Step 3: Add the `showChooser` action to `DashboardController`**

Add these imports at the top of `app/Http/Controllers/DashboardController.php`:

```php
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
```

Add the method inside the class (after `index()`):

```php
public function showChooser(Request $request): RedirectResponse|InertiaResponse
{
    /** @var User $user */
    $user = $request->user();

    if (! $user->isMultiRoleStaff()) {
        return redirect()->route('dashboard');
    }

    $canAdmin = $user->canAccessAdminDashboard();

    return Inertia::render('Dashboard/ChooseMode', [
        'staffOption' => [
            'label' => 'View my staff record',
            'description' => 'Go to your personal staff page.',
            'mode' => 'staff',
        ],
        'otherOption' => [
            'label' => $canAdmin ? 'Go to admin dashboard' : 'Go to staff list',
            'description' => $canAdmin
                ? 'Continue to the institution dashboard with your administrative permissions.'
                : 'Continue to the staff directory.',
            'mode' => 'other',
        ],
    ]);
}
```

- [ ] **Step 4: Register the route**

In `routes/web.php`, immediately after the `/dashboard` route, add:

```php
Route::get('/dashboard/choose-mode', [DashboardController::class, 'showChooser'])
    ->middleware(['auth', 'password_changed', 'verified'])
    ->name('dashboard.choose-mode');
```

- [ ] **Step 5: Run tests — verify they pass**

Run:
```bash
php artisan test --filter=DashboardTest
```

Expected: all 8 tests PASS.

- [ ] **Step 6: Commit**

```bash
git add app/Http/Controllers/DashboardController.php routes/web.php tests/Feature/DashboardTest.php
git commit -m "feat: add dashboard view-mode chooser page

Co-Authored-By: Claude Opus 4.6 (1M context) <noreply@anthropic.com>"
```

---

## Task 6: Add `SwitchViewModeRequest` + `switchMode` action + route

**Files:**
- Create: `app/Http/Requests/SwitchViewModeRequest.php`
- Modify: `app/Http/Controllers/DashboardController.php`
- Modify: `routes/web.php`
- Modify: `tests/Feature/DashboardTest.php`

- [ ] **Step 1: Scaffold the form request**

Run:
```bash
php artisan make:request SwitchViewModeRequest --no-interaction
```

Expected: `Request [app/Http/Requests/SwitchViewModeRequest.php] created successfully.`

- [ ] **Step 2: Implement authorize + rules**

Replace the file contents of `app/Http/Requests/SwitchViewModeRequest.php` with:

```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SwitchViewModeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isMultiRoleStaff() ?? false;
    }

    public function rules(): array
    {
        return [
            'mode' => ['required', 'string', 'in:staff,other'],
        ];
    }
}
```

- [ ] **Step 3: Add failing feature tests for `switchMode`**

Append to `tests/Feature/DashboardTest.php` inside the class:

```php
public function test_switch_mode_to_staff_sets_session_and_redirects_to_dashboard(): void
{
    $user = User::factory()->create(['password_change_at' => now()]);
    $user->assignRole(['staff', 'super-administrator']);

    $response = $this->actingAs($user)
        ->post('/dashboard/switch-mode', ['mode' => 'staff']);

    $response->assertRedirect(route('dashboard'));
    $this->assertSame('staff', session('view_mode'));
}

public function test_switch_mode_to_other_sets_session_and_redirects_to_dashboard(): void
{
    $user = User::factory()->create(['password_change_at' => now()]);
    $user->assignRole(['staff', 'super-administrator']);

    $response = $this->actingAs($user)
        ->post('/dashboard/switch-mode', ['mode' => 'other']);

    $response->assertRedirect(route('dashboard'));
    $this->assertSame('other', session('view_mode'));
}

public function test_switch_mode_rejects_invalid_mode(): void
{
    $user = User::factory()->create(['password_change_at' => now()]);
    $user->assignRole(['staff', 'super-administrator']);

    $response = $this->actingAs($user)
        ->from('/dashboard/choose-mode')
        ->post('/dashboard/switch-mode', ['mode' => 'bogus']);

    $response->assertSessionHasErrors('mode');
    $this->assertNull(session('view_mode'));
}

public function test_switch_mode_rejects_non_multi_role_user(): void
{
    $user = User::factory()->create(['password_change_at' => now()]);
    $user->assignRole('staff');

    $response = $this->actingAs($user)
        ->post('/dashboard/switch-mode', ['mode' => 'other']);

    $response->assertForbidden();
    $this->assertNull(session('view_mode'));
}
```

- [ ] **Step 4: Run tests — verify they fail**

Run:
```bash
php artisan test --filter=DashboardTest
```

Expected: the 4 new tests FAIL with 404 Not Found (route doesn't exist). The 8 prior tests still pass.

- [ ] **Step 5: Add `switchMode` action**

Add this import at the top of `app/Http/Controllers/DashboardController.php`:

```php
use App\Http\Requests\SwitchViewModeRequest;
```

Add the method inside the class (after `showChooser()`):

```php
public function switchMode(SwitchViewModeRequest $request): RedirectResponse
{
    $request->session()->put('view_mode', $request->validated('mode'));

    return redirect()->route('dashboard');
}
```

- [ ] **Step 6: Register the route**

In `routes/web.php`, immediately after the `/dashboard/choose-mode` route, add:

```php
Route::post('/dashboard/switch-mode', [DashboardController::class, 'switchMode'])
    ->middleware(['auth', 'password_changed', 'verified'])
    ->name('dashboard.switch-mode');
```

- [ ] **Step 7: Run tests — verify they pass**

Run:
```bash
php artisan test --filter=DashboardTest
```

Expected: all 12 tests PASS.

- [ ] **Step 8: Commit**

```bash
git add app/Http/Controllers/DashboardController.php app/Http/Requests/SwitchViewModeRequest.php routes/web.php tests/Feature/DashboardTest.php
git commit -m "feat: add dashboard switch-mode endpoint

Co-Authored-By: Claude Opus 4.6 (1M context) <noreply@anthropic.com>"
```

---

## Task 7: Multi-role branching in `DashboardController::index`

Route the user based on their session `view_mode`. This is the behavior change that fixes the bug.

**Files:**
- Modify: `app/Http/Controllers/DashboardController.php`
- Modify: `tests/Feature/DashboardTest.php`

- [ ] **Step 1: Add failing tests for multi-role branching**

Append to `tests/Feature/DashboardTest.php` inside the class:

```php
public function test_multi_role_staff_without_session_mode_redirects_to_chooser(): void
{
    $user = User::factory()->create(['password_change_at' => now()]);
    $user->assignRole(['staff', 'super-administrator']);

    $response = $this->actingAs($user)->get('/dashboard');

    $response->assertRedirect(route('dashboard.choose-mode'));
}

public function test_multi_role_staff_with_staff_mode_redirects_to_staff_landing(): void
{
    $user = User::factory()->create(['password_change_at' => now()]);
    $user->assignRole(['staff', 'super-administrator']);

    $response = $this->actingAs($user)
        ->withSession(['view_mode' => 'staff'])
        ->get('/dashboard');

    // No person attached, so falls through to staff.index.
    $response->assertRedirect(route('staff.index'));
}

public function test_multi_role_staff_with_other_mode_as_admin_redirects_to_institution_show(): void
{
    \App\Models\Institution::factory()->create();

    $user = User::factory()->create(['password_change_at' => now()]);
    $user->assignRole(['staff', 'super-administrator']);

    $response = $this->actingAs($user)
        ->withSession(['view_mode' => 'other'])
        ->get('/dashboard');

    $response->assertRedirect(route('institution.show', [1]));
}

public function test_multi_role_staff_with_other_mode_no_admin_redirects_to_staff_index(): void
{
    $user = User::factory()->create(['password_change_at' => now()]);
    $user->assignRole(['staff', 'hr-user']);

    $response = $this->actingAs($user)
        ->withSession(['view_mode' => 'other'])
        ->get('/dashboard');

    $response->assertRedirect(route('staff.index'));
}

public function test_multi_role_staff_with_other_mode_admin_no_institutions_redirects_to_institution_index(): void
{
    \App\Models\Institution::query()->delete();

    $user = User::factory()->create(['password_change_at' => now()]);
    $user->assignRole(['staff', 'super-administrator']);

    $response = $this->actingAs($user)
        ->withSession(['view_mode' => 'other'])
        ->get('/dashboard');

    $response->assertRedirect(route('institution.index'));
}
```

- [ ] **Step 2: Run tests — verify the new ones fail**

Run:
```bash
php artisan test --filter=DashboardTest
```

Expected: 5 new tests FAIL. `test_multi_role_staff_without_session_mode_redirects_to_chooser` fails because the current `index()` sends staff users to the staff landing (the bug). The other 4 similarly land on staff.show.

- [ ] **Step 3: Update `index()` to handle multi-role users**

In `app/Http/Controllers/DashboardController.php`, replace the current `index()` method with:

```php
public function index(Request $request): RedirectResponse
{
    $request->session()->reflash();

    /** @var User $user */
    $user = $request->user();

    if ($user->isMultiRoleStaff()) {
        return $this->routeMultiRoleUser($user, $request->session()->get('view_mode'));
    }

    if ($user->hasRole('staff')) {
        return $this->redirectToStaffLanding($user);
    }

    if ($user->canAccessAdminDashboard()) {
        return $this->redirectToAdminDashboard();
    }

    return redirect()->route('staff.index');
}

private function routeMultiRoleUser(User $user, ?string $mode): RedirectResponse
{
    if ($mode === 'staff') {
        return $this->redirectToStaffLanding($user);
    }

    if ($mode === 'other') {
        return $this->redirectToOtherLanding($user);
    }

    return redirect()->route('dashboard.choose-mode');
}

private function redirectToOtherLanding(User $user): RedirectResponse
{
    if ($user->canAccessAdminDashboard()) {
        return $this->redirectToAdminDashboard();
    }

    return redirect()->route('staff.index');
}
```

Keep the existing `redirectToStaffLanding` and `redirectToAdminDashboard` private helpers unchanged.

- [ ] **Step 4: Run tests — verify all pass**

Run:
```bash
php artisan test --filter=DashboardTest
```

Expected: all 17 tests PASS.

- [ ] **Step 5: Commit**

```bash
git add app/Http/Controllers/DashboardController.php tests/Feature/DashboardTest.php
git commit -m "feat: route multi-role staff users via session view_mode

Fixes the bug where users with 'staff' plus another role were locked
into the staff dashboard. Multi-role users are now redirected to a
chooser on first /dashboard visit, and subsequent visits honor their
session-scoped view_mode selection.

Co-Authored-By: Claude Opus 4.6 (1M context) <noreply@anthropic.com>"
```

---

## Task 8: Share view-mode props via Inertia

**Files:**
- Modify: `app/Http/Middleware/HandleInertiaRequests.php`
- Modify: `tests/Feature/DashboardTest.php`

- [ ] **Step 1: Add failing test for shared props**

Append to `tests/Feature/DashboardTest.php` inside the class:

```php
public function test_inertia_shares_view_mode_props_for_multi_role_staff(): void
{
    $user = User::factory()->create(['password_change_at' => now()]);
    $user->assignRole(['staff', 'super-administrator']);

    $response = $this->actingAs($user)
        ->withSession(['view_mode' => 'other'])
        ->get('/dashboard/choose-mode');

    $response->assertInertia(fn ($page) => $page
        ->where('auth.viewMode', 'other')
        ->where('auth.isMultiRoleStaff', true)
        ->where('auth.viewModeLabel', 'Admin')
    );
}

public function test_inertia_view_mode_label_is_other_when_user_has_no_admin_access(): void
{
    $user = User::factory()->create(['password_change_at' => now()]);
    $user->assignRole(['staff', 'hr-user']);

    $response = $this->actingAs($user)
        ->get('/dashboard/choose-mode');

    $response->assertInertia(fn ($page) => $page
        ->where('auth.isMultiRoleStaff', true)
        ->where('auth.viewModeLabel', 'Other')
    );
}

public function test_inertia_isMultiRoleStaff_is_false_for_pure_staff_user(): void
{
    $user = User::factory()->create(['password_change_at' => now()]);
    $user->assignRole('staff');

    // Pure staff is redirected from /dashboard/choose-mode; hit a page that renders.
    // We need any authenticated Inertia page — use /permission if they can view,
    // otherwise pick another existing Inertia page. Here we send them to /dashboard
    // which redirects to staff.index (non-Inertia). So we assert via the redirect
    // target's shared props by following the redirect.
    $response = $this->actingAs($user)->get('/staff');

    $response->assertInertia(fn ($page) => $page
        ->where('auth.isMultiRoleStaff', false)
        ->where('auth.viewMode', null)
    );
}
```

> **Note:** The third test hits `/staff` because it's a known Inertia page. If `/staff` isn't reachable for this user due to permissions, substitute any Inertia page the pure-staff user can view (check routes/web.php + permission seeder). The goal is simply to assert the shared props' shape.

- [ ] **Step 2: Run tests — verify they fail**

Run:
```bash
php artisan test --filter=DashboardTest
```

Expected: 3 new tests FAIL (`auth.viewMode` / `auth.isMultiRoleStaff` / `auth.viewModeLabel` keys don't exist on the shared props yet).

- [ ] **Step 3: Share the props**

Open `app/Http/Middleware/HandleInertiaRequests.php`. Replace the `share()` method with:

```php
public function share(Request $request)
{
    return array_merge(parent::share($request), [
        'auth' => [
            'user' => fn () => $request->user()?->only('id', 'name', 'email'),
            'roles' => fn () => $request->user()?->getRoleNames(),
            'permissions' => fn () => $request->user()?->getAllPermissions()->pluck('name'),
            'viewMode' => fn () => $request->session()->get('view_mode'),
            'isMultiRoleStaff' => fn () => $request->user()?->isMultiRoleStaff() ?? false,
            'viewModeLabel' => fn () => $this->resolveViewModeLabel($request->user()),
        ],
        'ziggy' => function () use ($request) {
            return array_merge((new \Tightenco\Ziggy\Ziggy)->toArray(), [
                'location' => $request->url(),
            ]);
        },
        'flash' => [
            'success' => fn () => $request->session()->get('success'),
            'error' => fn () => $request->session()->get('error'),
            'warning' => fn () => $request->session()->get('warning'),
            'info' => fn () => $request->session()->get('info'),
        ],
    ]);
}

private function resolveViewModeLabel(?\App\Models\User $user): ?string
{
    if (! $user?->isMultiRoleStaff()) {
        return null;
    }

    return $user->canAccessAdminDashboard() ? 'Admin' : 'Other';
}
```

> **Note:** Keep the existing `Ziggy` import style that's already in the file — if it uses `use Tightenco\Ziggy\Ziggy;` at the top, drop the fully-qualified `\Tightenco\Ziggy\Ziggy` above and use `Ziggy` instead. Don't introduce a duplicate import.

- [ ] **Step 4: Run tests — verify they pass**

Run:
```bash
php artisan test --filter=DashboardTest
```

Expected: all 20 tests PASS.

- [ ] **Step 5: Commit**

```bash
git add app/Http/Middleware/HandleInertiaRequests.php tests/Feature/DashboardTest.php
git commit -m "feat: share view-mode props with Inertia for multi-role users

Co-Authored-By: Claude Opus 4.6 (1M context) <noreply@anthropic.com>"
```

---

## Task 9: `Dashboard/ChooseMode.vue` page

Frontend page shown to multi-role users at `/dashboard/choose-mode`.

**Files:**
- Create: `resources/js/Pages/Dashboard/ChooseMode.vue`

- [ ] **Step 1: Check the existing layout import convention**

Inspect a sibling page, e.g. `resources/js/Pages/Job/Index.vue`. Note the exact layout import path — typically `@/Layouts/NewAuthenticated.vue` or `@/Layouts/AuthenticatedLayout.vue`. Use the same path in Step 2.

- [ ] **Step 2: Create the page component**

Create `resources/js/Pages/Dashboard/ChooseMode.vue`:

```vue
<script setup>
import MainLayout from "@/Layouts/NewAuthenticated.vue";
import { Head, router } from "@inertiajs/vue3";

defineProps({
    staffOption: { type: Object, required: true },
    otherOption: { type: Object, required: true },
});

function chooseMode(mode) {
    router.post(route("dashboard.switch-mode"), { mode });
}
</script>

<template>
    <MainLayout>
        <Head title="Choose your view" />
        <main class="mx-auto max-w-3xl px-4 py-12">
            <h1
                class="text-2xl font-semibold text-gray-900 dark:text-gray-50"
            >
                How would you like to continue?
            </h1>
            <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">
                You have access to multiple views. Pick one to land on —
                you can switch any time using the header.
            </p>
            <div class="mt-8 grid gap-6 md:grid-cols-2">
                <button
                    type="button"
                    class="rounded-lg border border-gray-200 bg-white p-6 text-left shadow-sm transition hover:border-indigo-500 hover:shadow dark:border-gray-700 dark:bg-gray-800"
                    @click="chooseMode(staffOption.mode)"
                >
                    <h2
                        class="text-lg font-semibold text-gray-900 dark:text-gray-50"
                    >
                        {{ staffOption.label }}
                    </h2>
                    <p
                        class="mt-2 text-sm text-gray-600 dark:text-gray-300"
                    >
                        {{ staffOption.description }}
                    </p>
                </button>
                <button
                    type="button"
                    class="rounded-lg border border-gray-200 bg-white p-6 text-left shadow-sm transition hover:border-indigo-500 hover:shadow dark:border-gray-700 dark:bg-gray-800"
                    @click="chooseMode(otherOption.mode)"
                >
                    <h2
                        class="text-lg font-semibold text-gray-900 dark:text-gray-50"
                    >
                        {{ otherOption.label }}
                    </h2>
                    <p
                        class="mt-2 text-sm text-gray-600 dark:text-gray-300"
                    >
                        {{ otherOption.description }}
                    </p>
                </button>
            </div>
        </main>
    </MainLayout>
</template>
```

> **If the layout path differs**, change the `import MainLayout from "@/Layouts/NewAuthenticated.vue";` line to match the convention you observed in Step 1.

- [ ] **Step 3: Lint and build**

Run:
```bash
npm run lint && npm run build
```

Expected: lint passes with no new warnings; build completes successfully.

- [ ] **Step 4: Re-run feature tests**

Run:
```bash
php artisan test --filter=DashboardTest
```

Expected: all 20 tests still PASS (the Inertia component name assertion from Task 5 is satisfied).

- [ ] **Step 5: Commit**

```bash
git add resources/js/Pages/Dashboard/ChooseMode.vue
git commit -m "feat: add Dashboard/ChooseMode page

Co-Authored-By: Claude Opus 4.6 (1M context) <noreply@anthropic.com>"
```

---

## Task 10: `ViewModeSwitcher.vue` component

Dropdown rendered in the top nav for multi-role users.

**Files:**
- Create: `resources/js/Components/ViewModeSwitcher.vue`

- [ ] **Step 1: Create the component**

Create `resources/js/Components/ViewModeSwitcher.vue`:

```vue
<script setup>
import { computed } from "vue";
import { usePage, router } from "@inertiajs/vue3";
import { Menu, MenuButton, MenuItems, MenuItem } from "@headlessui/vue";
import { ChevronDownIcon } from "@heroicons/vue/20/solid";

const page = usePage();

const isMultiRoleStaff = computed(
    () => page.props.auth.isMultiRoleStaff,
);
const viewMode = computed(() => page.props.auth.viewMode);
const viewModeLabel = computed(() => page.props.auth.viewModeLabel);

const buttonLabel = computed(() => {
    if (!viewMode.value) return "Choose view";
    if (viewMode.value === "staff") return "Viewing as: Staff";
    return `Viewing as: ${viewModeLabel.value}`;
});

const oppositeMode = computed(() =>
    viewMode.value === "staff" ? "other" : "staff",
);

const oppositeLabel = computed(() => {
    if (!viewMode.value) return "Go to chooser";
    if (oppositeMode.value === "staff") return "Switch to Staff view";
    return `Switch to ${viewModeLabel.value} view`;
});

function handleClick() {
    if (!viewMode.value) {
        router.visit(route("dashboard.choose-mode"));
        return;
    }
    router.post(route("dashboard.switch-mode"), {
        mode: oppositeMode.value,
    });
}
</script>

<template>
    <Menu v-if="isMultiRoleStaff" as="div" class="relative">
        <MenuButton
            class="flex items-center gap-1 rounded-md px-3 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-100 dark:text-gray-50 dark:hover:bg-gray-700"
        >
            {{ buttonLabel }}
            <ChevronDownIcon
                class="h-4 w-4 text-gray-400"
                aria-hidden="true"
            />
        </MenuButton>
        <transition
            enter-active-class="transition ease-out duration-100"
            enter-from-class="transform opacity-0 scale-95"
            enter-to-class="transform opacity-100 scale-100"
            leave-active-class="transition ease-in duration-75"
            leave-from-class="transform opacity-100 scale-100"
            leave-to-class="transform opacity-0 scale-95"
        >
            <MenuItems
                class="absolute right-0 z-10 mt-2 origin-top-right rounded-md bg-white dark:bg-gray-700 py-1 shadow-lg ring-1 ring-gray-900/5 focus:outline-none"
            >
                <MenuItem v-slot="{ active }">
                    <button
                        type="button"
                        :class="[
                            active ? 'bg-gray-50 dark:bg-gray-600' : '',
                            'block w-full px-3 py-1.5 text-left text-sm text-gray-900 dark:text-gray-50',
                        ]"
                        @click="handleClick"
                    >
                        {{ oppositeLabel }}
                    </button>
                </MenuItem>
            </MenuItems>
        </transition>
    </Menu>
</template>
```

- [ ] **Step 2: Lint and build**

Run:
```bash
npm run lint && npm run build
```

Expected: lint passes; build completes. If `@heroicons/vue/20/solid` import fails, swap to the version already used in `TopMenu.vue` (check its imports).

- [ ] **Step 3: Commit**

```bash
git add resources/js/Components/ViewModeSwitcher.vue
git commit -m "feat: add ViewModeSwitcher component

Co-Authored-By: Claude Opus 4.6 (1M context) <noreply@anthropic.com>"
```

---

## Task 11: Mount `ViewModeSwitcher` in `TopMenu.vue`

**Files:**
- Modify: `resources/js/Components/TopMenu.vue`

- [ ] **Step 1: Add the component to the top nav**

Open `resources/js/Components/TopMenu.vue`. In the `<script setup>` block, add the import near the other component imports:

```js
import ViewModeSwitcher from "@/Components/ViewModeSwitcher.vue";
```

In the `<template>` block, locate the existing profile `Menu` dropdown (around lines 64-116 per the conventions exploration — a `<Menu as="div" class="relative">` containing `MenuButton` with the user image). Immediately **before** it, add:

```vue
<ViewModeSwitcher class="mr-2" />
```

If `TopMenu.vue` groups nav items differently, place the switcher inside the same flex container as the profile dropdown so they align horizontally.

- [ ] **Step 2: Lint and build**

Run:
```bash
npm run lint && npm run build
```

Expected: both pass.

- [ ] **Step 3: Manual verification**

Start the dev servers:

```bash
php artisan serve
```

In a second terminal:

```bash
npm run dev
```

Log in as a user with both `staff` and `super-administrator` roles (seed one via tinker if needed):

```bash
php artisan tinker
>>> $u = App\Models\User::factory()->create(['password_change_at' => now(), 'password' => bcrypt('password')]);
>>> $u->assignRole(['staff', 'super-administrator']);
>>> $u->email
```

Visit `/dashboard`. Expected:
1. Redirected to `/dashboard/choose-mode` — see two cards.
2. Click "Go to admin dashboard" — redirected to `/institution/1`.
3. In the header, see "Viewing as: Admin ▾".
4. Click the dropdown — see "Switch to Staff view".
5. Click it — redirected to `/staff` (no `person` attached).
6. Header now shows "Viewing as: Staff ▾".

> **Note to the implementer:** If any of these steps don't behave as described, stop and investigate before committing. Common issues: the switcher doesn't render → check the `isMultiRoleStaff` prop in browser devtools (`$page.props.auth.isMultiRoleStaff`); the POST fails with 419 → CSRF token missing from Inertia router call (should work out of the box).

- [ ] **Step 4: Commit**

```bash
git add resources/js/Components/TopMenu.vue
git commit -m "feat: mount ViewModeSwitcher in top nav

Co-Authored-By: Claude Opus 4.6 (1M context) <noreply@anthropic.com>"
```

---

## Task 12: Final quality gates

**Files:** none new.

- [ ] **Step 1: Format PHP**

Run:
```bash
./vendor/bin/pint --dirty
```

Expected: reports formatting changes if any; 0 errors. If any files were reformatted, review and continue.

- [ ] **Step 2: Lint and format JS/Vue**

Run:
```bash
npm run lint && npm run format
```

Expected: lint passes; format applies prettier if needed.

- [ ] **Step 3: Run the full test suite**

Run:
```bash
php artisan test
```

Expected: all tests PASS, including the 20 new ones in `DashboardTest` and 7 in `UserMultiRoleTest`.

- [ ] **Step 4: Verify no uncommitted changes are sneaking in**

Run:
```bash
git status
```

Expected: only files deliberately modified during this plan. If stray files appear, investigate before committing.

- [ ] **Step 5: Commit any formatting changes**

If Pint or Prettier reformatted anything:

```bash
git add -u
git commit -m "chore: apply Pint / Prettier formatting

Co-Authored-By: Claude Opus 4.6 (1M context) <noreply@anthropic.com>"
```

If nothing was reformatted, skip this step.

- [ ] **Step 6: Push and open a PR**

```bash
git push -u origin fix/multi-role-staff-dashboard-access
gh pr create --title "Fix: multi-role staff users locked out of non-staff dashboards" --body "$(cat <<'EOF'
## Summary
- Users with the `staff` role plus any other role (e.g., `super-administrator`, `hr-user`) were redirected straight to their staff record from `/dashboard`, bypassing the admin-dashboard branch.
- Extracted `/dashboard` logic from `routes/web.php` into `DashboardController`.
- Multi-role staff users now see a chooser page on first `/dashboard` visit, and a header dropdown (`ViewModeSwitcher`) lets them flip modes any time. Choice is session-scoped.
- Pure-staff and pure-admin users are unaffected.

## Test plan
- [x] `php artisan test --filter=DashboardTest` passes (20 tests)
- [x] `php artisan test --filter=UserMultiRoleTest` passes (7 tests)
- [x] Full test suite passes
- [x] Manual: log in as staff+super-admin, verify chooser → admin dashboard → header switcher → staff view round-trip
- [x] Manual: pure-staff user still lands on `staff.show` / `staff.index`
- [x] Manual: pure super-admin still lands on `institution.show`

🤖 Generated with [Claude Code](https://claude.com/claude-code)
EOF
)"
```

Expected: PR URL printed.

---

## Self-review notes (for plan author — not part of execution)

- Spec coverage: every spec section maps to a task (backend helpers → T1-2, route extraction → T4, chooser → T5+T9, switchMode → T6, multi-role branching → T7, Inertia props → T8, switcher UI → T10-11, tests → woven into each task, quality gates → T12).
- Edge cases covered: no-institutions (T7 step 1 final test), pure staff at chooser URL (T5 step 1 last test), non-multi-role POST to switch-mode (T6 step 3 last test), logout clearing session is inherent to Laravel and covered implicitly (the `withSession([...])` tests only apply per-request).
- Every code step contains real code; no "TBD" placeholders. Method names are consistent across tasks (`isMultiRoleStaff`, `canAccessAdminDashboard`, `redirectToStaffLanding`, `redirectToAdminDashboard`, `redirectToOtherLanding`, `routeMultiRoleUser`).
- One known ambiguity flagged inline with "Note:" callouts — the exact permission name `'view dashboard'` and seeded role `'hr-user'`. Implementer must verify against `database/seeders/`.
