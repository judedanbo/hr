# Staff "My Profile" Redesign — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build a new staff-facing `/my-profile` page that puts photo upload and qualification management front and center for first-time self-service users, while keeping the existing admin `Staff/NewShow.vue` unchanged.

**Architecture:** A new `StaffProfileProvider` service extracts the eager-load-and-map payload currently inlined in `InstitutionPersonController@show` so it can be reused by a new `MyProfileController`. The controller renders `Pages/MyProfile/Index.vue`, composed of focused components under `Components/MyProfile/` that reuse existing modals (`AddQualification`, `EditContactForm`, etc.) and the existing `PersonAvatarController` endpoints. Three new fields on `HandleInertiaRequests` (`person_id`, `has_photo`, `qualifications_count`) power a dismissible completion banner for multi-role users, and `DashboardController::redirectToStaffLanding` is updated to land staff-only users on `/my-profile` instead of the admin staff show.

**Tech Stack:** Laravel 11, Inertia.js v1, Vue 3 + Tailwind 3, PHPUnit 11, Spatie Permission, HeadlessUI, FormKit, existing project image-upload/qualification/contact flows.

**Spec:** `docs/superpowers/specs/2026-04-17-staff-my-profile-redesign-design.md`

**Branch:** `feature/staff-my-profile-redesign`

---

## Task 1: Extract `StaffProfileProvider` service (refactor-safe, TDD)

**Files:**
- Create: `app/Services/StaffProfileProvider.php`
- Modify: `app/Http/Controllers/InstitutionPersonController.php` (lines ~174–400 of `show()`)
- Create: `tests/Unit/Services/StaffProfileProviderTest.php`

- [ ] **Step 1: Add a characterization test for the existing admin payload shape**

Create `tests/Unit/Services/StaffProfileProviderTest.php`:

```php
<?php

namespace Tests\Unit\Services;

use App\Models\InstitutionPerson;
use App\Services\StaffProfileProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StaffProfileProviderTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_null_when_no_active_institution_person_exists(): void
    {
        $provider = new StaffProfileProvider;

        $this->assertNull($provider->forPerson(999_999));
    }

    public function test_returns_payload_with_expected_top_level_keys(): void
    {
        $staff = InstitutionPerson::factory()->create();

        $payload = (new StaffProfileProvider)->forPerson($staff->person_id);

        $this->assertIsArray($payload);
        $this->assertSame(
            ['person', 'qualifications', 'contacts', 'address', 'staff'],
            array_keys($payload),
        );
    }

    public function test_person_block_contains_expected_fields(): void
    {
        $staff = InstitutionPerson::factory()->create();

        $payload = (new StaffProfileProvider)->forPerson($staff->person_id);

        $this->assertArrayHasKey('id', $payload['person']);
        $this->assertArrayHasKey('name', $payload['person']);
        $this->assertArrayHasKey('initials', $payload['person']);
        $this->assertArrayHasKey('image', $payload['person']);
        $this->assertArrayHasKey('identities', $payload['person']);
    }

    public function test_staff_block_contains_expected_employment_fields(): void
    {
        $staff = InstitutionPerson::factory()->create();

        $payload = (new StaffProfileProvider)->forPerson($staff->person_id);

        $this->assertArrayHasKey('staff_id', $payload['staff']);
        $this->assertArrayHasKey('staff_number', $payload['staff']);
        $this->assertArrayHasKey('file_number', $payload['staff']);
        $this->assertArrayHasKey('hire_date', $payload['staff']);
        $this->assertArrayHasKey('ranks', $payload['staff']);
        $this->assertArrayHasKey('units', $payload['staff']);
        $this->assertArrayHasKey('dependents', $payload['staff']);
    }
}
```

- [ ] **Step 2: Run the test — expect class-not-found**

Run: `php artisan test --filter=StaffProfileProviderTest`
Expected: FAIL — `Class "App\Services\StaffProfileProvider" not found`

- [ ] **Step 3: Create the service by moving the eager-load + map from the controller**

Create `app/Services/StaffProfileProvider.php`:

```php
<?php

namespace App\Services;

use App\Enums\QualificationLevelEnum;
use App\Models\InstitutionPerson;
use App\Models\Qualification;
use Carbon\Carbon;

final class StaffProfileProvider
{
    /**
     * Build the profile payload consumed by both the admin Staff/NewShow page
     * and the self-service MyProfile page.
     *
     * @return array{person: array, qualifications: array, contacts: array|null, address: array|null, staff: array}|null
     */
    public function forPerson(int $personId): ?array
    {
        $staff = InstitutionPerson::query()
            ->with([
                'person' => function ($query) {
                    $query->with([
                        'address' => fn ($q) => $q->whereNull('valid_end'),
                        'contacts',
                        'identities' => fn ($q) => $q->withTrashed(),
                        'qualifications',
                    ]);
                },
                'units' => fn ($query) => $query->with(['institution', 'parent']),
                'ranks',
                'dependents.person',
                'statuses',
                'notes.documents',
                'positions' => fn ($query) => $query->withTrashed(),
            ])
            ->active()
            ->where('person_id', $personId)
            ->first();

        if (! $staff) {
            return null;
        }

        return [
            'person' => $this->mapPerson($staff),
            'qualifications' => $this->mapQualifications($staff->person->id),
            'contacts' => $this->mapContacts($staff),
            'address' => $this->mapAddress($staff),
            'staff' => $this->mapStaff($staff),
        ];
    }

    private function mapPerson(InstitutionPerson $staff): array
    {
        return [
            'id' => $staff->person->id,
            'name' => $staff->person->full_name,
            'maiden_name' => $staff->person->maiden_name,
            'dob-value' => $staff->person->date_of_birth,
            'dob' => $staff->person->date_of_birth?->format('d M Y'),
            'age' => $staff->person->age.' years old',
            'gender' => $staff->person->gender?->label(),
            'ssn' => $staff->person->social_security_number,
            'initials' => $staff->person->initials,
            'nationality' => $staff->person->nationality?->nationality(),
            'religion' => $staff->person->religion,
            'marital_status' => $staff->person->marital_status?->label(),
            'image' => $staff->person->image ? '/storage/'.$staff->person->image : null,
            'identities' => $staff->person->identities->count() > 0
                ? $staff->person->identities->map(fn ($id) => [
                    'id' => $id->id,
                    'id_type' => $id->id_type,
                    'id_type_display' => $id->id_type->label(),
                    'id_number' => $id->id_number,
                ])->all()
                : null,
        ];
    }

    private function mapQualifications(int $personId): array
    {
        return Qualification::query()
            ->where('person_id', $personId)
            ->visibleTo(auth()->user(), $personId)
            ->with('documents')
            ->get()
            ->map(fn ($q) => [
                'id' => $q->id,
                'person_id' => $q->person_id,
                'course' => $q->course,
                'institution' => $q->institution,
                'qualification' => $q->qualification,
                'qualification_number' => $q->qualification_number,
                'level' => $q->level ? QualificationLevelEnum::tryFrom($q->level)?->label() ?? $q->level : null,
                'year' => $q->year,
                'status' => $q->status?->label(),
                'status_color' => $q->status?->color(),
                'can_edit' => $q->canBeEditedBy(auth()->user()),
                'can_delete' => $q->canBeDeletedBy(auth()->user()),
                'documents' => $q->documents->count() > 0 ? $q->documents->map(fn ($d) => [
                    'document_type' => $d->document_type,
                    'document_title' => $d->document_title,
                    'document_status' => $d->document_status,
                    'document_number' => $d->document_number,
                    'file_name' => $d->file_name,
                    'file_type' => $d->file_type,
                ])->all() : null,
            ])
            ->all();
    }

    private function mapContacts(InstitutionPerson $staff): ?array
    {
        if ($staff->person->contacts->count() === 0) {
            return null;
        }

        return $staff->person->contacts->map(fn ($c) => [
            'id' => $c->id,
            'contact' => $c->contact,
            'contact_type' => $c->contact_type,
            'contact_type_dis' => $c->contact_type->label(),
            'valid_end' => $c->valid_end,
        ])->all();
    }

    private function mapAddress(InstitutionPerson $staff): ?array
    {
        $address = $staff->person->address->first();
        if (! $address) {
            return null;
        }

        return [
            'id' => $address->id,
            'address_line_1' => $address->address_line_1,
            'address_line_2' => $address->address_line_2,
            'city' => $address->city,
            'region' => $address->region,
            'country' => $address->country,
            'post_code' => $address->post_code,
            'valid_end' => $address->valid_end,
        ];
    }

    private function mapStaff(InstitutionPerson $staff): array
    {
        return [
            'staff_id' => $staff->id,
            'institution_id' => $staff->institution_id,
            'staff_number' => $staff->staff_number,
            'file_number' => $staff->file_number,
            'old_staff_number' => $staff->old_staff_number,
            'hire_date' => $staff->hire_date?->format('d M Y'),
            'hire_date_display' => $staff->hire_date?->diffForHumans(),
            'retirement_date' => $staff->retirement_date_formatted,
            'retirement_date_display' => $staff->retirement_date_diff,
            'start_date' => $staff->start_date?->format('d M Y'),
            'statuses' => $staff->statuses?->map(fn ($s) => [
                'id' => $s->id,
                'status' => $s->status,
                'status_display' => $s->status?->name,
                'description' => $s->description,
                'start_date' => $s->start_date?->format('Y-m-d'),
                'start_date_display' => $s->start_date?->format('d M Y'),
                'end_date' => $s->end_date?->format('Y-m-d'),
                'end_date_display' => $s->end_date?->format('d M Y'),
            ])->all(),
            'staff_type' => $staff->type?->map(fn ($t) => [
                'id' => $t->id,
                'type' => $t->staff_type,
                'type_label' => $t->staff_type->label(),
                'start_date' => $t->start_date?->format('Y-m-d'),
                'start_date_display' => $t->start_date?->format('d M Y'),
                'end_date' => $t->end_date?->format('Y-m-d'),
                'end_date_display' => $t->end_date?->format('d M Y'),
            ])->all(),
            'positions' => $staff->positions?->map(fn ($p) => [
                'id' => $p->id,
                'name' => $p->name,
                'start_date' => $p->pivot->start_date,
                'end_date' => $p->pivot->end_date,
                'start_date_display' => $p->pivot->start_date ? Carbon::parse($p->pivot->start_date)->format('d M Y') : null,
                'end_date_display' => $p->pivot->end_date ? Carbon::parse($p->pivot->end_date)->format('d M Y') : null,
            ])->all(),
            'ranks' => $staff->ranks->map(fn ($r) => [
                'id' => $r->id,
                'name' => $r->name,
                'staff_name' => $staff->person->full_name,
                'staff_id' => $r->pivot->staff_id,
                'rank_id' => $r->pivot->job_id,
                'start_date' => $r->pivot->start_date?->format('d M Y'),
                'start_date_unix' => $r->pivot->start_date?->format('Y-m-d'),
                'end_date' => $r->pivot->end_date?->format('d M Y'),
                'end_date_unix' => $r->pivot->end_date?->format('Y-m-d'),
                'remarks' => $r->pivot->remarks,
                'distance' => $r->pivot->start_date?->diffForHumans(),
            ])->all(),
            'notes' => $staff->notes->count() > 0 ? $staff->notes->map(fn ($n) => [
                'id' => $n->id,
                'note' => $n->note,
                'note_date' => $n->note_date->diffForHumans(),
                'note_date_time' => $n->note_date,
                'note_type' => $n->note_type,
                'created_by' => $n->created_by,
                'url' => $n->documents->count() > 0 ? $n->documents->map(fn ($d) => [
                    'document_type' => $d->document_type,
                    'document_title' => $d->document_title,
                    'file_name' => $d->file_name,
                    'file_type' => $d->file_type,
                ])->all() : null,
            ])->all() : null,
            'units' => $staff->units->map(fn ($u) => [
                'unit_id' => $u->id,
                'unit_name' => $u->name,
                'status' => $u->pivot->status?->label(),
                'status_color' => $u->pivot->status?->color(),
                'department' => $u->parent?->name,
                'department_short_name' => $u->parent?->short_name,
                'staff_id' => $u->pivot->staff_id,
                'start_date' => $u->pivot->start_date?->format('d M Y'),
                'start_date_unix' => $u->pivot->start_date?->format('Y-m-d'),
                'end_date' => $u->pivot->end_date?->format('d M Y'),
                'end_date_unix' => $u->pivot->end_date?->format('Y-m-d'),
                'distance' => $u->pivot->start_date?->diffForHumans(),
                'remarks' => $u->pivot->remarks,
                'old_data' => $u->pivot->old_data,
            ])->all(),
            'dependents' => $staff->dependents ? $staff->dependents->map(fn ($d) => [
                'id' => $d->id,
                'person_id' => $d->person_id,
                'initials' => $d->person->initials,
                'title' => $d->person->title,
                'name' => $d->person->full_name,
                'surname' => $d->person->surname,
                'first_name' => $d->person->first_name,
                'other_names' => $d->person->other_names,
                'maiden_name' => $d->person->maiden_name,
                'nationality' => $d->person->nationality?->label(),
                'nationality_form' => $d->person->nationality,
                'marital_status' => $d->person->marital_status,
                'image' => $d->person->image ? '/storage/'.$d->person->image : null,
                'religion' => $d->person->religion,
                'gender' => $d->person->gender?->label(),
                'gender_form' => $d->person->gender,
                'date_of_birth' => $d->person->date_of_birth?->format('Y-m-d'),
                'age' => $d->person->age.' years old',
                'relation' => $d->relation,
                'staff_id' => $staff->id,
                'contacts' => $d->person->contacts->map(fn ($c) => [
                    'id' => $c->id,
                    'type' => $c->contact_type?->label(),
                    'contact' => $c->contact,
                ])->all(),
            ])->all() : null,
        ];
    }
}
```

- [ ] **Step 4: Run the test — expect pass**

Run: `php artisan test --filter=StaffProfileProviderTest`
Expected: PASS (4 tests)

- [ ] **Step 5: Refactor `InstitutionPersonController@show` to delegate to the service**

In `app/Http/Controllers/InstitutionPersonController.php`, replace the body of `show($staffId)` so the eager-load-and-map is delegated. Keep the `view staff` gate and the staff-ownership guard at the top exactly as they are today.

Replace lines 164–401 with:

```php
public function show($staffId)
{
    if (request()->user()->cannot('view staff')) {
        return redirect()->route('dashboard')->with('error', 'You do not have permission to view staff details');
    }

    if (request()->user()->isStaff()) {
        if (request()->user()->person->institution->first()->staff->id != $staffId) {
            return redirect()->route('dashboard')->with('error', 'You do not have permission to view details of this staff');
        }
    }

    $staff = \App\Models\InstitutionPerson::query()->active()->whereId($staffId)->first();
    if (! $staff) {
        return redirect()->route('person.show', ['person' => $staffId])->with('error', 'Staff not found');
    }

    $payload = app(\App\Services\StaffProfileProvider::class)->forPerson($staff->person_id);

    return \Inertia\Inertia::render('Staff/NewShow', array_merge($payload, [
        'user' => [
            'id' => auth()->user()->id,
            'name' => auth()->user()->name,
            'email' => auth()->user()->email,
            'person_id' => auth()->user()->person_id,
        ],
    ]));
}
```

- [ ] **Step 6: Run the existing admin staff show tests — expect continued pass**

Run: `php artisan test --filter=InstitutionPerson`
Expected: all existing tests pass. If a test calls specific transformers (`$this->detailTransformer`), it's not in scope here — the transformers are still injected in the constructor but the show method no longer uses them. Do not remove them; they are used in other methods (`index` uses `listTransformer`).

Run the full suite as a safety net:
Run: `php artisan test`
Expected: no new failures introduced by this refactor.

- [ ] **Step 7: Format and commit**

Run: `./vendor/bin/pint --dirty`

```bash
git add app/Services/StaffProfileProvider.php app/Http/Controllers/InstitutionPersonController.php tests/Unit/Services/StaffProfileProviderTest.php
git commit -m "refactor: extract StaffProfileProvider service from InstitutionPersonController"
```

---

## Task 2: Create `MyProfileController` and the `/my-profile` route (TDD)

**Files:**
- Create: `app/Http/Controllers/MyProfileController.php`
- Modify: `routes/web.php`
- Create: `tests/Feature/MyProfile/MyProfileShowTest.php`

- [ ] **Step 1: Create the failing feature test**

Create `tests/Feature/MyProfile/MyProfileShowTest.php`:

```php
<?php

namespace Tests\Feature\MyProfile;

use App\Models\InstitutionPerson;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MyProfileShowTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('my-profile.show'))->assertRedirect(route('login'));
    }

    public function test_authenticated_user_without_person_id_gets_403(): void
    {
        $user = User::factory()->create(['person_id' => null]);

        $this->actingAs($user)
            ->get(route('my-profile.show'))
            ->assertForbidden();
    }

    public function test_authenticated_staff_user_sees_their_profile(): void
    {
        $staff = InstitutionPerson::factory()->create();
        $user = User::factory()->create(['person_id' => $staff->person_id]);

        $this->actingAs($user)
            ->get(route('my-profile.show'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('MyProfile/Index')
                ->has('person')
                ->has('staff')
                ->has('qualifications')
                ->where('person.id', $staff->person_id)
            );
    }

    public function test_user_with_no_active_staff_record_sees_404(): void
    {
        $user = User::factory()->create(['person_id' => 9_999_999]);

        $this->actingAs($user)
            ->get(route('my-profile.show'))
            ->assertNotFound();
    }
}
```

