# User Show Page Redesign Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Modernize `User/Show` and its related components into a clean two-column, green-branded layout, and fix the direct-vs-inherited permission correctness bug.

**Architecture:** Split the permission payload in `UserController@show` into `direct_permissions` (revokable) and `inherited_permissions` (read-only, annotated with the role they come from). Build new presentational cards under `resources/js/Components/User/`, restyle the existing stateful partials under `resources/js/Pages/User/partials/` into chip cards, and reassemble `Show.vue` as a two-column layout (left rail = account + staff record, right main = roles + permissions).

**Tech Stack:** Laravel 11, Inertia v1, Vue 3 `<script setup>`, Tailwind 3, FormKit, Spatie Laravel Permission, PHPUnit.

**Testing note:** This repo has **no JavaScript test runner** (ESLint only lints `public/`; there is no vitest). Backend changes are covered by PHPUnit feature tests with `assertInertia`. Frontend components are verified by `npm run build` compiling cleanly. Every frontend task ends with a build step; the data correctness is locked down by the Task 1 backend test.

**Branch:** `redesign/user-show` (already created; the design doc is committed there).

---

## File Structure

**Backend**
- Modify: `app/Http/Controllers/UserController.php` — `show()` returns split permission lists.
- Create: `tests/Feature/UserShowTest.php` — asserts the Inertia payload.

**New presentational components** — `resources/js/Components/User/`
- Create: `UserIdentityCard.vue` — full-width identity strip.
- Create: `UserAccountCard.vue` — rail: email, verified, id.
- Create: `UserStaffRecordCard.vue` — rail: staff link + associate/change/unlink (emits only).

**Reworked stateful partials** — `resources/js/Pages/User/partials/`
- Modify: `UserRoles.vue` — chip card + modal orchestration.
- Modify: `RolesList.vue` — table → role chips.
- Modify: `UserPermissions.vue` — direct + inherited sections; pass direct list to modal.
- Modify: `PermissionsList.vue` — table → permission chips (direct, removable).
- Modify: `AddUserPermission.vue` / `AddUserRole.vue` / `UserRoleForm.vue` / `AssociateStaff.vue` / `Delete.vue` — green-clean restyle.

**Page**
- Modify: `resources/js/Pages/User/Show.vue` — two-column assembly.

---

## Task 1: Backend — split direct vs inherited permissions in `show()`

