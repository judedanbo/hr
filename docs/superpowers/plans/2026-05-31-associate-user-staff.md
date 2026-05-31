# Associate User with Staff Record Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Let an admin associate an existing user account with an existing staff record, and block assigning the `staff` role to any user that is not associated with a staff record.

**Architecture:** Two coordinated parts. (1) An *association* capability on `UserController` (set / clear `users.person_id`) reachable from the user detail page and the user list via a searchable picker. (2) A *role gate*: a Form Request used by `RoleController::addRole`, plus a guard in `RoleController::addUsers`, that rejects the `staff` role for users with a null `person_id`. The invariant enforced everywhere: *a user with the `staff` role must have a `person_id`.*

**Tech Stack:** Laravel 11 (Laravel 10 file structure), PHPUnit feature tests with `RefreshDatabase`, Spatie Permission, Inertia + Vue 3, FormKit, existing `SearchSelect.vue`, Maatwebsite not involved.

---

## Key Facts (verified against the codebase)

- `users.person_id` is nullable and is **not** in `User::$fillable` — set it directly (`$user->person_id = ...; $user->save();`), do not mass-assign.
- `User::person()` is a `belongsTo(Person::class)`. `Person` uses `SoftDeletes`, has a `full_name` accessor (`"{$title} {$first_name} {$other_names} {$surname}"`), and `Person::isStaff()` is `$this->institution()->exists()`.
- `Person::institution()` is `belongsToMany(Institution)->using(InstitutionPerson)->withPivot('staff_number', ...)->as('staff')`. So `$person->institution->first()?->staff?->staff_number` gives a staff number. The pivot table is `institution_person` (column `person_id`).
- Roles are assigned **by name**. `roles.list` returns `name as value, name as label`; the checkbox in `UserRoleForm.vue` POSTs an array of role *names* to `user.add.roles` → `RoleController::addRole()` → `$user->syncRoles($request->roles)`. The role name is exactly `staff`.
- Tests: `Tests\TestCase` sets `protected $seed = true`, so `DatabaseSeeder` runs before each test and **all roles and permissions exist**. Feature tests use `RefreshDatabase` and `actingAs()`. Permissions can be granted to a test user with `$user->givePermissionTo('...')`.
- `DatabaseSeeder` already calls `UserPermissionsSeeder` and `AllPermissionsSeeder`. `super-administrator` is granted **all** permissions automatically (`AllPermissionsSeeder` + `RolesAndPermissionsSeeder` both `syncPermissions(Permission::all())`).
- `UserController` uses the `LogsAuthorization` trait (`$this->logSuccess('message', $model)`). It does **not** yet import `Gate` or `Person`.
- Factories available: `User::factory()`, `Person::factory()`, `Institution::factory()`, `InstitutionPerson::factory()`.

---

## File Structure

**Create:**
- `app/Http/Requests/StoreUserStaffRequest.php` — validates the staff-association request (exists, is staff, one-to-one uniqueness, permission).
- `app/Http/Requests/UpdateUserRolesRequest.php` — validates role assignment; rejects `staff` role for unlinked users.
- `resources/js/Pages/User/partials/AssociateStaff.vue` — modal form (searchable staff picker) reused by detail + list pages.
- `tests/Feature/AssociateUserStaffTest.php` — all backend feature tests for this feature.

**Modify:**
- `database/seeders/UserPermissionsSeeder.php` — add `associate user staff` permission.
- `database/seeders/AllPermissionsSeeder.php` — add `associate user staff` to the canonical list.
- `app/Http/Controllers/UserController.php` — add `staffOptions`, `associateStaff`, `dissociateStaff`; include `person_id` + linked-staff info in `index`/`show` payloads.
- `app/Http/Controllers/RoleController.php` — `addRole` uses `UpdateUserRolesRequest`; `addUsers` gains the staff-link guard.
- `routes/web.php` — three new routes in the `UserController` group.
- `resources/js/Pages/User/Show.vue` — "Staff record" panel + associate/change/unlink + modal; thread `has-staff-record` into roles.
- `resources/js/Pages/User/Index.vue` — row action + status indicator + modal.
- `resources/js/Pages/User/partials/UserRoles.vue` — accept and forward `hasStaffRecord`.
- `resources/js/Pages/User/partials/AddUserRole.vue` — accept and forward `hasStaffRecord`.
- `resources/js/Pages/User/partials/UserRoleForm.vue` — disable the `staff` checkbox when unlinked.

---

## Task 1: Add the `associate user staff` permission to the seeders

