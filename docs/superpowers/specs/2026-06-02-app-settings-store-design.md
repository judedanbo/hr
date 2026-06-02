# App Settings Store — Cycle 2a Design

**Date:** 2026-06-02
**Status:** Approved for planning
**Cycle:** 2a of the app-settings effort. Follow-ups: 2b (pagination retrofit), 2c (date-format retrofit), and password-interval enforcement (deferred — see "Out of scope").

## Goal

Add a persisted application-settings store with an admin management page, building on the settings hub from Cycle 1. This cycle delivers the store, the admin UI, seeded defaults, and the cheap/safe wiring (exposing settings to the frontend + showing the org name in branding). It **defines and exposes** `date_format` and `pagination_size` so the later retrofits (2b/2c) are pure consumption. Password-interval enforcement is intentionally deferred — the value is stored and editable, but not yet enforced.

## Storage — spatie/laravel-settings

The user approved adding the `spatie/laravel-settings` Composer dependency (CLAUDE.md requires approval for new deps; given here).

- `composer require spatie/laravel-settings`; publish its config (`config/settings.php`) and the migration that creates the `settings` table.
- Two settings classes (one per concern):
  - **`app/Settings/GeneralSettings.php`** — group `general`:
    - `org_name` (string)
    - `support_email` (?string)
    - `date_format` (string)
    - `pagination_size` (int)
  - **`app/Settings/SecuritySettings.php`** — group `security`:
    - `password_change_interval_days` (int)
- Register both classes in `config/settings.php` under `settings => [...]`.
- A spatie settings migration `database/settings/2026_06_02_000000_create_app_settings.php` (extends `Spatie\LaravelSettings\Migrations\SettingsMigration`) seeds defaults:
  - `general.org_name = 'HRMIS'`
  - `general.support_email = null`
  - `general.date_format = 'd M Y'`
  - `general.pagination_size = 10`
  - `security.password_change_interval_days = 90`
- These migrations run via `php artisan migrate`, so `RefreshDatabase` seeds them in tests.

## Admin Page

- **Permission:** new `update app settings`, added to `AllPermissionsSeeder` (assigned to super-administrator via its existing `syncPermissions`). Gates both the page and the save.
- **Routes** (mirroring the existing `settings.index` middleware style):
  - `GET /settings/app` → `AppSettingsController@edit`, name `app-settings.edit`
  - `PUT /settings/app` → `AppSettingsController@update`, name `app-settings.update`
  - both behind `auth`, `password_changed`, `can:update app settings`.
- **`AppSettingsController`** injects both settings classes:
  - `edit(GeneralSettings $general, SecuritySettings $security)` → `Inertia::render('Settings/App', [...current values...])`.
  - `update(UpdateAppSettingsRequest $request, GeneralSettings $general, SecuritySettings $security)` → assign validated values to each object, `$general->save()` / `$security->save()`, redirect back with success.
- **`UpdateAppSettingsRequest`** validation:
  - `org_name` — required, string, max:255
  - `support_email` — nullable, email, max:255
  - `date_format` — required, string, max:50
  - `pagination_size` — required, integer, min:5, max:100
  - `password_change_interval_days` — required, integer, min:0, max:3650 (0 = disabled, for the future enforcement step)
- **`resources/js/Pages/Settings/App.vue`** — green-clean form using Inertia `useForm`, organized into visual sections **Branding** (org_name, support_email), **Display** (date_format, pagination_size), **Security** (password_change_interval_days). Per-field validation errors and a Save button. Breadcrumb: Home / Settings / Application.

## Hub Integration

- Add an **"Application settings"** card to `Settings/Index.vue`, gated on `update app settings`, linking to `route('app-settings.edit')`. It is not a count card, so make `SettingCard`'s `count` prop optional (null → number hidden); the card shows title + a "Configure →" link with a secondary line ("Name, email, display, security").

## Wiring Into Behavior (this cycle)

- **Shared props:** add an `app` key in `HandleInertiaRequests::share()` exposing the **general** settings only — `org_name`, `support_email`, `date_format`, `pagination_size` (resolved from `GeneralSettings`; spatie caches settings so this is cheap). Frontend can read `$page.props.app.*`. (Security settings are not exposed to the frontend.)
- **Branding:** display `org_name` text beside the logo in `resources/js/Layouts/NewAuthenticated.vue`, sourced from `$page.props.app.org_name`. (Today only an SVG logo shows; this adds the name.)

## Testing

- `tests/Feature/AppSettingsTest.php`:
  - An admin with `update app settings` gets `Settings/App` with the current general + security values.
  - A valid `PUT` persists new values (re-resolve `GeneralSettings`/`SecuritySettings` and assert) and redirects with success.
  - Validation rejects a bad `support_email` and an out-of-range `pagination_size`.
  - A user **without** `update app settings` is forbidden (403) on both `edit` and `update`.
- Shared-props coverage: a test (or assertion) that an authenticated page exposes `app.org_name` reflecting the stored value.
- No middleware/enforcement test this cycle (enforcement deferred).

## Out of Scope (follow-ups)

- **Password-interval enforcement** — wiring `password_change_interval_days` into the `PasswordChanged` middleware (and adding a `password_change_at` datetime cast to `User`). Deferred; the value is stored and editable now.
- **2b — pagination retrofit:** replace the 44 hardcoded `paginate(N)` sites across 26 controllers with `pagination_size`. Its own spec/plan.
- **2c — date-format retrofit:** replace the ~180 hardcoded date-format sites (backend `->format()` + frontend) with `date_format`. Its own spec/plan.
