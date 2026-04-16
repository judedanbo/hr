# Multi-role staff dashboard access — design

## Problem

Users with the `staff` role **plus any additional role** (e.g., `super-administrator`, `hr-user`) cannot access non-staff features via the dashboard. The `/dashboard` route in `routes/web.php:123-151` uses first-match-wins logic that checks `hasRole('staff')` before any other role, redirecting multi-role users to their own staff record and never reaching the admin-dashboard branch.

```php
// routes/web.php:128-136 — the bug
if ($user->hasRole('staff')) {
    if ($user->person) {
        return redirect()
            ->route('staff.show', [$user->person->institution->first()->staff->id]);
    }
    return redirect()->route('staff.index');
}
```

No middleware, nav, policy, or controller outside this block treats staff as exclusive. The fix is localized, but the chosen UX introduces a small runtime "view mode" concept.

## Goals

1. Multi-role staff users can reach their non-staff role's features from the dashboard.
2. Users with only the `staff` role are unaffected.
3. Users with only non-staff roles are unaffected.
4. Minimal surface area: no new permissions, no DB migrations, no policy changes.

## Non-goals

- Scoping the whole app by view mode (admin pages remain reachable in "staff mode" if the user's permissions allow). View mode affects only the dashboard landing and the switcher UI.
- Persisting mode across sessions or devices. Session-scoped only.
- Role-based nav filtering. Existing permission-based nav is unchanged.

## UX

### Chooser

When a multi-role staff user hits `/dashboard` without a `view_mode` in their session, they are redirected to `/dashboard/choose-mode`, an Inertia page with two cards:

- **"View my staff record"** — posts `{ mode: 'staff' }` to `/dashboard/switch-mode`, then lands on `staff.show` for their own record (or `staff.index` if no `person` relationship).
- **"Go to admin dashboard"** OR **"Go to staff list"** — posts `{ mode: 'other' }`, then lands on `institution.show` (if they have `view dashboard` permission or `super-administrator`) or `staff.index` otherwise.

The label and description for the second card are supplied by the backend, since it already knows the user's permissions.

### Header switcher

A small dropdown in `AuthenticatedLayout.vue`'s top nav, visible only for multi-role staff users. Shows the current mode and lets the user flip to the other mode in one click.

- Button label: `Viewing as: Staff ▾` / `Viewing as: Admin ▾` / `Viewing as: Other ▾` (label matches the "other" option from the chooser).
- Before a mode is chosen: `Choose view ▾` with a single dropdown item linking to the chooser.
- After a mode is chosen: one dropdown item showing the opposite mode. Clicking it posts to `/dashboard/switch-mode` and Inertia redirects to `/dashboard` (which short-circuits to the chosen destination).

### Revisiting `/dashboard`

After a mode is chosen, `GET /dashboard` goes straight to that mode's landing page. No re-chooser. To switch modes, users use the header switcher.

## Architecture

### State

- `session('view_mode')` — one of `'staff'`, `'other'`, or unset. Cleared on logout by Laravel's normal session lifecycle. No DB column, no cookie.

### Flow

```
GET /dashboard
│
├─ Not multi-role staff? → existing logic unchanged
│   - super-admin / view-dashboard → institution.show
│   - pure staff → staff.show (own record) or staff.index fallback
│   - else → staff.index
│
└─ Multi-role staff?
    ├─ session('view_mode') = 'staff' → staff.show (or staff.index fallback)
    ├─ session('view_mode') = 'other' → institution.show (if admin access) else staff.index
    │   (if admin access but Institution::count() < 1, flash info + redirect to institution.index — preserves existing behavior)
    └─ unset → redirect to /dashboard/choose-mode
```

### Who's a "multi-role staff user"?

Any user with the `staff` role **and** at least one additional role of any kind. Encapsulated in `User::isMultiRoleStaff()`.

## Components

### Backend

**New files:**

- `app/Http/Controllers/DashboardController.php`
  - `index(Request $request)` — the logic moved from the routes closure. Reads `session('view_mode')`, branches per the flow above.
  - `showChooser(Request $request)` — returns `Inertia::render('Dashboard/ChooseMode', [...])` with props describing the two options. Redirects to `/dashboard` if the user isn't multi-role.
  - `switchMode(SwitchViewModeRequest $request)` — validates mode, writes `session(['view_mode' => $mode])`, redirects to `route('dashboard')`.

- `app/Http/Requests/SwitchViewModeRequest.php`
  - `authorize()`: `$this->user()->isMultiRoleStaff()`.
  - `rules()`: `['mode' => 'required|in:staff,other']`.

**Modified files:**

- `app/Models/User.php` — add two helpers:
  - `isMultiRoleStaff(): bool` → `$this->hasRole('staff') && $this->roles->count() > 1`
  - `resolveOtherModeRoute(): string` → returns `'institution.show'` if admin access, else `'staff.index'`. Used by both `index()` and `showChooser()` so branch logic is not duplicated.

- `routes/web.php` — replace the `/dashboard` closure with:
  ```php
  Route::get('/dashboard', [DashboardController::class, 'index'])
      ->middleware(['auth', 'password_changed', 'verified'])
      ->name('dashboard');
  Route::get('/dashboard/choose-mode', [DashboardController::class, 'showChooser'])
      ->middleware(['auth', 'password_changed', 'verified'])
      ->name('dashboard.choose-mode');
  Route::post('/dashboard/switch-mode', [DashboardController::class, 'switchMode'])
      ->middleware(['auth', 'password_changed', 'verified'])
      ->name('dashboard.switch-mode');
  ```

- `app/Http/Middleware/HandleInertiaRequests.php` — share two additional props under `auth`:
  - `viewMode`: `session('view_mode')` (may be `null`)
  - `isMultiRoleStaff`: boolean
  - `viewModeLabel`: human-readable label for the "other" destination (`'Admin'` or `'Other'`)

### Frontend

**New files:**

- `resources/js/Pages/Dashboard/ChooseMode.vue` — Inertia page. Renders inside `AuthenticatedLayout`. Two cards side-by-side (stack on mobile, per Tailwind conventions). Each card is a `<form>` that calls `router.post(route('dashboard.switch-mode'), { mode })`.

- `resources/js/Components/ViewModeSwitcher.vue` — small dropdown component using the existing HeadlessUI `Menu` / `MenuItem` pattern already present in the layout's user dropdown. Reads `$page.props.auth.viewMode`, `isMultiRoleStaff`, and `viewModeLabel`. Emits nothing; posts and lets Inertia handle the redirect.

**Modified files:**

- `resources/js/Layouts/AuthenticatedLayout.vue` — render `<ViewModeSwitcher />` in the top nav, gated on `$page.props.auth.isMultiRoleStaff`.

## Authorization

- `GET /dashboard` — `auth`, `password_changed`, `verified` (unchanged stack).
- `GET /dashboard/choose-mode` — same stack. Additional in-controller check: redirect to `/dashboard` if not multi-role (prevents confusion for single-role users who type the URL).
- `POST /dashboard/switch-mode` — same stack. `SwitchViewModeRequest::authorize()` rejects non-multi-role users with 403. Validation rejects bad modes with 422.

## Testing

### New feature tests — `tests/Feature/DashboardViewModeTest.php`

- `test_pure_staff_user_redirects_to_own_staff_record` — regression.
- `test_super_admin_without_staff_role_redirects_to_institution_dashboard` — regression.
- `test_multi_role_staff_user_without_session_mode_sees_chooser` — GET `/dashboard` redirects to `/dashboard/choose-mode` with Inertia component `Dashboard/ChooseMode`.
- `test_multi_role_staff_with_view_dashboard_chooser_shows_admin_option` — prop `otherOption.label` contains "admin dashboard".
- `test_multi_role_staff_without_view_dashboard_chooser_shows_staff_list_option` — prop `otherOption.label` contains "staff list".
- `test_switch_mode_to_staff_redirects_to_own_record` — POST `mode=staff`, then GET `/dashboard` → `staff.show`.
- `test_switch_mode_to_other_as_admin_redirects_to_institution_dashboard` — POST `mode=other`, then GET `/dashboard` → `institution.show`.
- `test_switch_mode_to_other_without_admin_access_redirects_to_staff_index` — POST `mode=other`, then GET `/dashboard` → `staff.index`.
- `test_switch_mode_rejects_invalid_mode` — `mode=bogus` → 422.
- `test_switch_mode_rejects_non_multi_role_user` — pure staff user POST → 403.
- `test_chooser_page_rejects_non_multi_role_user` — pure staff user GET `/dashboard/choose-mode` → redirect to `/dashboard`.
- `test_logout_clears_view_mode` — session lifecycle regression.
- `test_switch_mode_to_other_with_no_institutions_redirects_to_institution_index` — preserves existing `Institution::count() < 1` branch.

### Unit tests — `tests/Unit/UserTest.php` (or new file)

- `test_is_multi_role_staff_returns_true_for_staff_plus_any_role`
- `test_is_multi_role_staff_returns_false_for_staff_only`
- `test_is_multi_role_staff_returns_false_for_admin_only`

### Existing test impact

- `tests/Feature/AuthorizationTest.php` lines 294-301 cover multi-role assignment at the model level. No changes expected.
- Any existing `/dashboard` feature test must continue to pass for pure-staff and pure-admin users.

## Edge cases

- **Staff user with no `person` relationship** — chooser still shows the "staff" card; picking it lands on `staff.index` (existing fallback preserved). `isMultiRoleStaff` returns true regardless of `person`.
- **Session expiry mid-session** — next `/dashboard` hit has no `view_mode` → chooser. Expected per session-scoped design.
- **Direct URL access to admin pages** (e.g., `/institution/1`) — view mode does not gate this. Authorization remains permission-based. Consistent with "landing only" decision.
- **Concurrent sessions** (phone + desktop) — each has its own `view_mode`. No cross-device syncing.
- **User gains or loses a role mid-session** — `isMultiRoleStaff` is recomputed on each request, so state converges naturally. If a user's second role is revoked mid-session, a stale `view_mode` is harmless: they stop being multi-role, and `index()` falls through to the existing single-role logic (ignoring the session key).
- **No institutions configured** — when routing a multi-role user to the "other" destination and they have admin access, the existing `Institution::count() < 1` branch must be preserved (flash info message, redirect to `institution.index`). This lives in `DashboardController::index` and is covered by a regression test.

## Quality gates

- `./vendor/bin/pint --dirty`
- `npm run lint`
- `npm run format` (if frontend files touched)
- `php artisan test --filter=DashboardViewMode` then full `php artisan test` suite