**Files:**
- Modify: `database/seeders/UserPermissionsSeeder.php`
- Modify: `database/seeders/AllPermissionsSeeder.php`
- Test: `tests/Feature/AssociateUserStaffTest.php`

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/AssociateUserStaffTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Models\Institution;
use App\Models\Person;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AssociateUserStaffTest extends TestCase
{
    use RefreshDatabase;

    /** Create a Person that is a staff member (has an institution_person row). */
    protected function staffPerson(): Person
    {
        $person = Person::factory()->create();
        $person->institution()->attach(Institution::factory()->create()->id, [
            'staff_number' => 'STAFF'.fake()->unique()->numerify('#####'),
            'hire_date' => now()->subYears(3),
        ]);

        return $person;
    }

    /** A user holding the association permission. */
    protected function adminUser(): User
    {
        $user = User::factory()->create();
        $user->givePermissionTo('associate user staff');

        return $user;
    }

    public function test_associate_user_staff_permission_is_seeded(): void
    {
        $this->assertTrue(
            Permission::where('name', 'associate user staff')->exists(),
            "Permission 'associate user staff' should be seeded"
        );

        $this->assertTrue(
            Role::findByName('super-administrator')->hasPermissionTo('associate user staff')
        );
    }
}
```

- [ ] **Step 2: Run the test to verify it fails**

Run: `php artisan test --filter=test_associate_user_staff_permission_is_seeded`
Expected: FAIL — permission `associate user staff` does not exist.

- [ ] **Step 3: Add the permission to `UserPermissionsSeeder`**

In `database/seeders/UserPermissionsSeeder.php`, add this line alongside the other `firstOrCreate` calls (e.g. just after the `'update user profile'` line, before the `Role::findByName('staff')` block):

```php
        Permission::firstOrCreate(['name' => 'associate user staff']);
```

- [ ] **Step 4: Add the permission to `AllPermissionsSeeder`**

In `database/seeders/AllPermissionsSeeder.php`, inside `getAllPermissions()` in the `// User Management` block, add:

```php
            'associate user staff',
```

(`AllPermissionsSeeder` then syncs all permissions to `super-administrator`, so no further wiring is needed for the super admin.)

- [ ] **Step 5: Run the test to verify it passes**

Run: `php artisan test --filter=test_associate_user_staff_permission_is_seeded`
Expected: PASS.

- [ ] **Step 6: Confirm the existing seeder test still passes**

Run: `php artisan test --filter=AllPermissionsSeederTest`
Expected: PASS (its assertion is `assertGreaterThanOrEqual(100, ...)`, so an added permission is safe).

- [ ] **Step 7: Commit**

```bash
git add database/seeders/UserPermissionsSeeder.php database/seeders/AllPermissionsSeeder.php tests/Feature/AssociateUserStaffTest.php
git commit -m "feat: seed 'associate user staff' permission"
```

---

## Task 2: `staffOptions` endpoint — list unlinked staff for the picker

**Files:**
- Modify: `app/Http/Controllers/UserController.php`
- Modify: `routes/web.php`
- Test: `tests/Feature/AssociateUserStaffTest.php`

- [ ] **Step 1: Write the failing tests**

Add to `AssociateUserStaffTest`:

```php
    public function test_staff_options_returns_unlinked_staff(): void
    {
        $linkable = $this->staffPerson();

        // A staff person already linked to another user must be excluded.
        $linked = $this->staffPerson();
        User::factory()->create(['person_id' => $linked->id]);

        // A non-staff person (no institution_person row) must be excluded.
        $nonStaff = Person::factory()->create();

        $response = $this->actingAs($this->adminUser())
            ->getJson(route('users.staff-options'));

        $response->assertOk();
        $values = collect($response->json())->pluck('value');

        $this->assertTrue($values->contains($linkable->id));
        $this->assertFalse($values->contains($linked->id));
        $this->assertFalse($values->contains($nonStaff->id));
    }

    public function test_staff_options_requires_permission(): void
    {
        $response = $this->actingAs(User::factory()->create())
            ->getJson(route('users.staff-options'));

        $response->assertForbidden();
    }
```

- [ ] **Step 2: Run the tests to verify they fail**

Run: `php artisan test --filter="test_staff_options"`
Expected: FAIL — route `users.staff-options` is not defined.

- [ ] **Step 3: Add the route**

In `routes/web.php`, inside the `Route::controller(UserController::class)->middleware(['auth', 'password_changed'])->group(...)` block (around lines 95–106), add:

```php
    Route::get('/users/staff-options', 'staffOptions')->middleware('can:associate user staff')->name('users.staff-options');
    Route::patch('/user/{user}/associate-staff', 'associateStaff')->middleware('can:associate user staff')->name('user.associate-staff');
    Route::delete('/user/{user}/associate-staff', 'dissociateStaff')->middleware('can:associate user staff')->name('user.dissociate-staff');
```

(Add all three now; the associate/dissociate actions land in Tasks 3 and 4. `/users/staff-options` uses the `users` prefix so it does not collide with `/user/{user}`.)

- [ ] **Step 4: Add imports and the `staffOptions` method to `UserController`**