**Files:**
- Modify: `app/Http/Controllers/UserController.php:100-134`
- Test: `tests/Feature/UserShowTest.php`

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/UserShowTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UserShowTest extends TestCase
{
    use RefreshDatabase;

    /** A viewer that can view users and their roles/permissions. */
    protected function viewer(): User
    {
        $user = User::factory()->create();
        $user->givePermissionTo(['view user', 'view user roles', 'view user permissions']);

        return $user;
    }

    public function test_show_splits_direct_and_inherited_permissions(): void
    {
        $direct = Permission::firstOrCreate(['name' => 'directly.assigned']);
        $viaRole = Permission::firstOrCreate(['name' => 'role.granted']);

        $role = Role::firstOrCreate(['name' => 'editor']);
        $role->givePermissionTo($viaRole);

        $target = User::factory()->create();
        $target->assignRole($role);
        $target->givePermissionTo($direct);

        $response = $this->actingAs($this->viewer())
            ->get(route('user.show', ['user' => $target->id]));

        $response->assertOk();
        $response->assertInertia(
            fn (Assert $page) => $page
                ->component('User/Show')
                ->where('user.email', $target->email)
                ->where('user.direct_permissions.0.name', 'directly.assigned')
                ->where('user.inherited_permissions.0.name', 'role.granted')
                ->where('user.inherited_permissions.0.via', 'editor')
        );
    }

    public function test_directly_held_permission_is_not_listed_as_inherited(): void
    {
        $shared = Permission::firstOrCreate(['name' => 'shared.permission']);

        $role = Role::firstOrCreate(['name' => 'editor']);
        $role->givePermissionTo($shared);

        $target = User::factory()->create();
        $target->assignRole($role);
        $target->givePermissionTo($shared); // also held directly

        $response = $this->actingAs($this->viewer())
            ->get(route('user.show', ['user' => $target->id]));

        $response->assertInertia(function (Assert $page) {
            $direct = collect($page->toArray()['props']['user']['direct_permissions'])->pluck('name');
            $inherited = collect($page->toArray()['props']['user']['inherited_permissions'])->pluck('name');

            $this->assertTrue($direct->contains('shared.permission'));
            $this->assertFalse($inherited->contains('shared.permission'));
        });
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=UserShowTest`
Expected: FAIL — `user.direct_permissions` does not exist (page currently sends `permissions`).

- [ ] **Step 3: Rewrite `show()` to split permissions**

In `app/Http/Controllers/UserController.php`, replace the body of `show()` (lines 100-134) with:

```php
    public function show(User $user)
    {
        $user->load(['roles.permissions', 'permissions', 'person.institution']);

        $this->logSuccess('viewed a user', $user);

        $directIds = $user->getDirectPermissions()->pluck('id');
        $roles = $user->roles;

        $inherited = $user->getAllPermissions()
            ->reject(fn ($permission) => $directIds->contains($permission->id))
            ->map(fn ($permission) => [
                'id' => $permission->id,
                'name' => $permission->name,
                'via' => $roles
                    ->filter(fn ($role) => $role->permissions->contains('id', $permission->id))
                    ->pluck('name')
                    ->implode(', '),
            ])
            ->values();

        return Inertia::render('User/Show', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'verified' => $user->email_verified_at ? 'Yes' : 'No',
                'person_id' => $user->person_id,
                'staff' => $user->person ? [
                    'id' => $user->person->id,
                    'name' => $user->person->full_name,
                    'staff_number' => $user->person->institution->first()?->staff?->staff_number,
                ] : null,
                'roles' => $user->roles->map(fn ($role) => [
                    'id' => $role->id,
                    'name' => $role->name,
                    'start_date' => $role->created_at->format('d M Y'),
                ]),
                'direct_permissions' => $user->getDirectPermissions()->map(fn ($permission) => [
                    'id' => $permission->id,
                    'name' => $permission->name,
                    'start_date' => $permission->created_at->format('d M Y'),
                ])->values(),
                'inherited_permissions' => $inherited,
            ],
        ]);
    }
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test --filter=UserShowTest`
Expected: PASS (2 tests).

- [ ] **Step 5: Run Pint and commit**

```bash
vendor/bin/pint app/Http/Controllers/UserController.php tests/Feature/UserShowTest.php
git add app/Http/Controllers/UserController.php tests/Feature/UserShowTest.php
git commit -m "feat: split direct and inherited permissions on user show"
```

---

## Task 2: `UserIdentityCard.vue` — identity strip

**Files:**
- Create: `resources/js/Components/User/UserIdentityCard.vue`

- [ ] **Step 1: Create the component**

`User` has no image/initials field, so initials are computed from `name`.

```vue
<script setup>
import { computed } from "vue";
import { CheckBadgeIcon } from "@heroicons/vue/20/solid";

const props = defineProps({
	user: { type: Object, required: true },
});

const initials = computed(() =>
	(props.user.name ?? "")
		.split(" ")
		.filter(Boolean)
		.map((part) => part[0])
		.slice(0, 2)
		.join("")
		.toUpperCase(),
);

const primaryRole = computed(() => props.user.roles?.[0]?.name ?? null);
const isVerified = computed(() => props.user.verified === "Yes");
</script>

