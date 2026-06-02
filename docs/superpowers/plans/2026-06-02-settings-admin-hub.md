# Settings Admin Hub Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Replace the bare `/settings` page with a green-clean admin dashboard: permission-gated stat cards linking into existing management areas, plus a recent-activity list.

**Architecture:** Rewrite `SettingsController@__invoke` to return a `stats` object (counts) and a `recentActivity` array (gated on `view user activity`). Build two new presentational components under `resources/js/Components/Settings/` and rewrite `Settings/Index.vue` to compose them, deriving the visible cards from `stats` filtered by `auth.permissions`.

**Tech Stack:** Laravel 11, Inertia v1, Vue 3 `<script setup>`, Tailwind 3, Spatie Permission, Spatie Activitylog, PHPUnit, Heroicons.

**Testing note:** No JS test runner exists in this repo (ESLint lints only `public/`; no vitest). Backend is covered by a PHPUnit feature test with `assertInertia`; frontend is verified by `npm run build` compiling cleanly. Each frontend task ends with a build step.

**Branch:** `redesign/settings-dashboard` (already created; design doc committed there).

---

## File Structure

**Backend**
- Modify: `app/Http/Controllers/SettingsController.php` — return `stats` + `recentActivity`.
- Create: `tests/Feature/SettingsControllerTest.php` — authorized payload + deny redirect.

**Frontend**
- Create: `resources/js/Components/Settings/SettingCard.vue` — one stat/nav card (Inertia `<Link>`).
- Create: `resources/js/Components/Settings/RecentActivityCard.vue` — recent activity list.
- Modify: `resources/js/Pages/Settings/Index.vue` — compose cards + activity from props.

---

## Task 1: Backend — `stats` + `recentActivity` payload

**Files:**
- Modify: `app/Http/Controllers/SettingsController.php`
- Test: `tests/Feature/SettingsControllerTest.php`

- [ ] **Step 1: Write the failing test** — create `tests/Feature/SettingsControllerTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SettingsControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_settings_dashboard(): void
    {
        $admin = User::factory()->create();
        $admin->givePermissionTo(['view admin settings', 'view user activity']);

        User::factory()->count(3)->create();
        Role::firstOrCreate(['name' => 'reviewer']);

        $response = $this->actingAs($admin)->get(route('settings.index'));

        $response->assertOk();
        $response->assertInertia(
            fn (Assert $page) => $page
                ->component('Settings/Index')
                ->where('stats.users', User::count())
                ->where('stats.roles', Role::count())
                ->where('stats.permissions', Permission::count())
                ->has('stats.staff')
                ->has('stats.hrUser')
                ->has('stats.auditLogs')
                ->has('stats.institutions')
                ->has('recentActivity')
        );
    }

    public function test_recent_activity_is_empty_without_activity_permission(): void
    {
        $admin = User::factory()->create();
        $admin->givePermissionTo('view admin settings'); // no 'view user activity'

        $response = $this->actingAs($admin)->get(route('settings.index'));

        $response->assertOk();
        $response->assertInertia(
            fn (Assert $page) => $page
                ->component('Settings/Index')
                ->where('recentActivity', [])
        );
    }

    public function test_settings_denied_without_permission(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->from('/dashboard')
            ->get(route('settings.index'));

        $response->assertRedirect('/dashboard');
        $response->assertSessionHas('error');
    }
}
```

- [ ] **Step 2: Run the test, verify it FAILS**

Run: `php artisan test --filter=SettingsControllerTest`
Expected: FAIL — current controller sends `users`/`hr-user`/`staff`/`roles`/`permissions` (no `stats`, no `recentActivity`).

- [ ] **Step 3: Rewrite the controller** — replace the ENTIRE contents of `app/Http/Controllers/SettingsController.php` with:

```php
<?php

namespace App\Http\Controllers;

use App\Models\Institution;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class SettingsController extends Controller
{
    public function __invoke()
    {
        if (Gate::denies('view admin settings')) {
            activity()
                ->causedBy(auth()->user())
                ->event('view')
                ->withProperties([
                    'result' => 'failed',
                    'user_ip' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                ])
                ->log('attempted access to settings');

            return redirect()->back()->with('error', 'You are not authorized to view settings');
        }

        activity()
            ->causedBy(auth()->user())
            ->event('view')
            ->withProperties([
                'result' => 'success',
                'user_ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ])
            ->log('viewed settings');

        $recentActivity = Gate::allows('view user activity')
            ? Activity::with('causer')
                ->latest()
                ->limit(5)
                ->get()
                ->map(fn (Activity $activity) => [
                    'id' => $activity->id,
                    'description' => $activity->description,
                    'causer_name' => $activity->causer?->name ?? 'System',
                    'created_at' => $activity->created_at->format('d M Y H:i'),
                ])
                ->all()
            : [];

        return Inertia::render('Settings/Index', [
            'stats' => [
                'users' => User::count(),
                'staff' => User::role('staff')->count(),
                'hrUser' => User::role('hr-user')->count(),
                'roles' => Role::count(),
                'permissions' => Permission::count(),
                'auditLogs' => Activity::count(),
                'institutions' => Institution::count(),
            ],
            'recentActivity' => $recentActivity,
        ]);
    }
}
```

- [ ] **Step 4: Run the test, verify it PASSES**

Run: `php artisan test --filter=SettingsControllerTest`
Expected: PASS (3 tests).

- [ ] **Step 5: Run Pint and commit**

```bash
vendor/bin/pint app/Http/Controllers/SettingsController.php tests/Feature/SettingsControllerTest.php
git add app/Http/Controllers/SettingsController.php tests/Feature/SettingsControllerTest.php
git commit -m "feat: settings controller returns stats and recent activity"
```

Do NOT stage any other files (there are unrelated working-tree changes — leave them).

---

## Task 2: `SettingCard.vue` component

**Files:**
- Create: `resources/js/Components/Settings/SettingCard.vue`

- [ ] **Step 1: Create the component** (directory `resources/js/Components/Settings/` does not exist yet — create it):

```vue
<script setup>
import { Link } from "@inertiajs/vue3";
import { ChevronRightIcon } from "@heroicons/vue/16/solid";

defineProps({
	title: { type: String, required: true },
	count: { type: [Number, String], required: true },
	secondary: { type: String, default: null },
	href: { type: String, required: true },
	linkLabel: { type: String, default: "Manage" },
	icon: { type: [Object, Function], default: null },
});
</script>

<template>
	<Link
		:href="href"
		class="group flex flex-col rounded-2xl border border-green-200/60 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm p-5 transition hover:border-green-400 dark:hover:border-gray-500 hover:shadow"
	>
		<div class="flex items-center justify-between">
			<span
				class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-green-50 dark:bg-gray-700 text-green-600 dark:text-green-300"
			>
				<component :is="icon" v-if="icon" class="h-5 w-5" />
			</span>
			<ChevronRightIcon
				class="h-5 w-5 text-gray-300 group-hover:text-green-500 dark:text-gray-500"
			/>
		</div>
		<div class="mt-4">
			<p class="text-2xl font-bold text-gray-900 dark:text-gray-50">
				{{ count }}
			</p>
			<p class="text-sm font-semibold text-gray-700 dark:text-gray-200">
				{{ title }}
			</p>
			<p
				v-if="secondary"
				class="mt-0.5 text-xs text-gray-500 dark:text-gray-400"
			>
				{{ secondary }}
			</p>
		</div>
		<span
			class="mt-4 text-sm font-medium text-green-600 dark:text-green-300 group-hover:underline"
		>
			{{ linkLabel }} →
		</span>
	</Link>
</template>
```

- [ ] **Step 2: Verify it compiles**

Run: `npm run build`
Expected: build succeeds.

- [ ] **Step 3: Commit**