At the top of `app/Http/Controllers/UserController.php`, add these imports:

```php
use App\Http\Requests\StoreUserStaffRequest;
use App\Models\Person;
use Illuminate\Http\JsonResponse;
```

Add this method to the class:

```php
    /**
     * Return staff people available to link to a user account.
     *
     * Only people who are staff (have an institution_person row) and are not
     * already linked to another user account are returned.
     *
     * @return JsonResponse
     */
    public function staffOptions(): JsonResponse
    {
        $linkedPersonIds = User::query()->whereNotNull('person_id')->pluck('person_id');

        $options = Person::query()
            ->whereHas('institution')
            ->whereNotIn('id', $linkedPersonIds)
            ->with('institution')
            ->orderBy('surname')
            ->get()
            ->map(function (Person $person): array {
                $staffNumber = $person->institution->first()?->staff?->staff_number;

                return [
                    'value' => $person->id,
                    'label' => $staffNumber
                        ? "{$person->full_name} — {$staffNumber}"
                        : $person->full_name,
                ];
            })
            ->values();

        return response()->json($options);
    }
```

- [ ] **Step 5: Run the tests to verify they pass**

Run: `php artisan test --filter="test_staff_options"`
Expected: PASS.

- [ ] **Step 6: Format and commit**

```bash
vendor/bin/pint --dirty
git add app/Http/Controllers/UserController.php routes/web.php tests/Feature/AssociateUserStaffTest.php
git commit -m "feat: add staff-options endpoint for user association picker"
```

---

## Task 3: Associate a user with a staff record

**Files:**
- Create: `app/Http/Requests/StoreUserStaffRequest.php`
- Modify: `app/Http/Controllers/UserController.php`
- Test: `tests/Feature/AssociateUserStaffTest.php`

- [ ] **Step 1: Write the failing tests**

Add to `AssociateUserStaffTest`:

```php
    public function test_admin_can_associate_user_with_staff_record(): void
    {
        $user = User::factory()->create(['person_id' => null]);
        $person = $this->staffPerson();

        $response = $this->actingAs($this->adminUser())
            ->patch(route('user.associate-staff', ['user' => $user->id]), [
                'person_id' => $person->id,
            ]);

        $response->assertRedirect();
        $this->assertEquals($person->id, $user->fresh()->person_id);
    }

    public function test_cannot_associate_a_non_staff_person(): void
    {
        $user = User::factory()->create(['person_id' => null]);
        $nonStaff = Person::factory()->create();

        $response = $this->actingAs($this->adminUser())
            ->patch(route('user.associate-staff', ['user' => $user->id]), [
                'person_id' => $nonStaff->id,
            ]);

        $response->assertSessionHasErrors('person_id');
        $this->assertNull($user->fresh()->person_id);
    }

    public function test_cannot_associate_a_staff_already_linked_to_another_user(): void
    {
        $person = $this->staffPerson();
        User::factory()->create(['person_id' => $person->id]);

        $user = User::factory()->create(['person_id' => null]);

        $response = $this->actingAs($this->adminUser())
            ->patch(route('user.associate-staff', ['user' => $user->id]), [
                'person_id' => $person->id,
            ]);

        $response->assertSessionHasErrors('person_id');
        $this->assertNull($user->fresh()->person_id);
    }

    public function test_associate_requires_permission(): void
    {
        $user = User::factory()->create(['person_id' => null]);
        $person = $this->staffPerson();

        $response = $this->actingAs(User::factory()->create())
            ->patch(route('user.associate-staff', ['user' => $user->id]), [
                'person_id' => $person->id,
            ]);

        $response->assertForbidden();
        $this->assertNull($user->fresh()->person_id);
    }
```

- [ ] **Step 2: Run the tests to verify they fail**

Run: `php artisan test --filter="test_admin_can_associate_user_with_staff_record|test_cannot_associate_a_non_staff_person|test_cannot_associate_a_staff_already_linked_to_another_user|test_associate_requires_permission"`
Expected: FAIL — `associateStaff` method does not exist (HTTP 500/“method not found”).

- [ ] **Step 3: Create the Form Request**

Create `app/Http/Requests/StoreUserStaffRequest.php`:

```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class StoreUserStaffRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('associate user staff');
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'person_id' => [
                'required',
                'integer',
                'exists:people,id',
                Rule::exists('institution_person', 'person_id'),
                Rule::unique('users', 'person_id')->ignore($this->route('user')->id),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'person_id.required' => 'Please select a staff record to associate.',
            'person_id.exists' => 'The selected record is not a valid staff member.',
            'person_id.unique' => 'That staff record is already linked to another user.',
        ];
    }
}
```

- [ ] **Step 4: Add the `associateStaff` method to `UserController`**