- [ ] **Step 2: Run the test — expect route-not-defined**

Run: `php artisan test --filter=MyProfileShowTest`
Expected: FAIL — route `[my-profile.show] not defined` or similar.

- [ ] **Step 3: Create the controller**

Create `app/Http/Controllers/MyProfileController.php`:

```php
<?php

namespace App\Http\Controllers;

use App\Services\StaffProfileProvider;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MyProfileController extends Controller
{
    public function __construct(protected StaffProfileProvider $provider) {}

    public function show(Request $request): Response
    {
        $personId = $request->user()->person_id;
        abort_unless($personId, 403, 'Your account is not linked to a staff record.');

        $payload = $this->provider->forPerson($personId);
        abort_if($payload === null, 404, 'Staff record not found.');

        return Inertia::render('MyProfile/Index', $payload);
    }
}
```

- [ ] **Step 4: Register the route**

In `routes/web.php`, add below the existing `person/{person}/avatar` block (around line 162):

```php
Route::middleware(['auth', 'password_changed'])
    ->get('/my-profile', [\App\Http\Controllers\MyProfileController::class, 'show'])
    ->name('my-profile.show');
```

- [ ] **Step 5: Run the test — expect pass**

Run: `php artisan test --filter=MyProfileShowTest`
Expected: PASS (4 tests). If `assertInertia` fails because the Vue page does not exist yet, that is resolved in Task 4; for now the controller + route test should pass because `Inertia::render` does not require the component file to exist at request time.

- [ ] **Step 6: Format and commit**

Run: `./vendor/bin/pint --dirty`

```bash
git add app/Http/Controllers/MyProfileController.php routes/web.php tests/Feature/MyProfile/MyProfileShowTest.php
git commit -m "feat: add MyProfileController and /my-profile route"
```

---

## Task 3: Share `person_id`, `has_photo`, `qualifications_count` via Inertia (TDD)

**Files:**
- Modify: `app/Http/Middleware/HandleInertiaRequests.php`
- Create: `tests/Feature/MyProfile/SharedPropsTest.php`

- [ ] **Step 1: Create the failing test**

Create `tests/Feature/MyProfile/SharedPropsTest.php`:

```php
<?php

namespace Tests\Feature\MyProfile;

use App\Models\InstitutionPerson;
use App\Models\Qualification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SharedPropsTest extends TestCase
{
    use RefreshDatabase;

    public function test_auth_props_include_person_id_has_photo_and_qualifications_count(): void
    {
        $staff = InstitutionPerson::factory()->create();
        $user = User::factory()->create(['person_id' => $staff->person_id]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertInertia(fn ($page) => $page
                ->where('auth.user.person_id', $staff->person_id)
                ->where('auth.has_photo', false)
                ->where('auth.qualifications_count', 0)
            );
    }

    public function test_has_photo_flips_true_when_person_image_is_set(): void
    {
        $staff = InstitutionPerson::factory()->create();
        $staff->person->update(['image' => 'avatars/example.jpg']);
        $user = User::factory()->create(['person_id' => $staff->person_id]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertInertia(fn ($page) => $page->where('auth.has_photo', true));
    }

    public function test_qualifications_count_reflects_stored_qualifications(): void
    {
        $staff = InstitutionPerson::factory()->create();
        Qualification::factory()->count(3)->create(['person_id' => $staff->person_id]);
        $user = User::factory()->create(['person_id' => $staff->person_id]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertInertia(fn ($page) => $page->where('auth.qualifications_count', 3));
    }

    public function test_props_are_null_for_users_without_person_id(): void
    {
        $user = User::factory()->create(['person_id' => null]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertInertia(fn ($page) => $page
                ->where('auth.has_photo', null)
                ->where('auth.qualifications_count', null)
            );
    }
}
```

> **Note:** `route('dashboard')` redirects. Use `followingRedirects()` only if the final page does not render Inertia (it does, so `assertInertia` works after `get()` against whatever destination the redirect lands on). If the tests fail because of redirect behaviour, swap `get(route('dashboard'))` for `get(route('my-profile.show'))` once Task 2 is in place — same shared props evaluate identically.

- [ ] **Step 2: Run the test — expect fail**

Run: `php artisan test --filter=SharedPropsTest`
Expected: FAIL — keys missing from `auth`.

- [ ] **Step 3: Extend `HandleInertiaRequests::share`**

Modify `app/Http/Middleware/HandleInertiaRequests.php`. Replace the `auth` block inside `share()` with:

```php
'auth' => [
    'user' => fn () => $request->user()
        ? array_merge(
            $request->user()->only('id', 'name', 'email'),
            ['person_id' => $request->user()->person_id],
        )
        : null,
    'roles' => fn () => $request->user()?->getRoleNames(),
    'permissions' => fn () => $request->user()?->getAllPermissions()->pluck('name'),
    'viewMode' => fn () => $request->session()->get('view_mode'),
    'isMultiRoleStaff' => fn () => $request->user()?->isMultiRoleStaff() ?? false,
    'viewModeLabel' => fn () => $this->resolveViewModeLabel($request->user()),
    'has_photo' => fn () => $this->hasPhotoForCurrentUser($request),
    'qualifications_count' => fn () => $this->qualificationsCountForCurrentUser($request),
],
```

Then add these two private helpers to the class:

```php
private function hasPhotoForCurrentUser(Request $request): ?bool
{
    $user = $request->user();
    if (! $user || ! $user->person_id) {
        return null;
    }

    return (bool) \App\Models\Person::query()
        ->whereKey($user->person_id)
        ->whereNotNull('image')
        ->exists();
}

private function qualificationsCountForCurrentUser(Request $request): ?int
{
    $user = $request->user();
    if (! $user || ! $user->person_id) {
        return null;
    }

    return \App\Models\Qualification::query()
        ->where('person_id', $user->person_id)
        ->count();
}
```

- [ ] **Step 4: Run the test — expect pass**

Run: `php artisan test --filter=SharedPropsTest`
Expected: PASS (4 tests). If any test still fails because `route('dashboard')` redirects to a non-Inertia destination, change that specific test's URL to `route('my-profile.show')` (Task 2 already provides it).

- [ ] **Step 5: Format and commit**

Run: `./vendor/bin/pint --dirty`

```bash
git add app/Http/Middleware/HandleInertiaRequests.php tests/Feature/MyProfile/SharedPropsTest.php
git commit -m "feat: share person_id, has_photo and qualifications_count via Inertia"
```

---

## Task 4: Redirect staff-only users to `/my-profile` on dashboard hit (TDD)

**Files:**
- Modify: `app/Http/Controllers/DashboardController.php`
- Create: `tests/Feature/MyProfile/StaffLandingRedirectTest.php`

- [ ] **Step 1: Create the failing test**

Create `tests/Feature/MyProfile/StaffLandingRedirectTest.php`:

```php
<?php

namespace Tests\Feature\MyProfile;

use App\Models\InstitutionPerson;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class StaffLandingRedirectTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Role::findOrCreate('staff');
    }

    public function test_staff_only_user_is_redirected_to_my_profile(): void
    {
        $staff = InstitutionPerson::factory()->create();
        $user = User::factory()->create(['person_id' => $staff->person_id]);
        $user->assignRole('staff');

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertRedirect(route('my-profile.show'));
    }
}
```

- [ ] **Step 2: Run the test — expect fail**

Run: `php artisan test --filter=StaffLandingRedirectTest`
Expected: FAIL — redirects to `staff.show/{id}` instead.

- [ ] **Step 3: Update `redirectToStaffLanding`**

In `app/Http/Controllers/DashboardController.php`, replace `redirectToStaffLanding`:

```php
private function redirectToStaffLanding(User $user): RedirectResponse
{
    if ($user->person_id) {
        return redirect()->route('my-profile.show');
    }

    return redirect()->route('staff.index');
}
```

- [ ] **Step 4: Run the test — expect pass**

Run: `php artisan test --filter=StaffLandingRedirectTest`
Expected: PASS.

- [ ] **Step 5: Run the existing multi-role dashboard tests — expect continued pass**