<template>
	<div
		class="rounded-2xl border border-green-200/60 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm p-5 sm:p-6 flex flex-col sm:flex-row items-center sm:items-stretch gap-4 sm:gap-5"
	>
		<div class="flex-shrink-0">
			<div
				class="w-[72px] h-[72px] rounded-full bg-gradient-to-br from-green-500 to-green-700 dark:from-gray-500 dark:to-gray-700 flex items-center justify-center text-white font-bold text-2xl"
			>
				{{ initials }}
			</div>
		</div>
		<div class="flex-1 min-w-0 text-center sm:text-left">
			<h1
				class="text-xl sm:text-2xl font-bold tracking-tight text-gray-900 dark:text-gray-50"
			>
				{{ user.name }}
			</h1>
			<p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
				<span v-if="primaryRole">{{ primaryRole }}</span>
				<span v-if="primaryRole && user.staff?.staff_number"> · </span>
				<span v-if="user.staff?.staff_number"
					>Staff #{{ user.staff.staff_number }}</span
				>
			</p>
		</div>
		<div
			class="flex flex-col items-center sm:items-end justify-center gap-1 text-sm"
		>
			<span class="text-gray-600 dark:text-gray-300">{{ user.email }}</span>
			<span
				v-if="isVerified"
				class="inline-flex items-center gap-1 rounded-full bg-green-50 dark:bg-gray-700 px-2 py-0.5 text-xs font-medium text-green-700 dark:text-green-300 ring-1 ring-inset ring-green-600/20"
			>
				<CheckBadgeIcon class="h-4 w-4" /> Verified
			</span>
			<span
				v-else
				class="inline-flex items-center rounded-full bg-yellow-50 dark:bg-gray-700 px-2 py-0.5 text-xs font-medium text-yellow-700 dark:text-yellow-300 ring-1 ring-inset ring-yellow-600/20"
			>
				Unverified
			</span>
		</div>
	</div>
</template>
```

- [ ] **Step 2: Verify it compiles**

Run: `npm run build`
Expected: build succeeds (component is not yet imported anywhere; this confirms it parses).

- [ ] **Step 3: Commit**

```bash
git add resources/js/Components/User/UserIdentityCard.vue
git commit -m "feat: add UserIdentityCard component"
```

---

## Task 3: `UserAccountCard.vue` — rail account details

**Files:**
- Create: `resources/js/Components/User/UserAccountCard.vue`

- [ ] **Step 1: Create the component**

```vue
<script setup>
defineProps({
	user: { type: Object, required: true },
});
</script>

<template>
	<div
		class="rounded-2xl border border-green-200/60 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm p-5"
	>
		<h2 class="text-sm font-semibold text-green-900 dark:text-gray-100">
			Account
		</h2>
		<dl class="mt-3 space-y-3 text-sm">
			<div class="flex justify-between gap-3">
				<dt class="text-gray-500 dark:text-gray-400">Email</dt>
				<dd class="text-gray-900 dark:text-gray-100 truncate">
					{{ user.email }}
				</dd>
			</div>
			<div class="flex justify-between gap-3">
				<dt class="text-gray-500 dark:text-gray-400">Verified</dt>
				<dd class="text-gray-900 dark:text-gray-100">{{ user.verified }}</dd>
			</div>
			<div class="flex justify-between gap-3">
				<dt class="text-gray-500 dark:text-gray-400">User ID</dt>
				<dd class="text-gray-900 dark:text-gray-100">#{{ user.id }}</dd>
			</div>
		</dl>
	</div>
</template>
```

- [ ] **Step 2: Verify it compiles**

Run: `npm run build`
Expected: build succeeds.

- [ ] **Step 3: Commit**

```bash
git add resources/js/Components/User/UserAccountCard.vue
git commit -m "feat: add UserAccountCard component"
```

---

## Task 4: `UserStaffRecordCard.vue` — rail staff link

**Files:**
- Create: `resources/js/Components/User/UserStaffRecordCard.vue`

- [ ] **Step 1: Create the component**

Presentational only — emits events; the page owns the modal/confirm.

```vue
<script setup>
const props = defineProps({
	user: { type: Object, required: true },
	canManage: { type: Boolean, default: false },
});

const emit = defineEmits(["associate", "unlink"]);
</script>