```php
    /**
     * Associate a user account with an existing staff record.
     */
    public function associateStaff(StoreUserStaffRequest $request, User $user): \Illuminate\Http\RedirectResponse
    {
        $user->person_id = $request->validated()['person_id'];
        $user->save();

        $this->logSuccess('associated a user with a staff record', $user);

        return redirect()->back()->with('success', 'User associated with staff record successfully');
    }
```

- [ ] **Step 5: Run the tests to verify they pass**

Run: `php artisan test --filter="test_admin_can_associate_user_with_staff_record|test_cannot_associate_a_non_staff_person|test_cannot_associate_a_staff_already_linked_to_another_user|test_associate_requires_permission"`
Expected: PASS.

- [ ] **Step 6: Format and commit**

```bash
vendor/bin/pint --dirty
git add app/Http/Requests/StoreUserStaffRequest.php app/Http/Controllers/UserController.php tests/Feature/AssociateUserStaffTest.php
git commit -m "feat: associate a user with an existing staff record"
```

---

## Task 4: Dissociate (unlink) — clears `person_id` and removes the `staff` role

**Files:**
- Modify: `app/Http/Controllers/UserController.php`
- Test: `tests/Feature/AssociateUserStaffTest.php`

- [ ] **Step 1: Write the failing tests**

Add to `AssociateUserStaffTest`:

```php
    public function test_dissociate_clears_person_and_removes_staff_role(): void
    {
        $person = $this->staffPerson();
        $user = User::factory()->create(['person_id' => $person->id]);
        $user->assignRole('staff');

        $response = $this->actingAs($this->adminUser())
            ->delete(route('user.dissociate-staff', ['user' => $user->id]));

        $response->assertRedirect();
        $user->refresh();
        $this->assertNull($user->person_id);
        $this->assertFalse($user->hasRole('staff'));
    }

    public function test_dissociate_requires_permission(): void
    {
        $person = $this->staffPerson();
        $user = User::factory()->create(['person_id' => $person->id]);

        $response = $this->actingAs(User::factory()->create())
            ->delete(route('user.dissociate-staff', ['user' => $user->id]));

        $response->assertForbidden();
        $this->assertEquals($person->id, $user->fresh()->person_id);
    }
```

- [ ] **Step 2: Run the tests to verify they fail**

Run: `php artisan test --filter="test_dissociate_clears_person_and_removes_staff_role|test_dissociate_requires_permission"`
Expected: FAIL — `dissociateStaff` method does not exist.

- [ ] **Step 3: Add the `dissociateStaff` method to `UserController`**

(The `can:associate user staff` route middleware already enforces the permission, so no inline gate is needed here.)

```php
    /**
     * Remove a user's association with a staff record.
     *
     * An unlinked user cannot be staff, so the staff role is removed as well to
     * keep the invariant consistent.
     */
    public function dissociateStaff(User $user): \Illuminate\Http\RedirectResponse
    {
        $user->person_id = null;
        $user->save();

        if ($user->hasRole('staff')) {
            $user->removeRole('staff');
        }

        $this->logSuccess('removed a user staff association', $user);

        return redirect()->back()->with('success', 'Staff association removed successfully');
    }
```

- [ ] **Step 4: Run the tests to verify they pass**

Run: `php artisan test --filter="test_dissociate_clears_person_and_removes_staff_role|test_dissociate_requires_permission"`
Expected: PASS.

- [ ] **Step 5: Format and commit**

```bash
vendor/bin/pint --dirty
git add app/Http/Controllers/UserController.php tests/Feature/AssociateUserStaffTest.php
git commit -m "feat: unlink user from staff record and drop staff role"
```

---

## Task 5: Role gate — block the `staff` role for unlinked users in `addRole`

**Files:**
- Create: `app/Http/Requests/UpdateUserRolesRequest.php`
- Modify: `app/Http/Controllers/RoleController.php`
- Test: `tests/Feature/AssociateUserStaffTest.php`

**Design note:** `RoleController::addRole` already has a permission-denial block that logs a failed activity and redirects back with an error. To preserve that exact UX, the Form Request's `authorize()` returns `true` and the existing permission block stays in the controller. The Form Request adds **only** the new "must be linked to assign staff" rule.

- [ ] **Step 1: Write the failing tests**

Add to `AssociateUserStaffTest`:

```php
    /** A user permitted to assign roles. */
    protected function roleAdmin(): User
    {
        $user = User::factory()->create();
        $user->givePermissionTo('assign roles to user');

        return $user;
    }

    public function test_cannot_assign_staff_role_to_unlinked_user(): void
    {
        $target = User::factory()->create(['person_id' => null]);

        $response = $this->actingAs($this->roleAdmin())
            ->post(route('user.add.roles', ['user' => $target->id]), [
                'roles' => ['staff'],
            ]);

        $response->assertSessionHasErrors('roles');
        $this->assertFalse($target->fresh()->hasRole('staff'));
    }

    public function test_can_assign_staff_role_to_linked_user(): void
    {
        $person = $this->staffPerson();
        $target = User::factory()->create(['person_id' => $person->id]);

        $response = $this->actingAs($this->roleAdmin())
            ->post(route('user.add.roles', ['user' => $target->id]), [
                'roles' => ['staff'],
            ]);

        $response->assertSessionHasNoErrors();
        $this->assertTrue($target->fresh()->hasRole('staff'));
    }

    public function test_can_assign_non_staff_role_to_unlinked_user(): void
    {
        $target = User::factory()->create(['person_id' => null]);

        $response = $this->actingAs($this->roleAdmin())
            ->post(route('user.add.roles', ['user' => $target->id]), [
                'roles' => ['admin-user'],
            ]);

        $response->assertSessionHasNoErrors();
        $this->assertTrue($target->fresh()->hasRole('admin-user'));
    }
```

- [ ] **Step 2: Run the tests to verify they fail**

Run: `php artisan test --filter="test_cannot_assign_staff_role_to_unlinked_user|test_can_assign_staff_role_to_linked_user|test_can_assign_non_staff_role_to_unlinked_user"`
Expected: FAIL — `test_cannot_assign_staff_role_to_unlinked_user` fails because the staff role is currently assignable without a link (no `roles` validation error).

- [ ] **Step 3: Create the Form Request**

Create `app/Http/Requests/UpdateUserRolesRequest.php`:

```php
<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class UpdateUserRolesRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Permission is enforced (and logged) inside RoleController::addRole.
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'roles' => ['required', 'array'],
            'roles.*' => ['string', 'exists:roles,name'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $user = $this->route('user');
            $roles = (array) $this->input('roles');

            if (in_array('staff', $roles, true) && $user instanceof User && is_null($user->person_id)) {
                $validator->errors()->add(
                    'roles',
                    'Associate this user with a staff record before assigning the staff role.'
                );
            }
        });
    }
}
```

- [ ] **Step 4: Wire the Form Request into `addRole`**

In `app/Http/Controllers/RoleController.php`, add the import:

```php
use App\Http\Requests\UpdateUserRolesRequest;
```

Change the `addRole` signature from:

```php
    public function addRole(Request $request, User $user)
```

to:

```php
    public function addRole(UpdateUserRolesRequest $request, User $user)
```

Leave the rest of the method (the `Gate::denies('assign roles to user')` block, activity logging, and `$user->syncRoles($request->roles)`) unchanged.

- [ ] **Step 5: Run the tests to verify they pass**

Run: `php artisan test --filter="test_cannot_assign_staff_role_to_unlinked_user|test_can_assign_staff_role_to_linked_user|test_can_assign_non_staff_role_to_unlinked_user"`
Expected: PASS.

- [ ] **Step 6: Format and commit**

```bash
vendor/bin/pint --dirty
git add app/Http/Requests/UpdateUserRolesRequest.php app/Http/Controllers/RoleController.php tests/Feature/AssociateUserStaffTest.php
git commit -m "feat: block staff role assignment for users without a staff record"
```

---

## Task 6: Role gate — guard the `staff` role in `addUsers` (role-side assignment)

**Files:**
- Modify: `app/Http/Controllers/RoleController.php`
- Test: `tests/Feature/AssociateUserStaffTest.php`

- [ ] **Step 1: Write the failing tests**

Add to `AssociateUserStaffTest`:

```php
    public function test_add_users_rejects_unlinked_user_for_staff_role(): void
    {
        $staffRole = Role::findByName('staff');
        $unlinked = User::factory()->create(['person_id' => null]);

        $response = $this->actingAs($this->roleAdmin())
            ->post(route('role.add.users', ['role' => $staffRole->id]), [
                'users' => [$unlinked->id],
            ]);

        $response->assertSessionHas('error');
        $this->assertFalse($unlinked->fresh()->hasRole('staff'));
    }

    public function test_add_users_allows_linked_user_for_staff_role(): void
    {
        $staffRole = Role::findByName('staff');
        $person = $this->staffPerson();
        $linked = User::factory()->create(['person_id' => $person->id]);

        $response = $this->actingAs($this->roleAdmin())
            ->post(route('role.add.users', ['role' => $staffRole->id]), [
                'users' => [$linked->id],
            ]);

        $response->assertSessionHas('success');
        $this->assertTrue($linked->fresh()->hasRole('staff'));
    }
```

- [ ] **Step 2: Run the tests to verify they fail**

Run: `php artisan test --filter="test_add_users_rejects_unlinked_user_for_staff_role|test_add_users_allows_linked_user_for_staff_role"`
Expected: FAIL — `test_add_users_rejects_unlinked_user_for_staff_role` fails because the staff role is currently assigned without checking `person_id`.