```bash
git add resources/js/Components/Settings/SettingCard.vue
git commit -m "feat: add SettingCard component"
```

---

## Task 3: `RecentActivityCard.vue` component

**Files:**
- Create: `resources/js/Components/Settings/RecentActivityCard.vue`

- [ ] **Step 1: Create the component**

```vue
<script setup>
defineProps({
	activities: { type: Array, default: () => [] },
});
</script>

<template>
	<div
		class="rounded-2xl border border-green-200/60 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm p-5"
	>
		<h2 class="text-sm font-semibold text-green-900 dark:text-gray-100">
			Recent admin activity
		</h2>
		<ul
			v-if="activities.length > 0"
			class="mt-3 divide-y divide-gray-100 dark:divide-gray-700"
		>
			<li
				v-for="activity in activities"
				:key="activity.id"
				class="flex items-center justify-between gap-3 py-2 text-sm"
			>
				<span class="min-w-0 truncate text-gray-700 dark:text-gray-200">
					<span class="font-medium text-gray-900 dark:text-gray-100">{{
						activity.causer_name
					}}</span>
					{{ activity.description }}
				</span>
				<span class="flex-shrink-0 text-xs text-gray-400 dark:text-gray-400">
					{{ activity.created_at }}
				</span>
			</li>
		</ul>
		<p v-else class="mt-3 text-sm text-gray-400 dark:text-gray-300">
			No recent activity.
		</p>
	</div>
</template>
```

- [ ] **Step 2: Verify it compiles**

Run: `npm run build`
Expected: build succeeds.

- [ ] **Step 3: Commit**

```bash
git add resources/js/Components/Settings/RecentActivityCard.vue
git commit -m "feat: add RecentActivityCard component"
```

---

## Task 4: Rewrite `Settings/Index.vue`

**Files:**
- Modify: `resources/js/Pages/Settings/Index.vue`

- [ ] **Step 1: Replace the page** — replace the ENTIRE contents of `resources/js/Pages/Settings/Index.vue` with:

```vue
<script setup>
import NewAuthenticated from "@/Layouts/NewAuthenticated.vue";
import { Head, usePage } from "@inertiajs/vue3";
import BreadCrump from "@/Components/BreadCrump.vue";
import SettingCard from "@/Components/Settings/SettingCard.vue";
import RecentActivityCard from "@/Components/Settings/RecentActivityCard.vue";
import { computed } from "vue";
import {
	UsersIcon,
	ShieldCheckIcon,
	KeyIcon,
	ClipboardDocumentListIcon,
	BuildingOffice2Icon,
} from "@heroicons/vue/24/outline";

const props = defineProps({
	stats: { type: Object, required: true },
	recentActivity: { type: Array, default: () => [] },
});

const page = usePage();
const permissions = computed(() => page.props?.auth.permissions);
const can = (permission) => permissions.value?.includes(permission);

const breadcrumbLinks = [
	{ name: "Home", url: "/dashboard" },
	{ name: "Settings", url: null },
];

const cards = computed(() =>
	[
		{
			title: "Users",
			count: props.stats.users,
			secondary: `${props.stats.staff} staff · ${props.stats.hrUser} HR`,
			href: route("user.index"),
			linkLabel: "Manage",
			icon: UsersIcon,
			gate: "view all users",
		},
		{
			title: "Roles",
			count: props.stats.roles,
			href: route("role.index"),
			linkLabel: "Manage",
			icon: ShieldCheckIcon,
			gate: "view roles",
		},
		{
			title: "Permissions",
			count: props.stats.permissions,
			href: route("permission.index"),
			linkLabel: "Manage",
			icon: KeyIcon,
			gate: "view permissions",
		},
		{
			title: "Audit Log",
			count: props.stats.auditLogs,
			href: route("audit-log.index"),
			linkLabel: "View",
			icon: ClipboardDocumentListIcon,
			gate: "view user activity",
		},
		{
			title: "Institutions",
			count: props.stats.institutions,
			href: route("institution.index"),
			linkLabel: "Manage",
			icon: BuildingOffice2Icon,
			gate: "view admin settings",
		},
	].filter((card) => can(card.gate)),
);
</script>

<template>
	<Head title="Settings" />
	<NewAuthenticated>
		<main class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-6">
			<BreadCrump :links="breadcrumbLinks" />

			<div class="mt-4">
				<h1
					class="text-2xl font-bold tracking-tight text-gray-900 dark:text-gray-50"
				>
					Settings
				</h1>
				<p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
					Manage users, roles, permissions, and related administration.
				</p>
			</div>

			<div class="mt-6 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
				<SettingCard
					v-for="card in cards"
					:key="card.title"
					:title="card.title"
					:count="card.count"
					:secondary="card.secondary"
					:href="card.href"
					:link-label="card.linkLabel"
					:icon="card.icon"
				/>
			</div>

			<RecentActivityCard
				v-if="can('view user activity')"
				:activities="recentActivity"
				class="mt-6"
			/>
		</main>
	</NewAuthenticated>
</template>
```