<template>
	<div
		class="rounded-2xl border border-green-200/60 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm p-5"
	>
		<h2 class="text-sm font-semibold text-green-900 dark:text-gray-100">
			Staff record
		</h2>
		<p class="mt-2 text-sm text-gray-700 dark:text-gray-200">
			{{
				user.staff
					? `${user.staff.name} — ${user.staff.staff_number ?? "—"}`
					: "Not linked"
			}}
		</p>
		<div v-if="canManage" class="mt-4 flex gap-3">
			<button
				type="button"
				class="rounded-md bg-green-600 px-2.5 py-1 text-xs font-medium text-white hover:bg-green-500 dark:bg-gray-600 dark:hover:bg-gray-500"
				@click="emit('associate')"
			>
				{{ user.person_id ? "Change" : "Associate" }}
			</button>
			<button
				v-if="user.person_id"
				type="button"
				class="rounded-md px-2.5 py-1 text-xs font-medium text-red-600 ring-1 ring-inset ring-red-600/20 dark:text-red-400 dark:ring-red-400/30 hover:bg-red-50 dark:hover:bg-gray-700"
				@click="emit('unlink')"
			>
				Unlink
			</button>
		</div>
	</div>
</template>
```

- [ ] **Step 2: Verify it compiles**

Run: `npm run build`
Expected: build succeeds.

- [ ] **Step 3: Commit**

```bash
git add resources/js/Components/User/UserStaffRecordCard.vue
git commit -m "feat: add UserStaffRecordCard component"
```

---

## Task 5: Restyle `RolesList.vue` and `UserRoles.vue` into a chip card

**Files:**
- Modify: `resources/js/Pages/User/partials/RolesList.vue`
- Modify: `resources/js/Pages/User/partials/UserRoles.vue`

- [ ] **Step 1: Convert `RolesList.vue` to role chips**

Replace the entire file with:

```vue
<script setup>
import { usePage } from "@inertiajs/vue3";
import { computed } from "vue";
import { XMarkIcon } from "@heroicons/vue/16/solid";

defineProps({
	roles: { type: Array, default: () => [] },
});

const emit = defineEmits(["deleteRole"]);

const page = usePage();
const permissions = computed(() => page.props?.auth.permissions);
const canRevoke = computed(() =>
	permissions.value?.includes("assign roles to user"),
);
</script>

<template>
	<div class="px-5 pb-5">
		<ul v-if="roles?.length > 0" class="flex flex-wrap gap-2">
			<li
				v-for="role in roles"
				:key="role.id"
				class="inline-flex items-center gap-1.5 rounded-full bg-green-50 dark:bg-gray-700 pl-3 pr-1.5 py-1 text-sm font-medium text-green-800 dark:text-green-200 ring-1 ring-inset ring-green-600/20"
			>
				<span class="h-1.5 w-1.5 rounded-full bg-green-500" />
				{{ role.name }}
				<button
					v-if="canRevoke"
					type="button"
					class="rounded-full p-0.5 text-green-500 hover:bg-green-100 hover:text-green-700 dark:hover:bg-gray-600"
					:aria-label="`Revoke ${role.name}`"
					@click="emit('deleteRole', role)"
				>
					<XMarkIcon class="h-4 w-4" />
				</button>
			</li>
		</ul>
		<p
			v-else
			class="py-6 text-center text-sm font-medium text-gray-400 dark:text-gray-300"
		>
			No roles assigned to this user.
		</p>
	</div>