- [ ] **Step 3: Add the guard to `addUsers`**

In `app/Http/Controllers/RoleController.php`, in `addUsers`, immediately **after** the existing `$request->validate([...])` block and **before** the success `activity()` log, insert:

```php
        if ($role->name === 'staff') {
            $unlinked = User::query()
                ->whereIn('id', $request->users)
                ->whereNull('person_id')
                ->pluck('name');

            if ($unlinked->isNotEmpty()) {
                return redirect()->back()->with(
                    'error',
                    'These users must be associated with a staff record before assigning the staff role: '.$unlinked->implode(', ')
                );
            }
        }
```

- [ ] **Step 4: Run the tests to verify they pass**

Run: `php artisan test --filter="test_add_users_rejects_unlinked_user_for_staff_role|test_add_users_allows_linked_user_for_staff_role"`
Expected: PASS.

- [ ] **Step 5: Format and commit**

```bash
vendor/bin/pint --dirty
git add app/Http/Controllers/RoleController.php tests/Feature/AssociateUserStaffTest.php
git commit -m "feat: guard staff role in role-side user assignment"
```

---

## Task 7: Expose link status in the user index/show payloads

**Files:**
- Modify: `app/Http/Controllers/UserController.php`
- Test: `tests/Feature/AssociateUserStaffTest.php`

- [ ] **Step 1: Write the failing test**

Add to `AssociateUserStaffTest` (Inertia assertion):

```php
    public function test_user_show_payload_includes_staff_link(): void
    {
        $person = $this->staffPerson();
        $user = User::factory()->create(['person_id' => $person->id]);

        $viewer = User::factory()->create();
        $viewer->givePermissionTo('view user');

        $response = $this->actingAs($viewer)->get(route('user.show', ['user' => $user->id]));

        $response->assertInertia(fn (\Inertia\Testing\AssertableInertia $page) => $page
            ->component('User/Show')
            ->where('user.person_id', $person->id)
            ->where('user.staff.id', $person->id)
        );
    }
```

- [ ] **Step 2: Run the test to verify it fails**

Run: `php artisan test --filter=test_user_show_payload_includes_staff_link`
Expected: FAIL — `user.person_id` is absent from the payload.

- [ ] **Step 3: Add link info to `show()`**

In `UserController::show()`, eager-load the relation and add the fields. Change the start of the method:

```php
    public function show(User $user)
    {
        $user->load(['roles', 'permissions', 'person.institution']);

        $this->logSuccess('viewed a user', $user);
```

Then inside the `'user' => [ ... ]` array (returned to `Inertia::render('User/Show', ...)`), add these two keys (e.g. right after `'verified' => ...`):

```php
                'person_id' => $user->person_id,
                'staff' => $user->person ? [
                    'id' => $user->person->id,
                    'name' => $user->person->full_name,
                    'staff_number' => $user->person->institution->first()?->staff?->staff_number,
                ] : null,
```

- [ ] **Step 4: Add link info to `index()`**

In `UserController::index()`, eager-load and expose the same status. Change the query to eager-load:

```php
        $users = User::query()
            ->with('roles', 'permissions', 'person.institution')
            ->withCount(['roles', 'permissions'])
            ->paginate(10)
            ->withQueryString()
            ->through(fn ($user) => [
```

and add these keys inside the `through(...)` array (e.g. after `'verified' => ...`):

```php
                'person_id' => $user->person_id,
                'staff_name' => $user->person?->full_name,
```

- [ ] **Step 5: Run the test to verify it passes**

Run: `php artisan test --filter=test_user_show_payload_includes_staff_link`
Expected: PASS.

- [ ] **Step 6: Run the full feature test file**

Run: `php artisan test tests/Feature/AssociateUserStaffTest.php`
Expected: PASS (all tests).

- [ ] **Step 7: Format and commit**

```bash
vendor/bin/pint --dirty
git add app/Http/Controllers/UserController.php tests/Feature/AssociateUserStaffTest.php
git commit -m "feat: expose user staff-link status in index and show payloads"
```

---

## Task 8: Frontend — association modal, detail/list controls, and staff-role guard

Frontend behavior is fully enforced and tested on the backend (Tasks 3–6). This task wires the UI; verify it with `npm run build` and a manual check.

**Files:**
- Create: `resources/js/Pages/User/partials/AssociateStaff.vue`
- Modify: `resources/js/Pages/User/Show.vue`
- Modify: `resources/js/Pages/User/Index.vue`
- Modify: `resources/js/Pages/User/partials/UserRoles.vue`
- Modify: `resources/js/Pages/User/partials/AddUserRole.vue`
- Modify: `resources/js/Pages/User/partials/UserRoleForm.vue`

- [ ] **Step 1: Create the association modal `AssociateStaff.vue`**

