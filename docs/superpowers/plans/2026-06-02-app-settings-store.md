# App Settings Store (Cycle 2a) Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add a persisted application-settings store (spatie/laravel-settings) with an admin management page, seeded defaults, frontend exposure, and org-name branding — without enforcing the password interval yet.

**Architecture:** Two settings classes (`GeneralSettings`, `SecuritySettings`) backed by spatie/laravel-settings with a defaults migration. An `AppSettingsController` + Form Request power a gated `/settings/app` edit page. General settings are shared to the frontend via `HandleInertiaRequests` and the org name is shown in the layout. A hub card links to the page.

**Tech Stack:** Laravel 11, PHP 8.4, spatie/laravel-settings (new dep, approved), Inertia v1, Vue 3 `<script setup>`, Tailwind 3, Spatie Permission, PHPUnit, Heroicons.

**Testing note:** No JS test runner exists (ESLint lints only `public/`; no vitest). Backend is covered by PHPUnit feature tests; frontend is verified by `npm run build` compiling cleanly. `tests/TestCase.php` sets `$seed = true` and uses `RefreshDatabase`, so `php artisan migrate` (including the spatie settings migration under `database/settings/`) seeds defaults before each test.

**Branch:** `feature/app-settings-store` (created; design committed there).

---

## File Structure

**Backend / store**
- Add dep: `spatie/laravel-settings` (composer).
- Create: `config/settings.php` (published, then edited to register classes).
- Create: `database/migrations/*_create_settings_table.php` (published by the package).
- Create: `app/Settings/GeneralSettings.php`, `app/Settings/SecuritySettings.php`.
- Create: `database/settings/2026_06_02_000000_create_app_settings.php` (defaults).
- Modify: `database/seeders/AllPermissionsSeeder.php` (add `update app settings`).

**Backend / admin**
- Create: `app/Http/Controllers/AppSettingsController.php`, `app/Http/Requests/UpdateAppSettingsRequest.php`.
- Modify: `routes/web.php` (two routes).
- Modify: `app/Http/Middleware/HandleInertiaRequests.php` (shared `app` prop).

**Frontend**
- Modify: `resources/js/Components/Settings/SettingCard.vue` (optional `count`).
- Create: `resources/js/Pages/Settings/App.vue` (settings form).
- Modify: `resources/js/Pages/Settings/Index.vue` (Application card).
- Modify: `resources/js/Layouts/NewAuthenticated.vue` (org name branding).

**Tests**
- Create: `tests/Feature/AppSettingsTest.php`.

---

## Task 1: Store foundation — package, settings classes, defaults, permission

**Files:**
- Add dep `spatie/laravel-settings`; publish `config/settings.php` + settings-table migration.
- Create: `app/Settings/GeneralSettings.php`, `app/Settings/SecuritySettings.php`
- Create: `database/settings/2026_06_02_000000_create_app_settings.php`
- Modify: `config/settings.php`, `database/seeders/AllPermissionsSeeder.php`
- Test: `tests/Feature/AppSettingsTest.php` (defaults + permission)

> Package install is not TDD-able; do Steps 1-6 (setup), then Step 7 writes a test that locks the seeded defaults and the new permission.

- [ ] **Step 1: Install the package**

Run: `composer require spatie/laravel-settings`
Expected: installs (v3.x, compatible with Laravel 11 / PHP 8.4).

- [ ] **Step 2: Publish config and the settings-table migration**

Run:
```bash
php artisan vendor:publish --provider="Spatie\LaravelSettings\LaravelSettingsServiceProvider" --tag="settings-config" --no-interaction
php artisan vendor:publish --provider="Spatie\LaravelSettings\LaravelSettingsServiceProvider" --tag="settings-migrations" --no-interaction
```
Expected: creates `config/settings.php` and a `database/migrations/*_create_settings_table.php`.
If a command reports "Nothing to publish", run `php artisan vendor:publish --provider="Spatie\LaravelSettings\LaravelSettingsServiceProvider"` once to list the exact tag names and use those instead. Do not invent tag names — report BLOCKED if they can't be found.

- [ ] **Step 3: Create the settings classes**

Create `app/Settings/GeneralSettings.php`:
```php
<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class GeneralSettings extends Settings
{
    public string $org_name;

    public ?string $support_email;

    public string $date_format;

    public int $pagination_size;

    public static function group(): string
    {
        return 'general';
    }
}
```