</template>
```

- [ ] **Step 2: Restyle `UserRoles.vue` card shell**

In `resources/js/Pages/User/partials/UserRoles.vue`, replace the outer card markup inside `<main>` (the `<div class="rounded-lg bg-green-200 ...">` block through its closing `</div>`, currently lines 62-88) with:

```html
		<div
			class="rounded-2xl border border-green-200/60 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm"
		>
			<div class="flex items-center justify-between px-5 pt-5 pb-3">
				<h3 class="text-sm font-semibold text-green-900 dark:text-gray-100">
					Roles
				</h3>
				<button
					v-if="canAdd"
					class="inline-flex items-center gap-1 rounded-md bg-green-600 px-2.5 py-1 text-xs font-medium text-white hover:bg-green-500 dark:bg-gray-600 dark:hover:bg-gray-500"
					@click="toggleAddRoleModal()"
				>
					Add Role
				</button>
			</div>
			<RolesList
				:roles="props.roles"
				@delete-role="(model) => confirmDeleteRole(model)"
			/>
		</div>
```

Leave the `<script setup>` and the modal/`<Delete>` markup below untouched.

- [ ] **Step 3: Verify it compiles**

Run: `npm run build`
Expected: build succeeds.

- [ ] **Step 4: Commit**

```bash
git add resources/js/Pages/User/partials/RolesList.vue resources/js/Pages/User/partials/UserRoles.vue
git commit -m "feat: restyle roles card with chip list"
```

---

## Task 6: Direct + inherited permissions in `UserPermissions.vue` / `PermissionsList.vue`

**Files:**
- Modify: `resources/js/Pages/User/partials/PermissionsList.vue`
- Modify: `resources/js/Pages/User/partials/UserPermissions.vue`
- Modify: `resources/js/Pages/User/partials/AddUserPermission.vue`

- [ ] **Step 1: Convert `PermissionsList.vue` to removable permission chips**

Replace the entire file with:

```vue
<script setup>
import { usePage } from "@inertiajs/vue3";
import { computed } from "vue";
import { XMarkIcon } from "@heroicons/vue/16/solid";

defineProps({
	permissions: { type: Array, default: () => [] },
});

const emit = defineEmits(["deletePermission"]);

const page = usePage();
const authPermissions = computed(() => page.props?.auth.permissions);
const canRevoke = computed(() =>
	authPermissions.value?.includes("assign permissions to user"),
);
</script>

<template>
	<ul v-if="permissions?.length > 0" class="flex flex-wrap gap-2">
		<li
			v-for="permission in permissions"
			:key="permission.id"
			class="inline-flex items-center gap-1.5 rounded-full bg-green-50 dark:bg-gray-700 pl-3 pr-1.5 py-1 text-sm font-medium text-green-800 dark:text-green-200 ring-1 ring-inset ring-green-600/20"
		>
			{{ permission.name }}
			<button
				v-if="canRevoke"
				type="button"
				class="rounded-full p-0.5 text-green-500 hover:bg-green-100 hover:text-green-700 dark:hover:bg-gray-600"
				:aria-label="`Revoke ${permission.name}`"
				@click="emit('deletePermission', permission)"
			>
				<XMarkIcon class="h-4 w-4" />
			</button>
		</li>
	</ul>
	<p
		v-else
		class="py-4 text-sm font-medium text-gray-400 dark:text-gray-300"
	>
		No direct permissions.
	</p>
</template>
```

- [ ] **Step 2: Rework `UserPermissions.vue`**

Replace the entire file with:

```vue
<script setup>
import { router } from "@inertiajs/vue3";
import { ref, computed } from "vue";
import { useToggle } from "@vueuse/core";
import Modal from "@/Components/NewModal.vue";
import AddUserPermission from "./AddUserPermission.vue";
import Delete from "./Delete.vue";
import PermissionsList from "./PermissionsList.vue";

defineEmits(["closeForm"]);

const props = defineProps({
	user: { type: Number, required: true },
	directPermissions: { type: Array, default: () => [] },
	inheritedPermissions: { type: Array, default: () => [] },
	canAdd: { type: Boolean, default: false },
});

const directNames = computed(() =>
	props.directPermissions.map((permission) => permission.name),
);

const openAddPermissionModal = ref(false);
const toggleAddPermissionModal = useToggle(openAddPermissionModal);

const openDeletePermissionModal = ref(false);
const toggleDeletePermissionModal = useToggle(openDeletePermissionModal);
const deleteModel = ref(null);