Create `resources/js/Pages/User/partials/AssociateStaff.vue`:

```vue
<script setup>
import { onMounted, ref } from "vue";
import { router } from "@inertiajs/vue3";
import axios from "axios";
import SearchSelect from "@/Components/Forms/SearchSelect.vue";

const emit = defineEmits(["formSubmitted"]);

const props = defineProps({
	user: { type: Number, required: true },
});

const options = ref([]);
const selected = ref(null);
const error = ref("");
const loading = ref(false);

onMounted(async () => {
	const response = await axios.get(route("users.staff-options"));
	options.value = response.data;
});

const submit = () => {
	error.value = "";
	loading.value = true;
	router.patch(
		route("user.associate-staff", { user: props.user }),
		{ person_id: selected.value },
		{
			preserveScroll: true,
			onSuccess: () => emit("formSubmitted"),
			onError: (errors) => {
				error.value = errors.person_id ?? "Could not associate staff record.";
			},
			onFinish: () => {
				loading.value = false;
			},
		},
	);
};
</script>

<template>
	<main class="px-8 py-8 bg-gray-100 dark:bg-gray-700">
		<h1 class="text-2xl pb-4 dark:text-gray-100">Associate Staff Record</h1>
		<SearchSelect
			v-model="selected"
			:options="options"
			:searchable="true"
			label="Staff record"
			placeholder="Search staff by name or staff number"
			:error="error"
		/>
		<div class="flex justify-end pt-6">
			<button
				type="button"
				:disabled="!selected || loading"
				class="rounded-md bg-green-600 dark:bg-gray-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-green-500 disabled:opacity-50"
				@click="submit"
			>
				Associate
			</button>
		</div>
	</main>
</template>
```

- [ ] **Step 2: Add the "Staff record" panel and modal to `Show.vue`**

In `resources/js/Pages/User/Show.vue`:

Add to the `<script setup>` imports and toggles:

```js
import AssociateStaff from "./partials/AssociateStaff.vue";
import { router } from "@inertiajs/vue3";

const openAssociateModal = ref(false);
const toggleAssociateModal = useToggle(openAssociateModal);

const unlinkStaff = () => {
	router.delete(route("user.dissociate-staff", { user: props.user.id }), {
		preserveScroll: true,
	});
};
```

Inside the `<div class="flex flex-col md:flex-row gap-4 w-full">` block (near `UserRoles`), add a staff-record panel:

```vue
<div
	v-if="permissions?.includes('associate user staff')"
	class="rounded-lg bg-green-200 dark:bg-gray-800 p-6 ring-1 ring-gray-900/5 dark:ring-gray-600/80 w-full md:w-2/5"
>
	<h2 class="text-md font-semibold text-gray-900 dark:text-gray-100">
		Staff record
	</h2>
	<p class="mt-2 text-sm text-gray-700 dark:text-gray-200">
		{{ user.staff ? `${user.staff.name} — ${user.staff.staff_number ?? "—"}` : "Not linked" }}
	</p>
	<div class="mt-4 flex gap-3">
		<button
			type="button"
			class="rounded-md bg-green-50 dark:bg-gray-400 px-2 py-1 text-xs font-medium text-green-600 dark:text-gray-50 ring-1 ring-inset ring-green-600/20"
			@click="toggleAssociateModal()"
		>
			{{ user.person_id ? "Change" : "Associate" }}
		</button>
		<button
			v-if="user.person_id"
			type="button"
			class="rounded-md px-2 py-1 text-xs font-medium text-red-600 ring-1 ring-inset ring-red-600/20"
			@click="unlinkStaff"
		>
			Unlink
		</button>
	</div>
</div>
```

Add the modal near the other `<NewModal>` elements at the end of the template:

```vue
<NewModal :show="openAssociateModal" @close="toggleAssociateModal()">
	<AssociateStaff :user="user.id" @form-submitted="toggleAssociateModal()" />
</NewModal>
```

- [ ] **Step 3: Thread `has-staff-record` into the roles components**

In `Show.vue`, update the `<UserRoles ... />` usage to pass link status:

```vue
<UserRoles
	:roles="user.roles"
	:user="user.id"
	:has-staff-record="!!user.person_id"
	class="flex-1"
	:can-add="permissions?.includes('assign roles to user')"
	@close-form="toggleRolesForm()"
/>
```

In `resources/js/Pages/User/partials/UserRoles.vue`, add the prop and forward it:

```js
	hasStaffRecord: {
		type: Boolean,
		default: false,
	},
```

and update the `<AddUserRole ... />` usage:

```vue
<AddUserRole
	:user="user"
	:has-staff-record="props.hasStaffRecord"
	@form-submitted="toggleAddRoleModal()"
/>
```

In `resources/js/Pages/User/partials/AddUserRole.vue`, add the prop and forward it to the form:

```js
const props = defineProps({
	user: { type: Number, required: true },
	hasStaffRecord: { type: Boolean, default: false },
});
```

and update `<UserRoleForm ... />`:

```vue
<UserRoleForm :user-roles="userRoles.roles" :has-staff-record="props.hasStaffRecord" />
```

- [ ] **Step 4: Disable the `staff` checkbox in `UserRoleForm.vue` when unlinked**

In `resources/js/Pages/User/partials/UserRoleForm.vue`, add the prop and disable the `staff` option when there is no staff record. Replace the `<script setup>` and the `FormKit` `:options` binding:

```js
import { CheckIcon } from "@heroicons/vue/20/solid";
import { computed, onMounted, ref } from "vue";

const props = defineProps({
	userRoles: {
		type: Array,
		default: () => [],
	},
	hasStaffRecord: {
		type: Boolean,
		default: false,
	},
});
let roles = ref([]);
const localUserRoles = ref([...props.userRoles]);

const roleOptions = computed(() =>
	roles.value.map((role) =>
		role.value === "staff" && !props.hasStaffRecord
			? {
					...role,
					attrs: { disabled: true },
					help: "Associate a staff record first",
				}
			: role,
	),
);

onMounted(async () => {
	const response = await axios.get(route("roles.list"));
	roles.value = response.data;
});
```

and change the checkbox `:options="roles"` to `:options="roleOptions"`.

- [ ] **Step 5: Add a row action + status to `Index.vue`**

In `resources/js/Pages/User/Index.vue`:
- Import `AssociateStaff` and `NewModal`, and `useToggle`/`ref`.
- Track which user the modal targets:

```js
import AssociateStaff from "./partials/AssociateStaff.vue";

const associateUserId = ref(null);
const openAssociateModal = ref(false);

const openAssociate = (id) => {
	associateUserId.value = id;
	openAssociateModal.value = true;
};
```

- In the actions cell of each user row, add (gated on the `associate user staff` permission, following the page's existing permission-check pattern):

```vue
<button
	type="button"
	class="text-xs font-medium text-green-700 dark:text-gray-100"
	@click="openAssociate(user.id)"
>
	{{ user.person_id ? "Staff linked" : "Associate staff" }}
</button>
```

- Add the modal once, outside the table loop:

```vue
<NewModal v-if="associateUserId" :show="openAssociateModal" @close="openAssociateModal = false">
	<AssociateStaff :user="associateUserId" @form-submitted="openAssociateModal = false" />
</NewModal>
```

(Match the exact table/markup structure already in `Index.vue`; adapt the cell placement to its existing columns.)

- [ ] **Step 6: Build the frontend**

Run: `npm run build`
Expected: build completes with no errors.

- [ ] **Step 7: Manual verification**

Start the app (`composer run dev` / `npm run dev` + `php artisan serve`) and, as a user with `associate user staff` + `assign roles to user`:
1. Open a user with no staff link → "Staff record: Not linked"; the `staff` checkbox in Add Role is disabled with the "Associate a staff record first" hint.
2. Click Associate → search and pick a staff record → panel shows the linked name/number.
3. The `staff` checkbox is now enabled; assigning it succeeds.
4. Click Unlink → link clears and the `staff` role is removed.
5. Confirm an already-linked staff member does not appear in the picker for a different user.

- [ ] **Step 8: Commit**

```bash
git add resources/js/Pages/User/
git commit -m "feat: UI to associate user with staff record and gate staff role"
```

---

## Final Verification

- [ ] **Run the full feature test file**

Run: `php artisan test tests/Feature/AssociateUserStaffTest.php`
Expected: all tests PASS.

- [ ] **Run the related existing suites to check for regressions**

Run: `php artisan test --filter="AllPermissionsSeederTest|StaffCreationTest"`
Expected: PASS.

- [ ] **Format the whole change set**

Run: `vendor/bin/pint --dirty`

- [ ] **Offer to run the full suite**

Ask the user whether to run `php artisan test` in full before merging.

---

## Spec Coverage Check

- Associate user ↔ existing staff record (set `person_id`): Tasks 3, 8.
- Searchable picker sourced from unlinked staff only: Tasks 2, 8 (`SearchSelect`, `staffOptions`).
- One-to-one (block staff already linked): Tasks 2 (excluded from picker) + 3 (`Rule::unique`).
- Entry points on both detail and list: Task 8 (`Show.vue`, `Index.vue`).
- Block `staff` role for unlinked users, server-side: Tasks 5 (`addRole`) + 6 (`addUsers`).
- UI surfaces the gate (disabled checkbox + hint): Task 8.
- Unlink/change allowed; unlink removes `staff` role: Task 4.
- New `associate user staff` permission seeded to super-administrator (and available to grant): Task 1.
- Tests for happy/failure/authorization paths: Tasks 1–7.