Create `app/Settings/SecuritySettings.php`:
```php
<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class SecuritySettings extends Settings
{
    public int $password_change_interval_days;

    public static function group(): string
    {
        return 'security';
    }
}
```

- [ ] **Step 4: Register the classes in `config/settings.php`**

In `config/settings.php`, find the `'settings' => [` array and set it to include both classes:
```php
    'settings' => [
        \App\Settings\GeneralSettings::class,
        \App\Settings\SecuritySettings::class,
    ],
```
Leave the rest of the published config unchanged.

- [ ] **Step 5: Create the defaults migration**

Create `database/settings/2026_06_02_000000_create_app_settings.php`:
```php
<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('general.org_name', 'HRMIS');
        $this->migrator->add('general.support_email', null);
        $this->migrator->add('general.date_format', 'd M Y');
        $this->migrator->add('general.pagination_size', 10);
        $this->migrator->add('security.password_change_interval_days', 90);
    }
};
```

- [ ] **Step 6: Add the `update app settings` permission**

In `database/seeders/AllPermissionsSeeder.php`, in the `getAllPermissions()` array, add the line `'update app settings',` immediately after `'view admin settings',` (in the User Management / settings area). Add only that one line. (The seeder's `firstOrCreate` loop + `syncPermissions(Permission::all())` will create it and assign it to super-administrator.)

- [ ] **Step 7: Write the foundation test**

Create `tests/Feature/AppSettingsTest.php`:
```php
<?php

namespace Tests\Feature;

use App\Settings\GeneralSettings;
use App\Settings\SecuritySettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class AppSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_settings_have_seeded_defaults(): void
    {
        $general = app(GeneralSettings::class);
        $security = app(SecuritySettings::class);

        $this->assertSame('HRMIS', $general->org_name);
        $this->assertNull($general->support_email);
        $this->assertSame('d M Y', $general->date_format);
        $this->assertSame(10, $general->pagination_size);
        $this->assertSame(90, $security->password_change_interval_days);
    }

    public function test_update_app_settings_permission_is_seeded(): void
    {
        $this->assertTrue(
            Permission::where('name', 'update app settings')->exists(),
            "Permission 'update app settings' should be seeded"
        );
    }
}
```

- [ ] **Step 8: Run migrations and the test**

Run: `php artisan migrate --no-interaction` then `php artisan test --filter=AppSettingsTest`
Expected: PASS (2 tests). If the settings migration didn't run, confirm `config/settings.php` has `'migrations_paths' => [database_path('settings')]` (the package default) and that the `database/settings/` file is named correctly.

- [ ] **Step 9: Pint and commit**

```bash
vendor/bin/pint app/Settings tests/Feature/AppSettingsTest.php database/seeders/AllPermissionsSeeder.php
git add -f config/settings.php database/migrations/*_create_settings_table.php database/settings/2026_06_02_000000_create_app_settings.php database/seeders/AllPermissionsSeeder.php
git add app/Settings/GeneralSettings.php app/Settings/SecuritySettings.php tests/Feature/AppSettingsTest.php composer.json composer.lock
git commit -m "feat: add app settings store with general and security groups"
```
> Note: `database/` paths are gitignored-but-tracked in this repo, so `git add -f` is required for files under `database/`. Verify the commit includes the migration + settings files with `git show --stat HEAD`. Do not stage unrelated pre-existing changes (e.g. `database/seeders/AdminUserSeeder.php`).

---

## Task 2: Admin controller, request, routes

**Files:**
- Create: `app/Http/Controllers/AppSettingsController.php`
- Create: `app/Http/Requests/UpdateAppSettingsRequest.php`
- Modify: `routes/web.php`
- Test: `tests/Feature/AppSettingsTest.php` (append controller tests)

- [ ] **Step 1: Add the controller tests**

Append these methods to the existing `tests/Feature/AppSettingsTest.php` class (and add the imports `use App\Models\User;`, `use Inertia\Testing\AssertableInertia as Assert;` at the top):
```php
    public function test_admin_can_view_app_settings_page(): void
    {
        $admin = \App\Models\User::factory()->create();
        $admin->givePermissionTo('update app settings');

        $response = $this->actingAs($admin)->get(route('app-settings.edit'));

        $response->assertOk();
        $response->assertInertia(
            fn (\Inertia\Testing\AssertableInertia $page) => $page
                ->component('Settings/App')
                ->where('general.org_name', 'HRMIS')
                ->where('general.pagination_size', 10)
                ->where('security.password_change_interval_days', 90)
        );
    }

    public function test_admin_can_update_app_settings(): void
    {
        $admin = \App\Models\User::factory()->create();
        $admin->givePermissionTo('update app settings');

        $response = $this->actingAs($admin)->put(route('app-settings.update'), [
            'org_name' => 'New Org',
            'support_email' => 'help@example.com',
            'date_format' => 'Y-m-d',
            'pagination_size' => 25,
            'password_change_interval_days' => 30,
        ]);

        $response->assertRedirect(route('app-settings.edit'));
        $response->assertSessionHas('success');

        $this->assertSame('New Org', app(\App\Settings\GeneralSettings::class)->org_name);
        $this->assertSame('help@example.com', app(\App\Settings\GeneralSettings::class)->support_email);
        $this->assertSame('Y-m-d', app(\App\Settings\GeneralSettings::class)->date_format);
        $this->assertSame(25, app(\App\Settings\GeneralSettings::class)->pagination_size);
        $this->assertSame(30, app(\App\Settings\SecuritySettings::class)->password_change_interval_days);
    }

    public function test_update_rejects_invalid_input(): void
    {
        $admin = \App\Models\User::factory()->create();
        $admin->givePermissionTo('update app settings');

        $response = $this->actingAs($admin)
            ->from(route('app-settings.edit'))
            ->put(route('app-settings.update'), [
                'org_name' => '',
                'support_email' => 'not-an-email',
                'date_format' => 'd M Y',
                'pagination_size' => 9999,
                'password_change_interval_days' => 30,
            ]);

        $response->assertSessionHasErrors(['org_name', 'support_email', 'pagination_size']);
    }

    public function test_app_settings_require_permission(): void
    {
        $user = \App\Models\User::factory()->create();

        $this->actingAs($user)->get(route('app-settings.edit'))->assertForbidden();
        $this->actingAs($user)->put(route('app-settings.update'), [])->assertForbidden();
    }
```

- [ ] **Step 2: Run the tests, verify they FAIL**

Run: `php artisan test --filter=AppSettingsTest`
Expected: FAIL — `route('app-settings.edit')` does not exist yet.

- [ ] **Step 3: Create the Form Request**

Create `app/Http/Requests/UpdateAppSettingsRequest.php`:
```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAppSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'org_name' => ['required', 'string', 'max:255'],
            'support_email' => ['nullable', 'email', 'max:255'],
            'date_format' => ['required', 'string', 'max:50'],
            'pagination_size' => ['required', 'integer', 'min:5', 'max:100'],
            'password_change_interval_days' => ['required', 'integer', 'min:0', 'max:3650'],
        ];
    }
}
```

- [ ] **Step 4: Create the controller**

Create `app/Http/Controllers/AppSettingsController.php`:
```php
<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateAppSettingsRequest;
use App\Settings\GeneralSettings;
use App\Settings\SecuritySettings;
use Inertia\Inertia;
use Inertia\Response;

class AppSettingsController extends Controller
{
    public function edit(GeneralSettings $general, SecuritySettings $security): Response
    {
        return Inertia::render('Settings/App', [
            'general' => [
                'org_name' => $general->org_name,
                'support_email' => $general->support_email,
                'date_format' => $general->date_format,
                'pagination_size' => $general->pagination_size,
            ],
            'security' => [
                'password_change_interval_days' => $security->password_change_interval_days,
            ],
        ]);
    }

    public function update(UpdateAppSettingsRequest $request, GeneralSettings $general, SecuritySettings $security)
    {
        $data = $request->validated();

        $general->org_name = $data['org_name'];
        $general->support_email = $data['support_email'];
        $general->date_format = $data['date_format'];
        $general->pagination_size = $data['pagination_size'];
        $general->save();

        $security->password_change_interval_days = $data['password_change_interval_days'];
        $security->save();

        return redirect()->route('app-settings.edit')->with('success', 'Settings updated successfully');
    }
}
```

- [ ] **Step 5: Register the routes**

In `routes/web.php`, add the import near the other controller imports at the top:
```php
use App\Http\Controllers\AppSettingsController;
```
And add these two lines immediately after the `Route::get('/settings', SettingsController::class)...->name('settings.index');` line:
```php
Route::get('/settings/app', [AppSettingsController::class, 'edit'])->middleware(['auth', 'password_changed', 'can:update app settings'])->name('app-settings.edit');
Route::put('/settings/app', [AppSettingsController::class, 'update'])->middleware(['auth', 'password_changed', 'can:update app settings'])->name('app-settings.update');
```

- [ ] **Step 6: Run the tests, verify they PASS**

Run: `php artisan test --filter=AppSettingsTest`
Expected: PASS (6 tests). The `Settings/App` Inertia component does not need to exist on disk for these backend tests — `assertInertia` checks the response payload, not a rendered page.

- [ ] **Step 7: Pint and commit**

```bash
vendor/bin/pint app/Http/Controllers/AppSettingsController.php app/Http/Requests/UpdateAppSettingsRequest.php tests/Feature/AppSettingsTest.php
git add app/Http/Controllers/AppSettingsController.php app/Http/Requests/UpdateAppSettingsRequest.php tests/Feature/AppSettingsTest.php routes/web.php
git commit -m "feat: app settings edit/update controller, request, routes"
```

---

## Task 3: Expose general settings via shared Inertia props

**Files:**
- Modify: `app/Http/Middleware/HandleInertiaRequests.php`
- Test: `tests/Feature/AppSettingsTest.php` (append)

- [ ] **Step 1: Add the shared-props test**

Append to `tests/Feature/AppSettingsTest.php`:
```php
    public function test_app_settings_are_shared_to_frontend(): void
    {
        $admin = \App\Models\User::factory()->create();
        $admin->givePermissionTo(['view admin settings', 'update app settings']);

        $response = $this->actingAs($admin)->get(route('app-settings.edit'));

        $response->assertInertia(
            fn (\Inertia\Testing\AssertableInertia $page) => $page
                ->where('app.org_name', 'HRMIS')
                ->where('app.pagination_size', 10)
        );
    }
```

- [ ] **Step 2: Run it, verify it FAILS**

Run: `php artisan test --filter=test_app_settings_are_shared_to_frontend`
Expected: FAIL — `app` prop not present.

- [ ] **Step 3: Add the shared prop**

In `app/Http/Middleware/HandleInertiaRequests.php`, inside the array returned by `share()` (after the `'auth' => [...]` block, alongside `'ziggy'`/`'flash'`), add:
```php
            'app' => function () {
                $general = app(\App\Settings\GeneralSettings::class);

                return [
                    'org_name' => $general->org_name,
                    'support_email' => $general->support_email,
                    'date_format' => $general->date_format,
                    'pagination_size' => $general->pagination_size,
                ];
            },
```

- [ ] **Step 4: Run it, verify it PASSES**

Run: `php artisan test --filter=AppSettingsTest`
Expected: PASS (7 tests).

- [ ] **Step 5: Pint and commit**

```bash
vendor/bin/pint app/Http/Middleware/HandleInertiaRequests.php
git add app/Http/Middleware/HandleInertiaRequests.php tests/Feature/AppSettingsTest.php
git commit -m "feat: share general app settings to the frontend"
```

---

## Task 4: Settings form page + optional SettingCard count

**Files:**
- Modify: `resources/js/Components/Settings/SettingCard.vue`
- Create: `resources/js/Pages/Settings/App.vue`

- [ ] **Step 1: Make `count` optional in `SettingCard.vue`**

In `resources/js/Components/Settings/SettingCard.vue`, change the `count` prop declaration from:
```js
	count: { type: [Number, String], required: true },
```
to:
```js
	count: { type: [Number, String], default: null },
```
And in the template, change the count line from:
```html
			<p class="text-2xl font-bold text-gray-900 dark:text-gray-50">
				{{ count }}
			</p>
```
to:
```html
			<p
				v-if="count !== null"
				class="text-2xl font-bold text-gray-900 dark:text-gray-50"
			>
				{{ count }}
			</p>
```
Leave everything else unchanged.

- [ ] **Step 2: Create the settings form page**

Create `resources/js/Pages/Settings/App.vue`:
```vue
<script setup>
import NewAuthenticated from "@/Layouts/NewAuthenticated.vue";
import { Head, useForm } from "@inertiajs/vue3";
import BreadCrump from "@/Components/BreadCrump.vue";

const props = defineProps({
	general: { type: Object, required: true },
	security: { type: Object, required: true },
});

const breadcrumbLinks = [
	{ name: "Home", url: "/dashboard" },
	{ name: "Settings", url: "/settings" },
	{ name: "Application", url: null },
];

const form = useForm({
	org_name: props.general.org_name,
	support_email: props.general.support_email,
	date_format: props.general.date_format,
	pagination_size: props.general.pagination_size,
	password_change_interval_days: props.security.password_change_interval_days,
});

const submit = () => {
	form.put(route("app-settings.update"), { preserveScroll: true });
};

const fieldClass =
	"mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm focus:border-green-500 focus:ring-green-500 sm:text-sm";
const labelClass =
	"block text-sm font-medium text-gray-700 dark:text-gray-200";
const errorClass = "mt-1 text-xs text-red-600 dark:text-red-400";
</script>

<template>
	<Head title="Application settings" />
	<NewAuthenticated>
		<main class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8 py-6">
			<BreadCrump :links="breadcrumbLinks" />

			<div class="mt-4 mb-6">
				<h1
					class="text-2xl font-bold tracking-tight text-gray-900 dark:text-gray-50"
				>
					Application settings
				</h1>
				<p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
					Organisation-wide configuration.
				</p>
			</div>

			<form class="space-y-6" @submit.prevent="submit">
				<section
					class="rounded-2xl border border-green-200/60 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm p-5"
				>
					<h2 class="text-sm font-semibold text-green-900 dark:text-gray-100">
						Branding
					</h2>
					<div class="mt-4 space-y-4">
						<div>
							<label class="$labelClass" :class="labelClass">Organization name</label>
							<input v-model="form.org_name" type="text" :class="fieldClass" />
							<p v-if="form.errors.org_name" :class="errorClass">
								{{ form.errors.org_name }}
							</p>
						</div>
						<div>
							<label :class="labelClass">Support email</label>
							<input
								v-model="form.support_email"
								type="email"
								:class="fieldClass"
							/>
							<p v-if="form.errors.support_email" :class="errorClass">
								{{ form.errors.support_email }}
							</p>
						</div>
					</div>
				</section>

				<section
					class="rounded-2xl border border-green-200/60 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm p-5"
				>
					<h2 class="text-sm font-semibold text-green-900 dark:text-gray-100">
						Display
					</h2>
					<div class="mt-4 space-y-4">
						<div>
							<label :class="labelClass">Date format</label>
							<input v-model="form.date_format" type="text" :class="fieldClass" />
							<p class="mt-1 text-xs text-gray-400">PHP date format, e.g. d M Y</p>
							<p v-if="form.errors.date_format" :class="errorClass">
								{{ form.errors.date_format }}
							</p>
						</div>
						<div>
							<label :class="labelClass">Records per page</label>
							<input
								v-model.number="form.pagination_size"
								type="number"
								min="5"
								max="100"
								:class="fieldClass"
							/>
							<p v-if="form.errors.pagination_size" :class="errorClass">
								{{ form.errors.pagination_size }}
							</p>
						</div>
					</div>
				</section>

				<section
					class="rounded-2xl border border-green-200/60 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm p-5"
				>
					<h2 class="text-sm font-semibold text-green-900 dark:text-gray-100">
						Security
					</h2>
					<div class="mt-4 space-y-4">
						<div>
							<label :class="labelClass">Password change interval (days)</label>
							<input
								v-model.number="form.password_change_interval_days"
								type="number"
								min="0"
								max="3650"
								:class="fieldClass"
							/>
							<p class="mt-1 text-xs text-gray-400">0 disables forced rotation.</p>
							<p
								v-if="form.errors.password_change_interval_days"
								:class="errorClass"
							>
								{{ form.errors.password_change_interval_days }}
							</p>
						</div>
					</div>
				</section>

				<div class="flex justify-end">
					<button
						type="submit"
						:disabled="form.processing"
						class="rounded-md bg-green-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-green-500 disabled:opacity-50 dark:bg-gray-600 dark:hover:bg-gray-500"
					>
						Save
					</button>
				</div>
			</form>
		</main>
	</NewAuthenticated>
</template>
```

> Note: the `<label class="$labelClass" :class="labelClass">` on the first field must be just `<label :class="labelClass">` — remove the literal `class="$labelClass"` if present; only the bound `:class` is needed.

- [ ] **Step 3: Build**

Run: `npm run build`
Expected: succeeds. If it fails, report BLOCKED with the error.

- [ ] **Step 4: Commit**

```bash
git add resources/js/Components/Settings/SettingCard.vue resources/js/Pages/Settings/App.vue
git commit -m "feat: application settings form page"
```

---

## Task 5: Hub card + org-name branding

**Files:**
- Modify: `resources/js/Pages/Settings/Index.vue`
- Modify: `resources/js/Layouts/NewAuthenticated.vue`

- [ ] **Step 1: Add the Application card to the hub**

In `resources/js/Pages/Settings/Index.vue`, add `Cog6ToothIcon` to the Heroicons import:
```js
import {
	UsersIcon,
	ShieldCheckIcon,
	KeyIcon,
	ClipboardDocumentListIcon,
	BuildingOffice2Icon,
	Cog6ToothIcon,
} from "@heroicons/vue/24/outline";
```
Then, inside the `cards` computed array (before the final `].filter(...)`), add this entry after the Institutions entry:
```js
		{
			title: "Application",
			count: null,
			secondary: "Name, email, display, security",
			href: route("app-settings.edit"),
			linkLabel: "Configure",
			icon: Cog6ToothIcon,
			gate: "update app settings",
		},
```

- [ ] **Step 2: Show the org name in the layout**

In `resources/js/Layouts/NewAuthenticated.vue`, replace the hardcoded desktop sidebar heading:
```html
						<h2
							class="font-semibold text-xl text-gray-800 leading-tight dark:text-gray-50"
						>
							Audit Service
						</h2>
```
with:
```html
						<h2
							class="font-semibold text-xl text-gray-800 leading-tight dark:text-gray-50"
						>
							{{ $page.props.app?.org_name ?? "HRMIS" }}
						</h2>
```
(`$page` is globally available in Inertia templates — no import needed.)

- [ ] **Step 3: Build**

Run: `npm run build`
Expected: succeeds.

- [ ] **Step 4: Commit**

```bash
git add resources/js/Pages/Settings/Index.vue resources/js/Layouts/NewAuthenticated.vue
git commit -m "feat: settings hub application card and org-name branding"
```

---

## Task 6: Final verification

- [ ] **Step 1: Run the settings tests**

Run: `php artisan test --filter="AppSettingsTest|SettingsControllerTest"`
Expected: PASS.

- [ ] **Step 2: Broader test sweep (the seeder + shared-props changes are app-wide)**

Run: `php -d memory_limit=512M artisan test --filter="Settings|Permission|Role|Dashboard|Inertia|Shared"`
Expected: PASS. (The full suite OOMs at the default 128MB during bootstrap — a pre-existing env issue; 512M avoids it.)

- [ ] **Step 3: Pint**

Run: `vendor/bin/pint --dirty`
Expected: no errors. Do not stage unrelated pre-existing PHP files it may touch.

- [ ] **Step 4: Build**

Run: `npm run build`
Expected: succeeds.

- [ ] **Step 5: Manual smoke check (ask the user to run)**

With `npm run dev`, as an admin with `update app settings`:
- Settings hub shows an "Application" card → `/settings/app`.
- The form shows Branding / Display / Security sections with current values; saving persists and flashes success; invalid input shows field errors.
- The sidebar shows the configured org name (default "HRMIS").

---

## Self-Review

**Spec coverage:**
- spatie/laravel-settings install + `settings` table → Task 1 Steps 1-2. ✓
- `GeneralSettings` + `SecuritySettings` classes, registered, with defaults migration → Task 1 Steps 3-5. ✓
- `update app settings` permission seeded → Task 1 Step 6. ✓
- Routes `app-settings.edit`/`update` gated on `update app settings` → Task 2 Step 5. ✓
- `AppSettingsController` edit/update + `UpdateAppSettingsRequest` validation → Task 2. ✓
- Shared `app` prop (general settings) → Task 3. ✓
- `Settings/App.vue` grouped form (Branding/Display/Security) via `useForm` → Task 4. ✓
- Hub "Application" card + optional `SettingCard` count → Task 4 Step 1 + Task 5 Step 1. ✓
- org-name branding in layout → Task 5 Step 2. ✓
- Tests (defaults, view, update, validation, authz, shared props) → Tasks 1-3, 6. ✓
- Password-interval enforcement deferred (stored only, no middleware change) → honored (no task touches `PasswordChanged`). ✓

**Placeholder scan:** No TBD/TODO; all code shown. The one prose caveat (stray `class="$labelClass"`) is an explicit correction instruction, not a placeholder. ✓

**Type/name consistency:** Settings property names (`org_name`, `support_email`, `date_format`, `pagination_size`, `password_change_interval_days`) are identical across the classes (Task 1), migration (Task 1), controller payload (Task 2), Form Request rules (Task 2), `useForm` fields (Task 4), and tests (Tasks 1-3). Route names `app-settings.edit`/`app-settings.update` consistent across Task 2, Task 4, Task 5. Shared prop key `app.org_name` consistent across Task 3 and Task 5. ✓

**Out of scope:** 2b pagination retrofit, 2c date-format retrofit, password-interval enforcement — all deferred.