const confirmDeletePermission = (model) => {
	deleteModel.value = model;
	toggleDeletePermissionModal();
};

const deletePermission = (user, permission) => {
	router.patch(route("user.revoke.permissions", { user }), {
		permission,
		preserveScroll: true,
	});
	toggleDeletePermissionModal();
};
</script>

<template>
	<main>
		<h2 class="sr-only">User Permissions</h2>
		<div
			class="rounded-2xl border border-green-200/60 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm"
		>
			<div class="flex items-center justify-between px-5 pt-5 pb-3">
				<h3 class="text-sm font-semibold text-green-900 dark:text-gray-100">
					Permissions
				</h3>
				<button
					v-if="canAdd"
					class="inline-flex items-center gap-1 rounded-md bg-green-600 px-2.5 py-1 text-xs font-medium text-white hover:bg-green-500 dark:bg-gray-600 dark:hover:bg-gray-500"
					@click="toggleAddPermissionModal()"
				>
					Add Permission
				</button>
			</div>

			<div class="px-5 pb-5 space-y-5">
				<div>
					<p
						class="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400"
					>
						Direct
					</p>
					<PermissionsList
						:permissions="directPermissions"
						@delete-permission="(model) => confirmDeletePermission(model)"
					/>
				</div>

				<div v-if="inheritedPermissions.length > 0">
					<p
						class="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400"
					>
						Inherited from roles
					</p>
					<ul class="flex flex-wrap gap-2">
						<li
							v-for="permission in inheritedPermissions"
							:key="permission.id"
							class="inline-flex items-center gap-1.5 rounded-full bg-gray-100 dark:bg-gray-700 px-3 py-1 text-sm text-gray-600 dark:text-gray-300 ring-1 ring-inset ring-gray-400/20"
						>
							{{ permission.name }}
							<span class="text-xs text-gray-400 dark:text-gray-400"
								>via {{ permission.via }}</span
							>
						</li>
					</ul>
				</div>
			</div>
		</div>

		<Modal :show="openAddPermissionModal" @close="toggleAddPermissionModal()">
			<AddUserPermission
				:user="user"
				:user-permissions="directNames"
				@form-submitted="toggleAddPermissionModal()"
			/>
		</Modal>

		<Delete
			:open="openDeletePermissionModal"
			:model="deleteModel"
			@delete-confirmed="deletePermission(user, deleteModel.name)"
			@close="toggleDeletePermissionModal()"
		/>
	</main>
</template>
```

- [ ] **Step 3: Point the Add-Permission modal heading at the green-clean style**

In `resources/js/Pages/User/partials/AddUserPermission.vue`, replace the `<main>` wrapper opening tag and `<h1>` (lines 59-60) with:

```html
	<main class="px-8 py-8 bg-white dark:bg-gray-800">
		<h1 class="text-xl font-semibold pb-4 text-green-900 dark:text-gray-100">
			Permissions
		</h1>
```

(The pre-fill source is already correct — `UserPermissions.vue` now passes only direct permission names via `user-permissions`.)

- [ ] **Step 4: Verify it compiles**

Run: `npm run build`
Expected: build succeeds.

- [ ] **Step 5: Commit**

```bash
git add resources/js/Pages/User/partials/PermissionsList.vue resources/js/Pages/User/partials/UserPermissions.vue resources/js/Pages/User/partials/AddUserPermission.vue
git commit -m "feat: split direct and inherited permissions in user permissions card"
```

---

## Task 7: Green-clean restyle of the remaining modals

**Files:**
- Modify: `resources/js/Pages/User/partials/UserRoleForm.vue`
- Modify: `resources/js/Pages/User/partials/AddUserRole.vue`
- Modify: `resources/js/Pages/User/partials/AssociateStaff.vue`
- Modify: `resources/js/Pages/User/partials/Delete.vue`

- [ ] **Step 1: Restyle the Add-Role modal wrapper**

In `resources/js/Pages/User/partials/AddUserRole.vue`, replace the `<main>` opening tag and `<h1>` (lines 39-40) with:

```html
	<main class="px-8 py-8 bg-white dark:bg-gray-800">
		<h1 class="text-xl font-semibold pb-4 text-green-900 dark:text-gray-100">
			Roles
		</h1>