- [ ] **Step 2: Verify it compiles**

Run: `npm run build`
Expected: build succeeds. (A wrong Heroicon import name would fail here.)

- [ ] **Step 3: Commit**

```bash
git add resources/js/Pages/Settings/Index.vue
git commit -m "feat: settings admin hub dashboard page"
```

---

## Task 5: Final verification

- [ ] **Step 1: Run the settings test**

Run: `php artisan test --filter=SettingsControllerTest`
Expected: PASS (3 tests).

- [ ] **Step 2: Run Pint on changed PHP**

Run: `vendor/bin/pint --dirty`
Expected: no errors. (Note: `--dirty` may also touch unrelated pre-existing working-tree PHP files; do not stage those.)

- [ ] **Step 3: Production build**

Run: `npm run build`
Expected: build succeeds with no errors.

- [ ] **Step 4: Manual smoke check (ask the user to run)**

With `npm run dev` running, open `/settings` as an admin and confirm:
- Green-clean stat cards render for Users (with "{staff} staff · {hr} HR"), Roles, Permissions, Audit Log, Institutions.
- Each card links to its management page.
- "Recent admin activity" lists the latest entries (or "No recent activity.").
- A user without a given permission does not see that card; a user without `view user activity` sees no activity panel.

---

## Self-Review

**Spec coverage:**
- Green-clean dashboard layout → Task 4. ✓
- Five permission-gated cards (Users/Roles/Permissions/Audit Log/Institutions) with counts + links → Task 4 `cards` config; counts from Task 1 `stats`. ✓
- Users card secondary "{staff} staff · {hrUser} HR" → Task 4. ✓
- Recent admin activity (last 5, gated on `view user activity`) → Task 1 (`recentActivity`, server-gated) + Task 3 (`RecentActivityCard`) + Task 4 (`v-if can('view user activity')`). ✓
- Backend `stats` + `recentActivity` payload replacing mismatched props → Task 1. ✓
- New `Components/Settings/` components → Tasks 2-3. ✓
- Testing (authorized payload + deny redirect + empty activity without permission) → Task 1, Task 5. ✓

**Placeholder scan:** No TBD/TODO; every code step shows full content. ✓

**Type/name consistency:** `stats` keys `users/staff/hrUser/roles/permissions/auditLogs/institutions` produced in Task 1 and read in Task 4. `recentActivity` item shape `{id, description, causer_name, created_at}` produced in Task 1 and consumed in Task 3 (`activity.causer_name`, `activity.description`, `activity.created_at`, `:key="activity.id"`). `SettingCard` props (`title/count/secondary/href/linkLabel/icon`) defined in Task 2 and passed in Task 4 (`:link-label` ↔ `linkLabel`). ✓

**Out of scope:** persisted app-settings store is Cycle 2 (separate spec/plan).