Run: `php artisan test --filter=MultiRole`
Expected: existing tests pass (they exercise `isMultiRoleStaff` and view_mode paths, which don't touch this helper).

- [ ] **Step 6: Format and commit**

Run: `./vendor/bin/pint --dirty`

```bash
git add app/Http/Controllers/DashboardController.php tests/Feature/MyProfile/StaffLandingRedirectTest.php
git commit -m "feat: land staff-only users on /my-profile instead of admin staff show"
```

---

## Task 5: Create the `MyProfile/Index.vue` page shell

**Files:**
- Create: `resources/js/Pages/MyProfile/Index.vue`

- [ ] **Step 1: Create the page**

Create `resources/js/Pages/MyProfile/Index.vue`:

```vue
<script setup>
import { Head } from "@inertiajs/vue3";
import MainLayout from "@/Layouts/NewAuthenticated.vue";
import IdentityStrip from "@/Components/MyProfile/IdentityStrip.vue";
import PhotoCard from "@/Components/MyProfile/PhotoCard.vue";
import QualificationsCard from "@/Components/MyProfile/QualificationsCard.vue";
import ContactCard from "@/Components/MyProfile/ContactCard.vue";
import ReadOnlyKvCard from "@/Components/MyProfile/ReadOnlyKvCard.vue";
import { computed } from "vue";

const props = defineProps({
	person: { type: Object, required: true },
	staff: { type: Object, required: true },
	qualifications: { type: Array, default: () => [] },
	contacts: { type: Array, default: () => null },
	address: { type: Object, default: () => null },
});

const employmentRows = computed(() => {
	const currentRank = props.staff.ranks?.find((r) => !r.end_date) ?? props.staff.ranks?.[0];
	const currentUnit = props.staff.units?.find((u) => !u.end_date) ?? props.staff.units?.[0];
	return [
		{ key: "Rank", value: currentRank?.name ?? "—" },
		{ key: "Unit", value: currentUnit?.unit_name ?? "—" },
		{ key: "Joined", value: props.staff.hire_date ?? "—" },
	];
});

const dependentRows = computed(() => {
	const deps = props.staff.dependents ?? [];
	const spouse = deps.find((d) => (d.relation ?? "").toLowerCase() === "spouse");
	const childrenCount = deps.filter((d) => (d.relation ?? "").toLowerCase() === "child").length;
	return [
		{ key: "Spouse", value: spouse?.name ?? "—" },
		{ key: "Children", value: String(childrenCount) },
	];
});
</script>

<template>
	<Head title="My Profile" />
	<MainLayout>
		<main class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-6">
			<IdentityStrip
				:person="person"
				:staff="staff"
				:qualifications="qualifications"
				:contacts="contacts"
			/>

			<div class="mt-5 grid grid-cols-1 md:grid-cols-2 gap-5">
				<PhotoCard :person="person" />
				<QualificationsCard
					:qualifications="qualifications"
					:person="{ id: person.id, name: person.name }"
				/>
			</div>

			<div class="mt-5 grid grid-cols-1 md:grid-cols-3 gap-4">
				<ContactCard
					:person-id="person.id"
					:contacts="contacts"
					:address="address"
				/>
				<ReadOnlyKvCard
					title="Employment"
					lock-label="HR-managed"
					:rows="employmentRows"
				/>
				<ReadOnlyKvCard
					title="Dependents"
					lock-label="View only"
					:rows="dependentRows"
					footer="Need changes? Contact HR."
				/>
			</div>
		</main>
	</MainLayout>
</template>
```

- [ ] **Step 2: Commit the shell (child components are stubbed out in following tasks)**

```bash
git add resources/js/Pages/MyProfile/Index.vue
git commit -m "feat: scaffold MyProfile Index page shell"
```

---

## Task 6: Build `IdentityStrip.vue` and `ProfileProgress.vue`

**Files:**
- Create: `resources/js/Components/MyProfile/ProfileProgress.vue`
- Create: `resources/js/Components/MyProfile/IdentityStrip.vue`

- [ ] **Step 1: Create `ProfileProgress.vue`**

Create `resources/js/Components/MyProfile/ProfileProgress.vue`:

```vue
<script setup>
import { computed } from "vue";

const props = defineProps({
	person: { type: Object, required: true },
	qualifications: { type: Array, default: () => [] },
	contacts: { type: Array, default: () => null },
});

const checkpoints = computed(() => {
	const hasPhoto = Boolean(props.person?.image);
	const hasQualification = props.qualifications.length > 0;
	const emails = (props.contacts ?? []).filter(
		(c) => String(c.contact_type).toLowerCase() === "email" && !c.valid_end,
	);
	const phones = (props.contacts ?? []).filter(
		(c) => String(c.contact_type).toLowerCase() === "phone" && !c.valid_end,
	);
	const hasContacts = emails.length > 0 && phones.length > 0;
	return [hasPhoto, hasQualification, hasContacts];
});

const percent = computed(
	() => Math.round((checkpoints.value.filter(Boolean).length / 3) * 100),
);
const isComplete = computed(() => percent.value === 100);
</script>

<template>
	<div
		:class="[
			'inline-flex items-center gap-2 px-3 py-1.5 rounded-full text-xs font-semibold',
			isComplete
				? 'bg-emerald-50 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-200'
				: 'bg-amber-50 text-amber-800 dark:bg-amber-900/40 dark:text-amber-200',
		]"
	>
		<div
			:class="[
				'w-20 h-1.5 rounded-full overflow-hidden',
				isComplete ? 'bg-emerald-200 dark:bg-emerald-800' : 'bg-amber-200 dark:bg-amber-800',
			]"
		>
			<div
				:class="[
					'h-full transition-all',
					isComplete ? 'bg-emerald-600' : 'bg-amber-500',
				]"
				:style="{ width: `${percent}%` }"
			></div>
		</div>
		<span v-if="isComplete">✓ Profile complete</span>
		<span v-else>{{ percent }}% complete</span>
	</div>
</template>
```

- [ ] **Step 2: Create `IdentityStrip.vue`**

Create `resources/js/Components/MyProfile/IdentityStrip.vue`:

```vue
<script setup>
import ProfileProgress from "@/Components/MyProfile/ProfileProgress.vue";
import { computed } from "vue";

const props = defineProps({
	person: { type: Object, required: true },
	staff: { type: Object, required: true },
	qualifications: { type: Array, default: () => [] },
	contacts: { type: Array, default: () => null },
});

const currentRank = computed(
	() => props.staff.ranks?.find((r) => !r.end_date) ?? props.staff.ranks?.[0],
);
const currentDepartment = computed(() => {
	const unit = props.staff.units?.find((u) => !u.end_date) ?? props.staff.units?.[0];
	return unit?.department ?? unit?.unit_name ?? null;
});
</script>

<template>
	<div
		class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-2xl p-5 sm:p-6 shadow-sm flex flex-col sm:flex-row items-center sm:items-stretch gap-4 sm:gap-5"
	>
		<div class="flex-shrink-0">
			<img
				v-if="person.image"
				:src="person.image"
				alt=""
				class="w-[72px] h-[72px] rounded-full object-cover border-2 border-white dark:border-gray-700 shadow"
			/>
			<div
				v-else
				class="w-[72px] h-[72px] rounded-full bg-gradient-to-br from-gray-400 to-gray-600 dark:from-gray-500 dark:to-gray-700 flex items-center justify-center text-white font-bold text-2xl"
			>
				{{ person.initials }}
			</div>
		</div>
		<div class="flex-1 min-w-0 text-center sm:text-left">
			<h1
				class="text-xl sm:text-2xl font-bold tracking-tight text-gray-900 dark:text-gray-50"
			>
				{{ person.name }}
			</h1>
			<p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
				<span v-if="currentRank">{{ currentRank.name }}</span>
				<span v-if="currentRank && currentDepartment"> · </span>
				<span v-if="currentDepartment">{{ currentDepartment }}</span>
				<span v-if="staff.staff_number"> · Staff #{{ staff.staff_number }}</span>
			</p>
		</div>
		<div class="flex items-center justify-center sm:justify-end">
			<ProfileProgress
				:person="person"
				:qualifications="qualifications"
				:contacts="contacts"
			/>
		</div>
	</div>
</template>
```

- [ ] **Step 3: Commit**

```bash
git add resources/js/Components/MyProfile/ProfileProgress.vue resources/js/Components/MyProfile/IdentityStrip.vue
git commit -m "feat: add IdentityStrip and ProfileProgress components"
```

---

## Task 7: Build `PhotoCard.vue` with empty / filled / uploading / error states

**Files:**
- Create: `resources/js/Components/MyProfile/PhotoCard.vue`
- Create: `tests/Feature/MyProfile/MyProfilePhotoUploadTest.php`

- [ ] **Step 1: Create the failing feature test**

Create `tests/Feature/MyProfile/MyProfilePhotoUploadTest.php`:

```php
<?php

namespace Tests\Feature\MyProfile;

use App\Models\InstitutionPerson;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MyProfilePhotoUploadTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_staff_can_upload_own_photo(): void
    {
        Storage::fake('public');
        $staff = InstitutionPerson::factory()->create();
        $user = User::factory()->create(['person_id' => $staff->person_id]);

        $this->actingAs($user)
            ->post(route('person.avatar.update', ['person' => $staff->person_id]), [
                'image' => UploadedFile::fake()->image('me.jpg', 400, 400)->size(500),
            ])
            ->assertSessionDoesntHaveErrors();

        $this->assertNotNull($staff->person->fresh()->image);
    }

    public function test_staff_cannot_upload_photo_for_another_person(): void
    {
        Storage::fake('public');
        $me = InstitutionPerson::factory()->create();
        $someone = InstitutionPerson::factory()->create();
        $user = User::factory()->create(['person_id' => $me->person_id]);

        $response = $this->actingAs($user)
            ->post(route('person.avatar.update', ['person' => $someone->person_id]), [
                'image' => UploadedFile::fake()->image('me.jpg'),
            ]);

        $response->assertForbidden();
    }
}
```

- [ ] **Step 2: Run the test — confirm current behaviour**

Run: `php artisan test --filter=MyProfilePhotoUploadTest`
Expected: the first test passes (confirms the existing endpoint accepts uploads); the second test **may fail** if the existing `PersonAvatarController` does not already enforce ownership. If it fails, open `app/Http/Controllers/PersonAvatarController.php::update`, add `abort_unless($request->user()->person_id === $person->id, 403);` at the top, and re-run. Commit that guard as part of this task.

- [ ] **Step 3: Create `PhotoCard.vue`**

Create `resources/js/Components/MyProfile/PhotoCard.vue`:

```vue
<script setup>
import { ref, computed } from "vue";
import { router } from "@inertiajs/vue3";
import NewModal from "@/Components/NewModal.vue";
import DeleteAvatar from "@/Pages/Staff/DeleteAvatar.vue";

const props = defineProps({
	person: { type: Object, required: true },
});

const isUploading = ref(false);
const errorMessage = ref("");
const isDragging = ref(false);
const fileInput = ref(null);
const showDeleteModal = ref(false);

const accepted = ["image/jpeg", "image/png"];
const maxBytes = 2 * 1024 * 1024;

const statusLabel = computed(() => (props.person.image ? "✓ Set" : "Not set"));
const statusClass = computed(() =>
	props.person.image
		? "bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-200"
		: "bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-200",
);

function openPicker() {
	fileInput.value?.click();
}

function onFileChange(event) {
	const file = event.target.files?.[0];
	if (file) {
		submit(file);
	}
}

function onDrop(event) {
	event.preventDefault();
	isDragging.value = false;
	const file = event.dataTransfer.files?.[0];
	if (file) {
		submit(file);
	}
}

function submit(file) {
	errorMessage.value = "";
	if (!accepted.includes(file.type)) {
		errorMessage.value = "Please choose a JPG or PNG image.";
		return;
	}
	if (file.size > maxBytes) {
		errorMessage.value = "Image must be 2 MB or smaller.";
		return;
	}

	const formData = new FormData();
	formData.append("image", file);

	isUploading.value = true;
	router.post(route("person.avatar.update", { person: props.person.id }), formData, {
		forceFormData: true,
		preserveScroll: true,
		onSuccess: () => {
			router.reload({ only: ["person"] });
		},
		onError: (errors) => {
			errorMessage.value = errors.image ?? "Upload failed — please try again.";
		},
		onFinish: () => {
			isUploading.value = false;
		},
	});
}

function confirmRemove() {
	showDeleteModal.value = true;
}

function remove() {
	router.delete(route("person.avatar.delete", { person: props.person.id }), {
		preserveScroll: true,
		onSuccess: () => {
			showDeleteModal.value = false;
			router.reload({ only: ["person"] });
		},
	});
}
</script>

<template>
	<section
		class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-2xl p-5 sm:p-6 shadow-sm"
	>
		<header class="flex justify-between items-start mb-4">
			<div>
				<h2 class="text-base font-bold text-gray-900 dark:text-gray-50">Your photo</h2>
				<p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
					{{
						person.image
							? "Visible across the HR system."
							: "Help colleagues recognise you. Used across the system."
					}}
				</p>
			</div>
			<span
				class="text-[11px] font-semibold px-2.5 py-1 rounded-full"
				:class="statusClass"
			>{{ statusLabel }}</span>
		</header>

		<!-- FILLED -->
		<div v-if="person.image && !isUploading" class="flex gap-4 items-center">
			<img
				:src="person.image"
				alt="Profile photo"
				class="w-[120px] h-[120px] rounded-xl object-cover border-[3px] border-white dark:border-gray-700 shadow"
			/>
			<div class="flex-1">
				<div class="flex flex-wrap gap-2">
					<button
						type="button"
						class="inline-flex items-center rounded-lg bg-emerald-50 dark:bg-emerald-900/30 text-emerald-800 dark:text-emerald-200 border border-emerald-200 dark:border-emerald-700 px-3 py-1.5 text-xs font-semibold hover:bg-emerald-100"
						@click="openPicker"
					>Change photo</button>
					<button
						type="button"
						class="inline-flex items-center rounded-lg bg-white dark:bg-gray-900 text-red-700 dark:text-red-300 border border-red-200 dark:border-red-700 px-3 py-1.5 text-xs font-semibold hover:bg-red-50 dark:hover:bg-red-900/30"
						@click="confirmRemove"
					>Remove</button>
				</div>
				<p v-if="errorMessage" class="mt-2 text-xs text-red-600 dark:text-red-400">{{ errorMessage }}</p>
			</div>
		</div>

		<!-- EMPTY / ERROR / UPLOADING -->
		<div v-else>
			<div
				:class="[
					'rounded-xl border-2 border-dashed px-6 py-9 text-center transition-colors cursor-pointer',
					isDragging
						? 'border-emerald-600 bg-emerald-50 dark:bg-emerald-900/30'
						: 'border-emerald-500 bg-emerald-50/60 hover:bg-emerald-50 dark:bg-emerald-900/20 dark:hover:bg-emerald-900/30',
					isUploading ? 'opacity-60 pointer-events-none' : '',
				]"
				@dragover.prevent="isDragging = true"
				@dragleave.prevent="isDragging = false"
				@drop="onDrop"
				@click="openPicker"
			>
				<div class="text-4xl">📷</div>
				<p class="mt-2 text-sm font-semibold text-emerald-900 dark:text-emerald-100">
					{{ isUploading ? "Uploading..." : "Drop your photo here" }}
				</p>
				<p class="text-xs text-gray-600 dark:text-gray-400 mt-1">
					JPG or PNG, up to 2 MB · square crop works best
				</p>
				<p class="text-[11px] text-gray-500 mt-3 mb-2">or</p>
				<button
					type="button"
					class="inline-flex items-center rounded-lg bg-emerald-600 px-4 py-2 text-xs font-semibold text-white shadow-sm hover:bg-emerald-700"
					@click.stop="openPicker"
				>Choose a file</button>
			</div>
			<p v-if="errorMessage" class="mt-3 text-xs text-red-600 dark:text-red-400">{{ errorMessage }}</p>
		</div>

		<input
			ref="fileInput"
			type="file"
			class="hidden"
			accept="image/jpeg,image/png"
			@change="onFileChange"
		/>

		<NewModal :show="showDeleteModal" @close="showDeleteModal = false">
			<DeleteAvatar
				@delete-confirmed="remove"
				@close="showDeleteModal = false"
			/>
		</NewModal>
	</section>
</template>
```

- [ ] **Step 4: Run the tests again**

Run: `php artisan test --filter=MyProfilePhotoUploadTest`
Expected: PASS (2 tests). If the ownership guard was added in Step 2, ensure the change is part of this commit.

- [ ] **Step 5: Format and commit**

Run: `./vendor/bin/pint --dirty`

```bash
git add resources/js/Components/MyProfile/PhotoCard.vue tests/Feature/MyProfile/MyProfilePhotoUploadTest.php
# plus app/Http/Controllers/PersonAvatarController.php if the ownership guard was added
git commit -m "feat: add PhotoCard with empty/filled/uploading states and ownership guard"
```

---

## Task 8: Build `QualificationsCard.vue` (reuses existing modals)

**Files:**
- Create: `resources/js/Components/MyProfile/QualificationsCard.vue`
- Create: `tests/Feature/MyProfile/MyProfileQualificationTest.php`

- [ ] **Step 1: Create the failing feature test**

Create `tests/Feature/MyProfile/MyProfileQualificationTest.php`:

```php
<?php

namespace Tests\Feature\MyProfile;

use App\Models\InstitutionPerson;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MyProfileQualificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_user_does_not_receive_approve_permission_in_page_props(): void
    {
        $staff = InstitutionPerson::factory()->create();
        $user = User::factory()->create(['person_id' => $staff->person_id]);

        $this->actingAs($user)
            ->get(route('my-profile.show'))
            ->assertInertia(fn ($page) => $page
                ->where(
                    'auth.permissions',
                    fn ($permissions) => ! collect($permissions)->contains('approve staff qualification'),
                )
            );
    }
}
```

- [ ] **Step 2: Run the test**

Run: `php artisan test --filter=MyProfileQualificationTest`
Expected: PASS — the test describes existing behaviour (the test is a regression guard, not a driver). If it fails because the factory-created user accidentally has the `approve staff qualification` permission, double-check the seeder and adjust the factory.

- [ ] **Step 3: Create `QualificationsCard.vue`**

Create `resources/js/Components/MyProfile/QualificationsCard.vue`:

```vue
<script setup>
import { ref, computed } from "vue";
import { router, usePage } from "@inertiajs/vue3";
import { useToggle } from "@vueuse/core";
import NewModal from "@/Components/NewModal.vue";
import AddQualification from "@/Pages/Qualification/Add.vue";
import EditQualification from "@/Pages/Qualification/Edit.vue";
import DeleteQualification from "@/Pages/Qualification/Delete.vue";
import AttachDocument from "@/Pages/Qualification/AttachDocument.vue";

const props = defineProps({
	qualifications: { type: Array, default: () => [] },
	person: { type: Object, required: true },
});

const page = usePage();
const permissions = computed(() => page.props?.auth?.permissions ?? []);
const canAdd = computed(
	() => permissions.value.includes("create staff qualification")
		|| permissions.value.includes("update staff"),
);
const canExport = computed(() => permissions.value.includes("qualifications.reports.export"));

const openAdd = ref(false);
const openEdit = ref(false);
const openDelete = ref(false);
const openAttach = ref(false);
const toggleAdd = useToggle(openAdd);
const toggleEdit = useToggle(openEdit);
const toggleDelete = useToggle(openDelete);
const toggleAttach = useToggle(openAttach);

const current = ref(null);

function startEdit(q) {
	current.value = q;
	toggleEdit();
}
function startDelete(q) {
	current.value = q;
	toggleDelete();
}
function startAttach(q) {
	current.value = q;
	toggleAttach();
}

function confirmDelete() {
	router.delete(route("qualification.delete", { qualification: current.value.id }), {
		preserveScroll: true,
		onSuccess: () => {
			current.value = null;
			toggleDelete();
			router.reload({ only: ["qualifications"] });
		},
	});
}

function statusTag(status) {
	const tone = (status ?? "").toLowerCase();
	if (tone.includes("approved")) return "bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-200";
	if (tone.includes("pending")) return "bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-200";
	return "bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-200";
}
</script>

<template>
	<section
		class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-2xl p-5 sm:p-6 shadow-sm"
	>
		<header class="flex justify-between items-start mb-4">
			<div>
				<h2 class="text-base font-bold text-gray-900 dark:text-gray-50">Your qualifications</h2>
				<p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
					{{ qualifications.length === 0 ? "Degrees, certificates, training. Reviewed by HR." : `${qualifications.length} added` }}
				</p>
			</div>
			<div class="flex items-center gap-2">
				<a
					v-if="canExport"
					:href="route('qualifications.reports.staff.profile.pdf', person.id)"
					class="text-[11px] font-semibold px-2.5 py-1 rounded-full bg-indigo-50 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-200"
				>PDF</a>
				<span
					v-if="qualifications.length === 0"
					class="text-[11px] font-semibold px-2.5 py-1 rounded-full bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-200"
				>0 added</span>
				<span
					v-else
					class="text-[11px] font-semibold px-2.5 py-1 rounded-full bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-200"
				>✓ Active</span>
			</div>
		</header>

		<!-- EMPTY -->
		<div
			v-if="qualifications.length === 0"
			class="rounded-xl border-2 border-dashed border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-900/40 px-6 py-9 text-center"
		>
			<div class="text-4xl">🎓</div>
			<p class="mt-2 text-sm font-bold text-gray-900 dark:text-gray-100">
				Add your first qualification
			</p>
			<p class="mt-1 text-xs text-gray-600 dark:text-gray-400">
				We'll walk you through it — name, institution, year, and an optional certificate upload.
			</p>
			<button
				v-if="canAdd"
				type="button"
				class="mt-4 w-full rounded-lg bg-emerald-600 hover:bg-emerald-700 px-4 py-2.5 text-sm font-bold text-white shadow-sm"
				@click="toggleAdd()"
			>+ Add qualification</button>
			<div class="flex flex-wrap justify-center gap-1.5 mt-3">
				<span
					v-for="tag in ['Degree', 'Diploma', 'Certificate', 'Training']"
					:key="tag"
					class="px-2.5 py-0.5 rounded-full bg-gray-100 dark:bg-gray-700 text-[11px] text-gray-600 dark:text-gray-300"
				>{{ tag }}</span>
			</div>
		</div>

		<!-- FILLED -->
		<ul v-else class="space-y-2">
			<li
				v-for="q in qualifications"
				:key="q.id"
				class="flex items-center gap-3 rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/40 px-3 py-2.5"
			>
				<span class="w-9 h-9 rounded-md bg-emerald-50 dark:bg-emerald-900/40 text-emerald-700 dark:text-emerald-200 flex items-center justify-center text-base">🎓</span>
				<div class="flex-1 min-w-0">
					<div class="text-sm font-bold truncate text-gray-900 dark:text-gray-50">{{ q.qualification || q.course }}</div>
					<div class="text-[11px] text-gray-500 dark:text-gray-400 mt-0.5 flex flex-wrap items-center gap-x-1.5">
						<span v-if="q.institution">{{ q.institution }}</span>
						<span v-if="q.year">· {{ q.year }}</span>
						<span v-if="q.status" :class="['px-2 py-0.5 rounded-full text-[10px] font-semibold', statusTag(q.status)]">{{ q.status }}</span>
						<span v-if="q.documents" class="text-gray-500">· {{ q.documents.length }} document{{ q.documents.length === 1 ? "" : "s" }}</span>
					</div>
				</div>
				<div class="flex items-center gap-2 text-[11px] text-emerald-700 dark:text-emerald-300 font-semibold">
					<button v-if="q.can_edit" type="button" class="hover:underline" @click="startAttach(q)">Attach</button>
					<button v-if="q.can_edit" type="button" class="hover:underline" @click="startEdit(q)">Edit</button>
					<button v-if="q.can_delete" type="button" class="hover:underline text-red-600 dark:text-red-400" @click="startDelete(q)">Delete</button>
				</div>
			</li>
			<li>
				<button
					v-if="canAdd"
					type="button"
					class="w-full rounded-lg border border-dashed border-emerald-500 bg-emerald-50 dark:bg-emerald-900/30 text-emerald-800 dark:text-emerald-200 px-3 py-2.5 text-sm font-semibold hover:bg-emerald-100"
					@click="toggleAdd()"
				>+ Add another qualification</button>
			</li>
		</ul>

		<NewModal :show="openAdd" @close="toggleAdd()">
			<AddQualification
				:person="person.id"
				:qualification-levels="page.props.qualificationLevels"
				@form-submitted="() => { toggleAdd(); router.reload({ only: ['qualifications'] }); }"
			/>
		</NewModal>
		<NewModal :show="openEdit" @close="toggleEdit()">
			<EditQualification
				:person="person.id"
				:qualification="current"
				@form-submitted="() => { toggleEdit(); router.reload({ only: ['qualifications'] }); }"
			/>
		</NewModal>
		<NewModal :show="openDelete" @close="toggleDelete()">
			<DeleteQualification
				:person="person.name"
				@close="toggleDelete()"
				@delete-confirmed="confirmDelete"
			/>
		</NewModal>
		<NewModal :show="openAttach" @close="toggleAttach()">
			<AttachDocument
				:qualification="current"
				@close="toggleAttach()"
				@form-submitted="() => { toggleAttach(); router.reload({ only: ['qualifications'] }); }"
			/>
		</NewModal>
	</section>
</template>
```

- [ ] **Step 2b: Verify the existing Add.vue/Edit.vue prop names match**

Open `resources/js/Pages/Qualification/Add.vue` and confirm it accepts `person`, `qualificationLevels`, and emits `form-submitted`. If any prop or event name differs, adjust the usage in this card to match — **do not modify `Add.vue`**, as it is still used by `PersonQualifications.vue`.

- [ ] **Step 3: Commit**

```bash
git add resources/js/Components/MyProfile/QualificationsCard.vue tests/Feature/MyProfile/MyProfileQualificationTest.php
git commit -m "feat: add QualificationsCard with empty + filled states"
```

---

## Task 9: Build `ContactCard.vue`

**Files:**
- Create: `resources/js/Components/MyProfile/ContactCard.vue`
- Create: `tests/Feature/MyProfile/MyProfileContactEditTest.php`

- [ ] **Step 1: Find the existing contact-edit endpoint**

Before writing the component, confirm the route name used by `EditContactForm.vue` today (it is imported into `NewShow.vue`). Open `resources/js/Pages/Staff/EditContactForm.vue` and note the `router.post`/`router.put` URL and prop shape. The component to wire up is exactly the same — we just host it from a new card.

- [ ] **Step 2: Create `ContactCard.vue`**

Create `resources/js/Components/MyProfile/ContactCard.vue`:

```vue
<script setup>
import { ref, computed } from "vue";
import { useToggle } from "@vueuse/core";
import NewModal from "@/Components/NewModal.vue";
import EditContactForm from "@/Pages/Staff/EditContactForm.vue";

const props = defineProps({
	personId: { type: Number, required: true },
	contacts: { type: Array, default: () => null },
	address: { type: Object, default: () => null },
});

const openEdit = ref(false);
const toggleEdit = useToggle(openEdit);

const email = computed(
	() =>
		(props.contacts ?? []).find(
			(c) => String(c.contact_type).toLowerCase() === "email" && !c.valid_end,
		)?.contact ?? "—",
);
const phone = computed(
	() =>
		(props.contacts ?? []).find(
			(c) => String(c.contact_type).toLowerCase() === "phone" && !c.valid_end,
		)?.contact ?? "—",
);
const addressDisplay = computed(() => {
	if (!props.address) return "—";
	const parts = [
		props.address.address_line_1,
		props.address.city,
		props.address.region,
	].filter(Boolean);
	return parts.length ? parts.join(", ") : "—";
});
</script>

<template>
	<section
		class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-4 shadow-sm"
	>
		<header class="flex justify-between items-center mb-3">
			<h3 class="text-sm font-bold text-gray-900 dark:text-gray-50">Contact</h3>
			<button
				type="button"
				class="text-[11px] font-semibold text-emerald-700 dark:text-emerald-300 hover:underline"
				@click="toggleEdit()"
			>Edit</button>
		</header>

		<dl class="divide-y divide-gray-100 dark:divide-gray-700 text-sm">
			<div class="flex justify-between py-1.5">
				<dt class="text-gray-500 dark:text-gray-400">Email</dt>
				<dd class="font-medium text-gray-900 dark:text-gray-100 truncate max-w-[60%] text-right">{{ email }}</dd>
			</div>
			<div class="flex justify-between py-1.5">
				<dt class="text-gray-500 dark:text-gray-400">Phone</dt>
				<dd class="font-medium text-gray-900 dark:text-gray-100 truncate max-w-[60%] text-right">{{ phone }}</dd>
			</div>
			<div class="flex justify-between py-1.5">
				<dt class="text-gray-500 dark:text-gray-400">Address</dt>
				<dd class="font-medium text-gray-900 dark:text-gray-100 truncate max-w-[60%] text-right">{{ addressDisplay }}</dd>
			</div>
		</dl>

		<NewModal :show="openEdit" @close="toggleEdit()">
			<EditContactForm
				:contact="personId"
				@form-submitted="toggleEdit()"
			/>
		</NewModal>
	</section>
</template>
```

- [ ] **Step 3: Create the contact feature test**

Create `tests/Feature/MyProfile/MyProfileContactEditTest.php`:

```php
<?php

namespace Tests\Feature\MyProfile;

use App\Models\Contact;
use App\Models\InstitutionPerson;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MyProfileContactEditTest extends TestCase
{
    use RefreshDatabase;

    public function test_my_profile_payload_exposes_contacts_and_address(): void
    {
        $staff = InstitutionPerson::factory()->create();
        Contact::factory()->create([
            'person_id' => $staff->person_id,
            'contact_type' => 'email',
            'contact' => 'me@example.org',
        ]);
        $user = User::factory()->create(['person_id' => $staff->person_id]);

        $this->actingAs($user)
            ->get(route('my-profile.show'))
            ->assertInertia(fn ($page) => $page
                ->has('contacts')
                ->where('contacts.0.contact', 'me@example.org')
            );
    }
}
```

- [ ] **Step 4: Run tests**

Run: `php artisan test --filter=MyProfileContactEditTest`
Expected: PASS. If `Contact::factory()` doesn't exist, substitute with the model's create method using fields already present in a `ContactFactory` if one exists; otherwise, inline a `Contact::create(...)` call with required fields after inspecting `app/Models/Contact.php`'s `$fillable`.

- [ ] **Step 5: Commit**

```bash
git add resources/js/Components/MyProfile/ContactCard.vue tests/Feature/MyProfile/MyProfileContactEditTest.php
git commit -m "feat: add ContactCard wrapping existing EditContactForm"
```

---

## Task 10: Build `ReadOnlyKvCard.vue`

**Files:**
- Create: `resources/js/Components/MyProfile/ReadOnlyKvCard.vue`

- [ ] **Step 1: Create the component**

Create `resources/js/Components/MyProfile/ReadOnlyKvCard.vue`:

```vue
<script setup>
defineProps({
	title: { type: String, required: true },
	rows: {
		type: Array,
		required: true,
	},
	lockLabel: { type: String, default: "" },
	footer: { type: String, default: "" },
});
</script>

<template>
	<section
		class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-4 shadow-sm"
	>
		<header class="flex justify-between items-center mb-3">
			<h3 class="text-sm font-bold text-gray-900 dark:text-gray-50">{{ title }}</h3>
			<span
				v-if="lockLabel"
				class="text-[11px] text-gray-400 dark:text-gray-500"
			>{{ lockLabel }}</span>
		</header>

		<dl class="divide-y divide-gray-100 dark:divide-gray-700 text-sm">
			<div v-for="row in rows" :key="row.key" class="flex justify-between py-1.5">
				<dt class="text-gray-500 dark:text-gray-400">{{ row.key }}</dt>
				<dd class="font-medium text-gray-900 dark:text-gray-100 truncate max-w-[60%] text-right">
					{{ row.value }}
				</dd>
			</div>
		</dl>

		<p v-if="footer" class="mt-3 text-[11px] italic text-gray-500 dark:text-gray-400">
			{{ footer }}
		</p>
	</section>
</template>
```

- [ ] **Step 2: Manual smoke — browse `/my-profile` in dev**

```bash
npm run dev  # in one terminal
php artisan serve  # in another
```

Log in as a staff user (or use the user linked to an `InstitutionPerson` factory record in a seeded DB). Confirm the page loads, the identity strip shows, photo card shows "Not set" empty state, qualifications card shows the empty state, and the three secondary cards render. Fix any Vite import errors before committing.

- [ ] **Step 3: Commit**

```bash
git add resources/js/Components/MyProfile/ReadOnlyKvCard.vue
git commit -m "feat: add ReadOnlyKvCard for Employment and Dependents"
```

---

## Task 11: Add "My Profile" nav link in `NewAuthenticated.vue`

**Files:**
- Modify: `resources/js/Layouts/NewAuthenticated.vue`

- [ ] **Step 1: Find the `navigation` array (around line 32)**

The existing array has entries like `Dashboard`, `Staff`, etc., each with `{ name, href, icon, current, visible }`.

- [ ] **Step 2: Add a `My Profile` entry**

Insert this entry as the FIRST item of the `navigation` array (before `Dashboard`):

```js
{
    name: "My Profile",
    href: route("my-profile.show"),
    icon: UserGroupIcon,
    current: route().current("my-profile.*"),
    visible: Boolean(page.props?.auth?.user?.person_id),
},
```

`UserGroupIcon` is already imported at the top of the file. If you prefer a different icon, add the import (`UserIcon` or `IdentificationIcon` from `@heroicons/vue/24/outline`) alongside the existing `UserGroupIcon` import.

- [ ] **Step 3: Manual smoke**

Reload `/my-profile` and any dashboard route; confirm the link appears for users with a `person_id` and is hidden for users without one (e.g., a super-admin created without a linked person).

- [ ] **Step 4: Commit**

```bash
git add resources/js/Layouts/NewAuthenticated.vue
git commit -m "feat: add My Profile link to authenticated nav"
```

---

## Task 12: Build `CompletionBanner.vue` and show it on the admin landing

**Files:**
- Create: `resources/js/Components/MyProfile/CompletionBanner.vue`
- Modify: `resources/js/Pages/Institution/Show.vue` (admin landing)

- [ ] **Step 1: Confirm the admin landing page**

`DashboardController::redirectToAdminDashboard` redirects to `route('institution.show', [1])`. The banner should appear on `resources/js/Pages/Institution/Show.vue`. Open that file and locate a suitable slot near the top of the main content area — typically just after the page `<Head />` or the first layout heading. If the file structure differs, insert the banner component at the top of the returned `<template>`, wrapped in a container matching existing page padding.

- [ ] **Step 2: Create the banner**

Create `resources/js/Components/MyProfile/CompletionBanner.vue`:

```vue
<script setup>
import { computed, ref, onMounted } from "vue";
import { Link, usePage } from "@inertiajs/vue3";

const page = usePage();
const auth = computed(() => page.props?.auth ?? {});

const hasPersonId = computed(() => Boolean(auth.value?.user?.person_id));
const hasPhoto = computed(() => auth.value?.has_photo === true);
const hasQuals = computed(() => (auth.value?.qualifications_count ?? 0) > 0);

const dismissedKey = "my-profile.banner.dismissed";
const dismissed = ref(false);

onMounted(() => {
	dismissed.value = sessionStorage.getItem(dismissedKey) === "1";
});

const shouldShow = computed(
	() => hasPersonId.value && (!hasPhoto.value || !hasQuals.value) && !dismissed.value,
);

function dismiss() {
	sessionStorage.setItem(dismissedKey, "1");
	dismissed.value = true;
}

const message = computed(() => {
	if (!hasPhoto.value && !hasQuals.value) return "Add your photo and first qualification.";
	if (!hasPhoto.value) return "Add your profile photo.";
	return "Add your first qualification.";
});
</script>

<template>
	<div
		v-if="shouldShow"
		class="flex items-center gap-3 rounded-xl bg-emerald-50 dark:bg-emerald-900/30 border border-emerald-200 dark:border-emerald-700 px-4 py-3"
	>
		<div class="text-2xl">📝</div>
		<div class="flex-1 text-sm text-emerald-900 dark:text-emerald-100">
			<strong>Complete your profile.</strong>
			{{ message }}
		</div>
		<Link
			:href="route('my-profile.show')"
			class="inline-flex items-center rounded-lg bg-emerald-600 hover:bg-emerald-700 px-3 py-1.5 text-xs font-semibold text-white"
		>Open My Profile →</Link>
		<button
			type="button"
			class="text-emerald-700 dark:text-emerald-300 text-xs font-semibold hover:underline"
			@click="dismiss"
		>Dismiss</button>
	</div>
</template>
```

- [ ] **Step 3: Mount the banner on `Institution/Show.vue`**

Add the import near the other component imports:

```js
import CompletionBanner from "@/Components/MyProfile/CompletionBanner.vue";
```

And render it at the top of the main content, e.g.:

```vue
<CompletionBanner class="mb-4" />
```

- [ ] **Step 4: Manual smoke**

Log in as an admin user whose account *also* has a `person_id` but no photo → the banner should appear on `/institution/{id}`. Click "Dismiss" → it should stay hidden for the session. Reload after closing the tab → banner reappears (sessionStorage was cleared).

- [ ] **Step 5: Commit**

```bash
git add resources/js/Components/MyProfile/CompletionBanner.vue resources/js/Pages/Institution/Show.vue
git commit -m "feat: show profile-completion banner on admin landing for staff-linked users"
```

---

## Task 13: Final regression + manual verification

**Files:**
- None (this is a verification gate)

- [ ] **Step 1: Run the full backend suite**

```bash
./vendor/bin/pint --dirty
php artisan test
```

Expected: PASS. The specific new tests added are:
- `Tests\Unit\Services\StaffProfileProviderTest` (4)
- `Tests\Feature\MyProfile\MyProfileShowTest` (4)
- `Tests\Feature\MyProfile\SharedPropsTest` (4)
- `Tests\Feature\MyProfile\StaffLandingRedirectTest` (1)
- `Tests\Feature\MyProfile\MyProfilePhotoUploadTest` (2)
- `Tests\Feature\MyProfile\MyProfileQualificationTest` (1)
- `Tests\Feature\MyProfile\MyProfileContactEditTest` (1)

That's 17 new tests. Existing admin tests must still pass.

- [ ] **Step 2: Run frontend linters**

```bash
npm run lint
npm run format
npm run build
```

Expected: no errors; bundle builds.

- [ ] **Step 3: Manual browser checklist**

`php artisan serve` and `npm run dev`, log in as:

1. **Staff-only user (no photo, no qualifications):**
   - Hitting `/dashboard` redirects to `/my-profile`.
   - Page shows identity strip, amber "0% complete" chip (or 33% if contacts already set), empty photo drop zone, empty qualifications card with `+ Add qualification`.
   - "My Profile" appears in nav.
   - Upload a JPG under 2 MB — card flips to filled state, progress chip updates.
   - Upload a non-image — inline error, no crash.
   - Upload a >2 MB image — inline error.
   - Click `+ Add qualification` → modal opens, submit → list renders the new row.
   - Open Contact → edit email → save → card reflects new email.

2. **Multi-role user with admin dashboard access:**
   - Choose "admin" mode → land on `/institution/1` → banner visible if photo missing.
   - Dismiss banner → gone.
   - Reload page in same tab → still hidden.
   - Open new tab → banner returns.

3. **User without `person_id`:**
   - `/my-profile` returns 403.
   - Nav no longer shows "My Profile".
   - Admin dashboard banner not shown.

4. **Dark mode:**
   - Toggle to dark theme; confirm identity strip, priority cards, secondary cards, progress chip, and banner all have legible dark variants.

5. **Mobile (<768 px):**
   - Identity strip stacks; priority grid becomes a column; secondary grid becomes a column; drop zone still acts as a tap target.

- [ ] **Step 4: Commit any polish found during manual verification**

```bash
./vendor/bin/pint --dirty
git add -A
git commit -m "chore: polish from My Profile manual verification pass"
```

- [ ] **Step 5: Push branch**

```bash
git push -u origin feature/staff-my-profile-redesign
```

- [ ] **Step 6: Open PR**

```bash
gh pr create --title "feat: staff My Profile page with photo and qualifications focus" --body "$(cat <<'EOF'
## Summary
- New `/my-profile` route + `MyProfileController` for staff self-service
- `StaffProfileProvider` service extracts payload shared between admin and self-service pages
- New UI with photo upload and qualifications as the two primary actions
- Editable contact card; read-only employment and dependents
- Completion banner on admin landing for staff-linked users
- Staff-only login lands on `/my-profile` instead of admin staff show

Spec: docs/superpowers/specs/2026-04-17-staff-my-profile-redesign-design.md

## Test plan
- [ ] Backend suite passes (`php artisan test`)
- [ ] Staff user sees new page on login and can upload photo
- [ ] Staff user can add a qualification end-to-end
- [ ] Staff user can edit their email / phone / address
- [ ] Admin view (`/staff/{id}`) unchanged
- [ ] Multi-role user sees dismissible banner on admin landing
- [ ] Mobile layout stacks correctly
- [ ] Dark mode legible

🤖 Generated with [Claude Code](https://claude.com/claude-code)
EOF
)"
```

---

## Spec → Task Coverage Map

| Spec requirement | Task |
|---|---|
| New `/my-profile` route + controller | Task 2 |
| Shared `StaffProfileProvider` + admin refactor | Task 1 |
| Shared Inertia props (`person_id`, `has_photo`, `qualifications_count`) | Task 3 |
| Staff-only landing redirected to My Profile | Task 4 |
| `MyProfile/Index.vue` page shell | Task 5 |
| `IdentityStrip` + progress chip | Task 6 |
| `PhotoCard` empty/filled/uploading/error + inline remove | Task 7 |
| `QualificationsCard` empty/filled + reuse of existing modals | Task 8 |
| `ContactCard` using existing EditContactForm | Task 9 |
| `ReadOnlyKvCard` for Employment + Dependents | Task 10 |
| Nav link | Task 11 |
| Dashboard banner for multi-role users | Task 12 |
| Feature test suite | Tasks 2, 3, 4, 7, 8, 9 |
| Regression + manual verification | Task 13 |

Admin approval / email verification / phone OTP are explicitly out of scope (deferred to Spec 2) and have no tasks here.