```

- [ ] **Step 2: Restyle the Associate-Staff modal**

In `resources/js/Pages/User/partials/AssociateStaff.vue`, replace the `<main>` opening tag and `<h1>` (lines 48-49) with:

```html
	<main class="px-8 py-8 bg-white dark:bg-gray-800">
		<h1 class="text-xl font-semibold pb-4 text-green-900 dark:text-gray-100">
			Associate Staff Record
		</h1>
```

- [ ] **Step 3: Inspect and restyle `Delete.vue` confirm dialog**

Read `resources/js/Pages/User/partials/Delete.vue` first. Update its primary confirm button to the green-clean danger style (red confirm, neutral cancel) and ensure the panel uses `rounded-2xl`. Apply this class to the confirm button:

```
class="inline-flex w-full justify-center rounded-md bg-red-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-red-500 sm:w-auto"
```

and the cancel button:

```
class="mt-3 inline-flex w-full justify-center rounded-md bg-white dark:bg-gray-700 px-3 py-2 text-sm font-semibold text-gray-900 dark:text-gray-100 ring-1 ring-inset ring-gray-300 dark:ring-gray-600 hover:bg-gray-50 dark:hover:bg-gray-600 sm:mt-0 sm:w-auto"
```

(Only adjust classes already present on those buttons; do not change the emit/prop wiring.)

- [ ] **Step 4: Verify it compiles**

Run: `npm run build`
Expected: build succeeds.

- [ ] **Step 5: Commit**

```bash
git add resources/js/Pages/User/partials/AddUserRole.vue resources/js/Pages/User/partials/AssociateStaff.vue resources/js/Pages/User/partials/Delete.vue
git commit -m "feat: green-clean restyle of user management modals"
```

---

## Task 8: Reassemble `Show.vue` as a two-column layout

**Files:**
- Modify: `resources/js/Pages/User/Show.vue`

- [ ] **Step 1: Replace the page**

Replace the entire file with:

```vue
<script setup>
import MainLayout from "@/Layouts/NewAuthenticated.vue";
import { Head, usePage, router } from "@inertiajs/vue3";
import { ref, computed } from "vue";
import { useToggle } from "@vueuse/core";
import BreadCrump from "@/Components/BreadCrump.vue";
import NoPermission from "@/Components/NoPermission.vue";
import NewModal from "@/Components/NewModal.vue";
import UserIdentityCard from "@/Components/User/UserIdentityCard.vue";
import UserAccountCard from "@/Components/User/UserAccountCard.vue";
import UserStaffRecordCard from "@/Components/User/UserStaffRecordCard.vue";
import UserRoles from "./partials/UserRoles.vue";
import UserPermissions from "./partials/UserPermissions.vue";
import AssociateStaff from "./partials/AssociateStaff.vue";

const props = defineProps({
	user: { type: Object, default: () => null },
});

const page = usePage();
const permissions = computed(() => page.props?.auth.permissions);

const breadcrumbLinks = [
	{ name: "Dashboard", url: "/dashboard" },
	{ name: "Users", url: "/user" },
	{ name: props.user.name, url: null },
];

const openAssociateModal = ref(false);
const toggleAssociateModal = useToggle(openAssociateModal);

const unlinkStaff = () => {
	if (
		!window.confirm(
			"Unlink this user from their staff record? This also removes the staff role.",
		)
	) {
		return;
	}
	router.delete(route("user.dissociate-staff", { user: props.user.id }), {
		preserveScroll: true,
	});
};
</script>

<template>
	<Head :title="user.name" />
	<MainLayout>
		<main
			v-if="permissions?.includes('view user')"
			class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-6"
		>
			<BreadCrump :links="breadcrumbLinks" />

			<UserIdentityCard :user="user" class="mt-4" />

			<div class="mt-5 grid grid-cols-1 lg:grid-cols-3 gap-5">
				<!-- Left rail -->
				<div class="lg:col-span-1 flex flex-col gap-5">
					<UserAccountCard :user="user" />
					<UserStaffRecordCard
						:user="user"
						:can-manage="permissions?.includes('associate user staff')"
						@associate="toggleAssociateModal()"
						@unlink="unlinkStaff"
					/>
				</div>

				<!-- Right main -->
				<div class="lg:col-span-2 flex flex-col gap-5">
					<UserRoles
						:roles="user.roles"
						:user="user.id"
						:can-add="permissions?.includes('assign roles to user')"
						:has-staff-record="!!user.person_id"
					/>
					<UserPermissions
						:user="user.id"
						:direct-permissions="user.direct_permissions"
						:inherited-permissions="user.inherited_permissions"
						:can-add="permissions?.includes('assign permissions to user')"
					/>
				</div>
			</div>

			<NewModal :show="openAssociateModal" @close="toggleAssociateModal()">
				<AssociateStaff
					:user="user.id"
					@form-submitted="toggleAssociateModal()"
				/>
			</NewModal>
		</main>
		<NoPermission v-else />
	</MainLayout>
</template>
```

- [ ] **Step 2: Verify it compiles**

Run: `npm run build`
Expected: build succeeds.

- [ ] **Step 3: Commit**

```bash
git add resources/js/Pages/User/Show.vue
git commit -m "feat: two-column user show layout"
```

---

## Task 9: Final verification

- [ ] **Step 1: Run the user feature tests**

Run: `php artisan test --filter="UserShowTest|AssociateUserStaffTest"`
Expected: PASS.

- [ ] **Step 2: Run Pint on changed PHP**

Run: `vendor/bin/pint --dirty`
Expected: no errors.

- [ ] **Step 3: Production build**

Run: `npm run build`
Expected: build succeeds with no errors.

- [ ] **Step 4: Manual smoke check (ask the user to run)**

Suggest the user open a user detail page (`/user/{id}`) with `npm run dev` running and confirm:
- Two-column layout renders with identity strip, account + staff cards on the left, roles + permissions on the right.
- Roles and direct permissions show as removable chips; inherited permissions show read-only with "via {role}".
- Adding a role keeps existing roles (the earlier sync fix); revoking a direct permission works; inherited permissions offer no revoke control.

- [ ] **Step 5: Final commit if any cleanup was needed**

```bash
git add -A
git commit -m "chore: user show redesign cleanup"
```

---

## Self-Review

**Spec coverage:**
- Visual direction (green-clean cards) → Tasks 2-8 use the agreed classes. ✓
- Two-column layout → Task 8. ✓
- New `Components/User/` cards (Identity, Account, StaffRecord) → Tasks 2-4. ✓
- Roles/permissions chip cards → Tasks 5-6. ✓
- Direct vs inherited backend split → Task 1. ✓
- Add-Permission modal pre-fills from direct only → Task 6 Step 2 (`directNames` passed as `user-permissions`). ✓
- Restyled modals → Tasks 5-7. ✓
- Testing (assertInertia split + existing tests) → Tasks 1, 9. ✓

**Placeholder scan:** No TBD/TODO; every code step shows full content. ✓

**Type/name consistency:** Backend emits `direct_permissions`, `inherited_permissions` (Task 1); `Show.vue` binds `:direct-permissions`/`:inherited-permissions` and `UserPermissions.vue` declares `directPermissions`/`inheritedPermissions` (Tasks 6, 8). `inherited_permissions[].via` is produced in Task 1 and read in Task 6. `confirmDeleteRole`/`deletePermission` names match their existing usages. ✓

**Note on `RolesList`/`PermissionsList`:** The design left "convert vs fold" open; this plan converts them in place (Tasks 5-6), preserving the parent→child boundary.
