# Unit Show — Recursive Stats & Paginated Staff Directory Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Fix inaccurate unit totals, sub-unit card figures, and rank distribution on the Unit show page so they sum across every nested level, and convert the Staff Directory to a server-paginated, server-filtered list that includes every descendant's staff.

**Architecture:** A new `UnitHierarchy` service collects descendant unit IDs once per request. `UnitController@show` uses it to build root stats, per-sub-unit aggregates, and the rank distribution with `whereIn` queries. A new `UnitController@staff` Inertia partial endpoint powers server-side pagination/search/filters. Frontend swaps bespoke stat cards for `StatCard.vue`, and `StaffDirectorySection.vue` becomes server-driven via `router.reload`.

**Tech Stack:** Laravel 11, Inertia.js v1, Vue 3 + Tailwind 3, PHPUnit 11, Spatie Permission, Maatwebsite Excel.

**Spec:** `docs/superpowers/specs/2026-04-16-unit-show-recursive-stats-and-pagination-design.md`

**Branch:** `fix/unit-show-recursive-stats-and-pagination`

---

## Task 1: Build the UnitHierarchy service (TDD)

**Files:**
- Create: `app/Services/UnitHierarchy.php`
- Create: `tests/Unit/Services/UnitHierarchyTest.php`

- [ ] **Step 1: Create the failing unit test**

Create `tests/Unit/Services/UnitHierarchyTest.php`:

```php
<?php

namespace Tests\Unit\Services;

use App\Models\Unit;
use App\Services\UnitHierarchy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UnitHierarchyTest extends TestCase
{
    use RefreshDatabase;

    private UnitHierarchy $hierarchy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->hierarchy = new UnitHierarchy;
    }

    public function test_leaf_unit_returns_only_its_own_id(): void
    {
        $leaf = Unit::factory()->create();

        $ids = $this->hierarchy->descendantIds($leaf);

        $this->assertSame([$leaf->id], $ids);
    }

    public function test_descendant_ids_walks_three_levels(): void
    {
        $root = Unit::factory()->create(['unit_id' => null]);
        $child = Unit::factory()->create(['unit_id' => $root->id]);
        $grandchild = Unit::factory()->create(['unit_id' => $child->id]);
        $greatgrand = Unit::factory()->create(['unit_id' => $grandchild->id]);

        $ids = $this->hierarchy->descendantIds($root);

        sort($ids);
        $expected = [$root->id, $child->id, $grandchild->id, $greatgrand->id];
        sort($expected);
        $this->assertSame($expected, $ids);
    }

    public function test_ended_units_are_excluded(): void
    {
        $root = Unit::factory()->create(['unit_id' => null]);
        $active = Unit::factory()->create(['unit_id' => $root->id]);
        Unit::factory()->ended()->create(['unit_id' => $root->id]);

        $ids = $this->hierarchy->descendantIds($root);

        sort($ids);
        $expected = [$root->id, $active->id];
        sort($expected);
        $this->assertSame($expected, $ids);
    }

    public function test_grouped_by_child_buckets_subtrees(): void
    {
        $root = Unit::factory()->create(['unit_id' => null]);
        $childA = Unit::factory()->create(['unit_id' => $root->id]);
        $childB = Unit::factory()->create(['unit_id' => $root->id]);
        $grandA = Unit::factory()->create(['unit_id' => $childA->id]);
        $grandB = Unit::factory()->create(['unit_id' => $childB->id]);

        $groups = $this->hierarchy->descendantIdsGroupedByChild($root);

        $this->assertArrayHasKey($childA->id, $groups);
        $this->assertArrayHasKey($childB->id, $groups);
        sort($groups[$childA->id]);
        sort($groups[$childB->id]);
        $this->assertSame([$childA->id, $grandA->id], $groups[$childA->id]);
        $this->assertSame([$childB->id, $grandB->id], $groups[$childB->id]);
    }
}
```

- [ ] **Step 2: Run the failing tests**

Run: `php artisan test tests/Unit/Services/UnitHierarchyTest.php`
Expected: FAIL — `Class "App\Services\UnitHierarchy" not found`.

- [ ] **Step 3: Implement the service**

Create `app/Services/UnitHierarchy.php`:

```php
<?php

namespace App\Services;

use App\Models\Unit;
use Illuminate\Support\Facades\DB;

final class UnitHierarchy
{
    /**
     * Return the ID of $unit plus every active descendant unit's ID at any depth.
     *
     * @return int[]
     */
    public function descendantIds(Unit $unit): array
    {
        $collected = [$unit->id];
        $frontier = [$unit->id];

        while (! empty($frontier)) {
            $next = DB::table('units')
                ->whereIn('unit_id', $frontier)
                ->whereNull('end_date')
                ->whereNull('deleted_at')
                ->pluck('id')
                ->all();

            if (empty($next)) {
                break;
            }

            $collected = array_merge($collected, $next);
            $frontier = $next;
        }

        return array_values(array_unique($collected));
    }

    /**
     * For each immediate active child of $unit, return its subtree IDs (child + descendants).
     *
     * @return array<int, int[]>  keyed by child unit id
     */
    public function descendantIdsGroupedByChild(Unit $unit): array
    {
        $children = DB::table('units')
            ->where('unit_id', $unit->id)
            ->whereNull('end_date')
            ->whereNull('deleted_at')
            ->pluck('id')
            ->all();

        $groups = [];
        foreach ($children as $childId) {
            $child = Unit::find($childId);
            $groups[$childId] = $child ? $this->descendantIds($child) : [$childId];
        }

        return $groups;
    }
}
```

- [ ] **Step 4: Run the tests and confirm they pass**

Run: `php artisan test tests/Unit/Services/UnitHierarchyTest.php`
Expected: 4 passed.

- [ ] **Step 5: Commit**

```bash
git add app/Services/UnitHierarchy.php tests/Unit/Services/UnitHierarchyTest.php
git commit -m "feat: add UnitHierarchy service for recursive descendant walking"
```

---

## Task 2: Add StaffDirectoryFilterRequest

**Files:**
- Create: `app/Http/Requests/StaffDirectoryFilterRequest.php`

- [ ] **Step 1: Create the form request**

Create `app/Http/Requests/StaffDirectoryFilterRequest.php`:

```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StaffDirectoryFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:100'],
            'job_category_id' => ['nullable', 'integer', 'exists:job_categories,id'],
            'rank_id' => ['nullable', 'integer', 'exists:jobs,id'],
            'sub_unit_id' => ['nullable', 'integer', 'exists:units,id'],
            'gender' => ['nullable', 'string', 'in:M,F'],
            'hire_date_from' => ['nullable', 'date'],
            'hire_date_to' => ['nullable', 'date', 'after_or_equal:hire_date_from'],
            'age_from' => ['nullable', 'integer', 'min:16', 'max:100'],
            'age_to' => ['nullable', 'integer', 'min:16', 'max:100', 'gte:age_from'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
```

- [ ] **Step 2: Commit**

```bash
git add app/Http/Requests/StaffDirectoryFilterRequest.php
git commit -m "feat: add StaffDirectoryFilterRequest for unit staff filters"
```

---

## Task 3: Add UnitController@staff paginated loader (TDD)

**Files:**
- Modify: `app/Http/Controllers/UnitController.php`
- Modify: `routes/web.php:218` (add new route under the unit group)
- Create: `tests/Feature/Unit/StaffDirectoryTest.php`

The loader returns a 15-per-page paginator over every descendant unit's active staff, with server-side filters and filter-option dropdowns derived from the descendant set.

- [ ] **Step 1: Create the feature test file**

Create `tests/Feature/Unit/StaffDirectoryTest.php`:

```php
<?php

namespace Tests\Feature\Unit;

use App\Models\InstitutionPerson;
use App\Models\Job;
use App\Models\JobCategory;
use App\Models\Person;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StaffDirectoryTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\UnitsPermissionSeeder::class);
        $this->user = User::factory()->create();
        $this->user->givePermissionTo('view unit');
    }

    /**
     * Create an active staff record attached to $unit with optional gender and rank.
     */
    private function makeActiveStaff(Unit $unit, ?string $gender = null, ?Job $rank = null): InstitutionPerson
    {
        $person = Person::factory()->create(
            $gender !== null ? ['gender' => $gender] : []
        );
        $staff = InstitutionPerson::factory()->create([
            'institution_id' => $unit->institution_id,
            'person_id' => $person->id,
        ]);
        $staff->statuses()->create([
            'status' => 'A',
            'start_date' => now()->subYear(),
            'institution_id' => $unit->institution_id,
        ]);
        $staff->units()->attach($unit->id, ['start_date' => now()->subYear()]);
        if ($rank) {
            $staff->ranks()->attach($rank->id, ['start_date' => now()->subYear()]);
        }

        return $staff;
    }

    public function test_staff_endpoint_returns_staff_from_all_descendants(): void
    {
        $root = Unit::factory()->create(['unit_id' => null]);
        $child = Unit::factory()->create(['unit_id' => $root->id, 'institution_id' => $root->institution_id]);
        $grand = Unit::factory()->create(['unit_id' => $child->id, 'institution_id' => $root->institution_id]);

        $this->makeActiveStaff($root);
        $this->makeActiveStaff($child);
        $this->makeActiveStaff($grand);

        $response = $this->actingAs($this->user)->get(route('unit.staff', ['unit' => $root->id]));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Unit/Show')
            ->has('staff.data', 3)
            ->has('staff.meta')
        );
    }

    public function test_staff_endpoint_paginates_at_fifteen_per_page(): void
    {
        $unit = Unit::factory()->create();
        for ($i = 0; $i < 20; $i++) {
            $this->makeActiveStaff($unit);
        }

        $response = $this->actingAs($this->user)->get(route('unit.staff', ['unit' => $unit->id]));

        $response->assertInertia(fn ($page) => $page
            ->where('staff.meta.per_page', 15)
            ->where('staff.meta.total', 20)
            ->has('staff.data', 15)
        );
    }

    public function test_staff_endpoint_filters_by_gender(): void
    {
        $unit = Unit::factory()->create();
        $this->makeActiveStaff($unit, 'M');
        $this->makeActiveStaff($unit, 'M');
        $this->makeActiveStaff($unit, 'F');

        $response = $this->actingAs($this->user)
            ->get(route('unit.staff', ['unit' => $unit->id, 'gender' => 'F']));

        $response->assertInertia(fn ($page) => $page
            ->where('staff.meta.total', 1)
        );
    }

    public function test_staff_endpoint_filters_by_rank(): void
    {
        $unit = Unit::factory()->create();
        $rank = Job::factory()->create();
        $other = Job::factory()->create();

        $this->makeActiveStaff($unit, null, $rank);
        $this->makeActiveStaff($unit, null, $other);

        $response = $this->actingAs($this->user)
            ->get(route('unit.staff', ['unit' => $unit->id, 'rank_id' => $rank->id]));

        $response->assertInertia(fn ($page) => $page
            ->where('staff.meta.total', 1)
        );
    }

    public function test_filter_options_include_ranks_from_descendants(): void
    {
        $root = Unit::factory()->create(['unit_id' => null]);
        $child = Unit::factory()->create(['unit_id' => $root->id, 'institution_id' => $root->institution_id]);
        $rank = Job::factory()->create(['name' => 'Director']);

        $this->makeActiveStaff($child, null, $rank);

        $response = $this->actingAs($this->user)->get(route('unit.staff', ['unit' => $root->id]));

        $response->assertInertia(fn ($page) => $page
            ->has('filter_options.ranks', 1)
            ->where('filter_options.ranks.0.label', 'Director')
        );
    }

    public function test_unauthorized_user_is_redirected(): void
    {
        $unit = Unit::factory()->create();
        $stranger = User::factory()->create();

        $response = $this->actingAs($stranger)->get(route('unit.staff', ['unit' => $unit->id]));

        $response->assertStatus(403);
    }
}
```

- [ ] **Step 2: Run the failing tests**

Run: `php artisan test tests/Feature/Unit/StaffDirectoryTest.php`
Expected: FAIL — `Route [unit.staff] not defined`.

- [ ] **Step 3: Add the route**

Open `routes/web.php`. Inside the existing `UnitController` group (near line 218), add below the `show` route:

```php
Route::get('/unit/{unit}/staff', 'staff')->middleware('can:view unit')->name('unit.staff');
```

- [ ] **Step 4: Add the staff loader helper + action to UnitController**

Open `app/Http/Controllers/UnitController.php`. Add imports at the top of the file if not already present:

```php
use App\Http\Requests\StaffDirectoryFilterRequest;
use App\Services\UnitHierarchy;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
```

Add the new action method (e.g., just after `show()`):

```php
public function staff(StaffDirectoryFilterRequest $request, Unit $unit, UnitHierarchy $hierarchy)
{
    if ($request->user()->cannot('view', $unit)) {
        abort(403);
    }

    $unitIds = $hierarchy->descendantIds($unit);

    return Inertia::render('Unit/Show', [
        'staff' => $this->loadStaffPage($unitIds, $request->validated()),
        'filter_options' => $this->buildFilterOptions($unit, $unitIds),
        'filters' => $request->validated(),
    ]);
}

/**
 * Build a paginated, filtered listing of active staff across the given unit ids.
 *
 * @param  int[]  $unitIds
 * @param  array<string, mixed>  $filters
 */
private function loadStaffPage(array $unitIds, array $filters): LengthAwarePaginator
{
    $query = InstitutionPerson::query()
        ->active()
        ->with(['person', 'ranks.category', 'units'])
        ->whereHas('units', function ($q) use ($unitIds) {
            $q->whereIn('units.id', $unitIds);
            $q->whereNull('staff_unit.end_date');
        });

    if (! empty($filters['search'])) {
        $query->search($filters['search']);
    }
    if (! empty($filters['job_category_id'])) {
        $query->whereHas('ranks', fn ($q) => $q->where('job_category_id', $filters['job_category_id']));
    }
    if (! empty($filters['rank_id'])) {
        $query->whereHas('ranks', fn ($q) => $q->where('jobs.id', $filters['rank_id']));
    }
    if (! empty($filters['sub_unit_id'])) {
        $query->whereHas('units', fn ($q) => $q->where('units.id', $filters['sub_unit_id']));
    }
    if (! empty($filters['gender'])) {
        $filters['gender'] === 'M' ? $query->maleStaff() : $query->femaleStaff();
    }
    if (! empty($filters['hire_date_from'])) {
        $query->whereDate('hire_date', '>=', $filters['hire_date_from']);
    }
    if (! empty($filters['hire_date_to'])) {
        $query->whereDate('hire_date', '<=', $filters['hire_date_to']);
    }
    if (! empty($filters['age_from'])) {
        $cutoff = now()->subYears((int) $filters['age_from'])->endOfDay();
        $query->whereHas('person', fn ($q) => $q->where('date_of_birth', '<=', $cutoff));
    }
    if (! empty($filters['age_to'])) {
        $cutoff = now()->subYears((int) $filters['age_to'] + 1)->startOfDay();
        $query->whereHas('person', fn ($q) => $q->where('date_of_birth', '>=', $cutoff));
    }

    return $query
        ->orderByRaw('(select coalesce(min(jc.level), 99) from jobs inner join job_categories jc on jc.id = jobs.job_category_id inner join job_staff on job_staff.job_id = jobs.id where job_staff.staff_id = institution_people.id and job_staff.end_date is null)')
        ->paginate(15)
        ->withQueryString()
        ->through(fn (InstitutionPerson $staff) => $this->shapeStaffRow($staff));
}

/**
 * Shape one staff row for the directory.
 *
 * @return array<string, mixed>
 */
private function shapeStaffRow(InstitutionPerson $staff): array
{
    $rank = $staff->ranks->first();
    $unit = $staff->units->first();

    return [
        'id' => $staff->person->id,
        'name' => $staff->person->full_name,
        'gender' => $staff->person->gender?->value,
        'dob' => $staff->person->date_of_birth?->format('d M Y'),
        'dob_raw' => $staff->person->date_of_birth?->format('Y-m-d'),
        'initials' => $staff->person->initials,
        'hire_date' => $staff->hire_date?->format('d M Y'),
        'hire_date_raw' => $staff->hire_date?->format('Y-m-d'),
        'staff_number' => $staff->staff_number,
        'file_number' => $staff->file_number,
        'image' => $staff->person->image ? '/storage/' . $staff->person->image : null,
        'rank' => $rank ? [
            'id' => $rank->id,
            'name' => $rank->name,
            'start_date' => $rank->pivot->start_date?->format('d M Y'),
            'remarks' => $rank->pivot->remarks,
            'cat' => $rank->category,
            'category_id' => $rank->job_category_id,
        ] : null,
        'unit' => $unit ? [
            'id' => $unit->id,
            'name' => $unit->name,
            'start_date' => $unit->pivot->start_date?->format('d M Y'),
            'duration' => $unit->pivot->start_date?->diffForHumans(),
        ] : null,
    ];
}

/**
 * Build dropdown option lists derived from all staff in the descendant set.
 *
 * @param  int[]  $unitIds
 * @return array<string, mixed>
 */
private function buildFilterOptions(Unit $unit, array $unitIds): array
{
    $categories = JobCategory::query()
        ->whereHas('jobs.activeStaff', fn ($q) => $q->whereHas('units', fn ($u) => $u->whereIn('units.id', $unitIds)))
        ->orderBy('name')
        ->get(['id', 'name'])
        ->map(fn ($c) => ['value' => $c->id, 'label' => $c->name])
        ->values()
        ->all();

    $ranks = Job::query()
        ->whereHas('activeStaff', fn ($q) => $q->whereHas('units', fn ($u) => $u->whereIn('units.id', $unitIds)))
        ->orderBy('name')
        ->get(['id', 'name', 'job_category_id'])
        ->map(fn ($r) => ['value' => $r->id, 'label' => $r->name, 'category_id' => $r->job_category_id])
        ->values()
        ->all();

    $subUnits = Unit::query()
        ->where('unit_id', $unit->id)
        ->whereNull('end_date')
        ->orderBy('name')
        ->get(['id', 'name'])
        ->map(fn ($u) => ['value' => $u->id, 'label' => $u->name])
        ->values()
        ->all();

    return [
        'job_categories' => $categories,
        'ranks' => $ranks,
        'sub_units' => $subUnits,
        'genders' => [
            ['value' => 'M', 'label' => 'Male'],
            ['value' => 'F', 'label' => 'Female'],
        ],
    ];
}
```

- [ ] **Step 5: Run the feature tests**

Run: `php artisan test tests/Feature/Unit/StaffDirectoryTest.php`
Expected: 6 passed.

If a test fails with "permission `view unit` does not exist", update the `setUp()` in `tests/Feature/Unit/StaffDirectoryTest.php` to seed the permission before assigning it:

```php
protected function setUp(): void
{
    parent::setUp();
    $this->seed(\Database\Seeders\UnitsPermissionSeeder::class);
    $this->user = User::factory()->create();
    $this->user->givePermissionTo('view unit');
}
```

- [ ] **Step 6: Commit**

```bash
git add app/Http/Controllers/UnitController.php app/Models/Job.php routes/web.php tests/Feature/Unit/StaffDirectoryTest.php
git commit -m "feat: add paginated staff directory loader for unit show"
```

---

## Task 4: Rewrite UnitController@show to use recursive aggregates (TDD)

**Files:**
- Modify: `app/Http/Controllers/UnitController.php:183-460`
- Create: `tests/Feature/Unit/ShowTest.php`

The rewrite replaces the 2-level aggregates with `whereIn`-based queries against the full descendant set, wires the initial staff page through `loadStaffPage`, and drops the in-payload `staff` array.

- [ ] **Step 1: Create the failing feature test**

Create `tests/Feature/Unit/ShowTest.php`:

```php
<?php

namespace Tests\Feature\Unit;

use App\Models\InstitutionPerson;
use App\Models\Job;
use App\Models\Person;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShowTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\UnitsPermissionSeeder::class);
        $this->user = User::factory()->create();
        $this->user->givePermissionTo('view unit');
    }

    private function makeActiveStaff(Unit $unit, string $gender, ?Job $rank = null): InstitutionPerson
    {
        $person = Person::factory()->create(['gender' => $gender]);
        $staff = InstitutionPerson::factory()->create([
            'institution_id' => $unit->institution_id,
            'person_id' => $person->id,
        ]);
        $staff->statuses()->create([
            'status' => 'A',
            'start_date' => now()->subYear(),
            'institution_id' => $unit->institution_id,
        ]);
        $staff->units()->attach($unit->id, ['start_date' => now()->subYear()]);
        if ($rank) {
            $staff->ranks()->attach($rank->id, ['start_date' => now()->subYear()]);
        }

        return $staff;
    }

    public function test_stats_total_male_female_span_all_descendants(): void
    {
        $root = Unit::factory()->create(['unit_id' => null]);
        $child = Unit::factory()->create(['unit_id' => $root->id, 'institution_id' => $root->institution_id]);
        $grand = Unit::factory()->create(['unit_id' => $child->id, 'institution_id' => $root->institution_id]);

        $this->makeActiveStaff($root, 'M');
        $this->makeActiveStaff($child, 'M');
        $this->makeActiveStaff($child, 'F');
        $this->makeActiveStaff($grand, 'F');
        $this->makeActiveStaff($grand, 'M');

        $response = $this->actingAs($this->user)->get(route('unit.show', ['unit' => $root->id]));

        $response->assertInertia(fn ($page) => $page
            ->where('stats.total', 5)
            ->where('stats.male', 3)
            ->where('stats.female', 2)
            ->where('stats.direct_subs', 1)
            ->where('stats.total_descendants', 2)
        );
    }

    public function test_sub_unit_cards_show_recursive_counts(): void
    {
        $root = Unit::factory()->create(['unit_id' => null]);
        $child = Unit::factory()->create(['unit_id' => $root->id, 'institution_id' => $root->institution_id]);
        $grand = Unit::factory()->create(['unit_id' => $child->id, 'institution_id' => $root->institution_id]);

        $this->makeActiveStaff($child, 'M');
        $this->makeActiveStaff($grand, 'F');
        $this->makeActiveStaff($grand, 'F');

        $response = $this->actingAs($this->user)->get(route('unit.show', ['unit' => $root->id]));

        $response->assertInertia(fn ($page) => $page
            ->has('subs', 1)
            ->where('subs.0.staff_count', 3)
            ->where('subs.0.male_staff', 1)
            ->where('subs.0.female_staff', 2)
        );
    }

    public function test_rank_distribution_aggregates_across_all_descendants(): void
    {
        $root = Unit::factory()->create(['unit_id' => null]);
        $child = Unit::factory()->create(['unit_id' => $root->id, 'institution_id' => $root->institution_id]);
        $grand = Unit::factory()->create(['unit_id' => $child->id, 'institution_id' => $root->institution_id]);
        $rank = Job::factory()->create(['name' => 'Officer']);

        $this->makeActiveStaff($root, 'M', $rank);
        $this->makeActiveStaff($child, 'F', $rank);
        $this->makeActiveStaff($grand, 'M', $rank);

        $response = $this->actingAs($this->user)->get(route('unit.show', ['unit' => $root->id]));

        $response->assertInertia(fn ($page) => $page
            ->has('rank_distribution', 1)
            ->where('rank_distribution.0.name', 'Officer')
            ->where('rank_distribution.0.count', 3)
        );
    }

    public function test_initial_staff_page_uses_paginator_shape(): void
    {
        $unit = Unit::factory()->create();
        for ($i = 0; $i < 18; $i++) {
            $this->makeActiveStaff($unit, 'M');
        }

        $response = $this->actingAs($this->user)->get(route('unit.show', ['unit' => $unit->id]));

        $response->assertInertia(fn ($page) => $page
            ->where('staff.meta.per_page', 15)
            ->where('staff.meta.total', 18)
            ->has('staff.data', 15)
            ->has('filter_options.genders', 2)
        );
    }
}
```

- [ ] **Step 2: Run the failing tests**

Run: `php artisan test tests/Feature/Unit/ShowTest.php`
Expected: FAIL — assertions on `stats.*`, `subs.*`, `staff.meta.*` not found (current payload uses different keys).

- [ ] **Step 3: Replace the `show` method body**

Open `app/Http/Controllers/UnitController.php`. Replace the entire `public function show($unit)` method (lines ~183-460) with:

```php
public function show(StaffDirectoryFilterRequest $request, $unit, UnitHierarchy $hierarchy)
{
    $unit = Unit::query()
        ->with([
            'institution',
            'parent',
            'currentOffice.district.region',
            'subs' => function ($query) {
                $query->whereNull('end_date');
            },
        ])
        ->whereId($unit)
        ->firstOrFail();

    if ($request->user()->cannot('view', $unit)) {
        return redirect()->route('dashboard')->with('error', 'You do not have permission to view this unit');
    }

    $allIds = $hierarchy->descendantIds($unit);
    $childIdMap = $hierarchy->descendantIdsGroupedByChild($unit);

    $stats = $this->buildRootStats($unit, $allIds);
    $subs = $this->buildSubUnitCards($unit, $childIdMap);
    $rankDistribution = $this->buildRankDistribution($allIds);

    return Inertia::render('Unit/Show', [
        'unit' => [
            'id' => $unit->id,
            'name' => $unit->name,
            'type' => $unit->type->label(),
            'institution' => $unit->institution ? [
                'id' => $unit->institution->id,
                'name' => $unit->institution->name,
            ] : null,
            'parent' => $unit->parent ? [
                'id' => $unit->parent->id,
                'name' => $unit->parent->name,
            ] : null,
            'current_office' => $unit->currentOffice->first() ? [
                'id' => $unit->currentOffice->first()->id,
                'name' => $unit->currentOffice->first()->name,
                'type' => $unit->currentOffice->first()->type?->label(),
                'district' => $unit->currentOffice->first()->district ? [
                    'id' => $unit->currentOffice->first()->district->id,
                    'name' => $unit->currentOffice->first()->district->name,
                    'region' => $unit->currentOffice->first()->district->region ? [
                        'id' => $unit->currentOffice->first()->district->region->id,
                        'name' => $unit->currentOffice->first()->district->region->name,
                    ] : null,
                ] : null,
            ] : null,
        ],
        'stats' => $stats,
        'subs' => $subs,
        'rank_distribution' => $rankDistribution,
        'staff' => $this->loadStaffPage($allIds, $request->validated()),
        'filter_options' => $this->buildFilterOptions($unit, $allIds),
        'filters' => $request->validated(),
    ]);
}

/**
 * @param  int[]  $allIds
 * @return array<string, int>
 */
private function buildRootStats(Unit $unit, array $allIds): array
{
    $totals = InstitutionPerson::query()
        ->active()
        ->whereHas('units', fn ($q) => $q->whereIn('units.id', $allIds)->whereNull('staff_unit.end_date'))
        ->leftJoin('people', 'people.id', '=', 'institution_people.person_id')
        ->selectRaw('count(*) as total')
        ->selectRaw("sum(case when people.gender = 'M' then 1 else 0 end) as male")
        ->selectRaw("sum(case when people.gender = 'F' then 1 else 0 end) as female")
        ->first();

    $directSubs = $unit->subs->count();
    $totalDescendants = max(0, count($allIds) - 1); // subtract self

    return [
        'total' => (int) ($totals->total ?? 0),
        'male' => (int) ($totals->male ?? 0),
        'female' => (int) ($totals->female ?? 0),
        'direct_subs' => $directSubs,
        'total_descendants' => $totalDescendants,
    ];
}

/**
 * @param  array<int, int[]>  $childIdMap
 * @return array<int, array<string, mixed>>
 */
private function buildSubUnitCards(Unit $unit, array $childIdMap): array
{
    return $unit->subs->map(function (Unit $sub) use ($childIdMap) {
        $subtreeIds = $childIdMap[$sub->id] ?? [$sub->id];

        $totals = InstitutionPerson::query()
            ->active()
            ->whereHas('units', fn ($q) => $q->whereIn('units.id', $subtreeIds)->whereNull('staff_unit.end_date'))
            ->leftJoin('people', 'people.id', '=', 'institution_people.person_id')
            ->selectRaw('count(*) as total')
            ->selectRaw("sum(case when people.gender = 'M' then 1 else 0 end) as male")
            ->selectRaw("sum(case when people.gender = 'F' then 1 else 0 end) as female")
            ->first();

        return [
            'id' => $sub->id,
            'name' => $sub->name,
            'type' => $sub->type->label(),
            'subs' => max(0, count($subtreeIds) - 1),
            'staff_count' => (int) ($totals->total ?? 0),
            'male_staff' => (int) ($totals->male ?? 0),
            'female_staff' => (int) ($totals->female ?? 0),
        ];
    })->values()->all();
}

/**
 * @param  int[]  $allIds
 * @return array<int, array<string, mixed>>
 */
private function buildRankDistribution(array $allIds): array
{
    return Job::query()
        ->select('jobs.id', 'jobs.name')
        ->selectRaw('COUNT(DISTINCT job_staff.id) as staff_count')
        ->join('job_staff', 'jobs.id', '=', 'job_staff.job_id')
        ->join('job_categories', 'jobs.job_category_id', '=', 'job_categories.id')
        ->join('staff_unit', 'staff_unit.staff_id', '=', 'job_staff.staff_id')
        ->whereIn('staff_unit.unit_id', $allIds)
        ->whereNull('job_staff.end_date')
        ->whereNull('staff_unit.end_date')
        ->groupBy('jobs.id', 'jobs.name', 'job_categories.level')
        ->orderBy('job_categories.level')
        ->get()
        ->map(fn ($job) => [
            'id' => $job->id,
            'name' => $job->name,
            'full_name' => $job->name,
            'count' => (int) $job->staff_count,
        ])
        ->values()
        ->all();
}
```

Note: `loadStaffPage`, `shapeStaffRow`, and `buildFilterOptions` were already added in Task 3; reuse them as-is.

- [ ] **Step 4: Run the feature tests**

Run: `php artisan test tests/Feature/Unit/ShowTest.php tests/Feature/Unit/StaffDirectoryTest.php`
Expected: all passed.

- [ ] **Step 5: Run the full test suite to catch regressions**

Run: `php artisan test`
Expected: no new failures. As of 2026-04-16 no existing test references the old payload keys, so the suite should pass cleanly; if one is added before this task runs, update it to the new keys (`stats.*`, `subs.*`, `staff.data/meta`).

- [ ] **Step 6: Commit**

```bash
git add app/Http/Controllers/UnitController.php tests/Feature/Unit/ShowTest.php
git commit -m "feat: rewrite UnitController@show with recursive aggregates"
```

---

## Task 5: Add pink accent to StatCard

**Files:**
- Modify: `resources/js/Components/StatCard.vue`

- [ ] **Step 1: Extend the accent validator and map**

Open `resources/js/Components/StatCard.vue`. Update the `accent` prop validator on line 11:

```js
accent: {
    type: String,
    default: "slate",
    validator: (v) => ["indigo", "emerald", "amber", "red", "pink", "slate"].includes(v),
},
```

Add a `pink` entry to `accentClasses` (after the `red` block):

```js
pink: {
    border: "border-pink-500 dark:border-pink-400",
    iconBg: "bg-pink-50 dark:bg-pink-900/40",
    iconText: "text-pink-600 dark:text-pink-300",
    spark: "#db2777",
},
```

- [ ] **Step 2: Commit**

```bash
git add resources/js/Components/StatCard.vue
git commit -m "feat: add pink accent variant to StatCard"
```

---

## Task 6: Rewrite UnitStatsSection to use StatCard

**Files:**
- Modify: `resources/js/Pages/Unit/partials/UnitStatsSection.vue`

- [ ] **Step 1: Replace the file contents**

Open `resources/js/Pages/Unit/partials/UnitStatsSection.vue`. Replace the entire file with:

```vue
<script setup>
import { computed } from "vue";
import {
	UsersIcon,
	UserGroupIcon,
	Square3Stack3DIcon,
} from "@heroicons/vue/24/outline";
import StatCard from "@/Components/StatCard.vue";

const props = defineProps({
	stats: {
		type: Object,
		required: true,
	},
});

const cards = computed(() => [
	{
		id: "total-staff",
		label: "Total Staff",
		value: props.stats?.total ?? 0,
		icon: UsersIcon,
		accent: "emerald",
	},
	{
		id: "male-staff",
		label: "Male Staff",
		value: props.stats?.male ?? 0,
		icon: UserGroupIcon,
		accent: "indigo",
	},
	{
		id: "female-staff",
		label: "Female Staff",
		value: props.stats?.female ?? 0,
		icon: UserGroupIcon,
		accent: "pink",
	},
	{
		id: "sub-units",
		label: "Sub-Units",
		value: props.stats?.direct_subs ?? 0,
		icon: Square3Stack3DIcon,
		accent: "slate",
		secondary:
			props.stats?.total_descendants > props.stats?.direct_subs
				? `${props.stats.total_descendants} nested`
				: null,
	},
]);
</script>

<template>
	<section>
		<h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">
			Overview
		</h2>
		<div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
			<StatCard
				v-for="card in cards"
				:key="card.id"
				:label="card.label"
				:value="card.value"
				:icon="card.icon"
				:accent="card.accent"
				:secondary="card.secondary"
			/>
		</div>
	</section>
</template>
```

- [ ] **Step 2: Commit**

```bash
git add resources/js/Pages/Unit/partials/UnitStatsSection.vue
git commit -m "feat: UnitStatsSection uses StatCard with recursive totals"
```

---

## Task 7: Convert StaffDirectorySection to server-driven pagination

**Files:**
- Modify: `resources/js/Pages/Unit/partials/StaffDirectorySection.vue`

- [ ] **Step 1: Replace the file contents**

Open `resources/js/Pages/Unit/partials/StaffDirectorySection.vue`. Replace the entire file with:

```vue
<script setup>
import { ref, computed, reactive, watch } from "vue";
import { router, Link } from "@inertiajs/vue3";
import { Disclosure, DisclosureButton, DisclosurePanel } from "@headlessui/vue";
import {
	ArrowDownTrayIcon,
	UsersIcon,
	ChevronLeftIcon,
	ChevronRightIcon,
	FunnelIcon,
	XMarkIcon,
	ChevronDownIcon,
} from "@heroicons/vue/24/outline";
import { MagnifyingGlassIcon } from "@heroicons/vue/20/solid";
import SearchSelect from "@/Components/Forms/SearchSelect.vue";
import SearchDateInput from "@/Components/Forms/SearchDateInput.vue";
import SearchNumberInput from "@/Components/Forms/SearchNumberInput.vue";

const props = defineProps({
	staff: {
		type: Object,
		required: true,
	},
	filterOptions: {
		type: Object,
		required: true,
	},
	filters: {
		type: Object,
		default: () => ({}),
	},
	unitId: {
		type: Number,
		required: true,
	},
	unitName: {
		type: String,
		default: "",
	},
	canDownload: {
		type: Boolean,
		default: false,
	},
});

const searchQuery = ref(props.filters.search ?? "");
const filterForm = reactive({
	job_category_id: props.filters.job_category_id ?? null,
	rank_id: props.filters.rank_id ?? null,
	sub_unit_id: props.filters.sub_unit_id ?? null,
	gender: props.filters.gender ?? null,
	hire_date_from: props.filters.hire_date_from ?? null,
	hire_date_to: props.filters.hire_date_to ?? null,
	age_from: props.filters.age_from ?? null,
	age_to: props.filters.age_to ?? null,
});

const rows = computed(() => props.staff?.data ?? []);
const meta = computed(() => props.staff?.meta ?? {});

const ranksForCategory = computed(() => {
	const all = props.filterOptions?.ranks ?? [];
	if (!filterForm.job_category_id) return all;
	return all.filter((r) => r.category_id === filterForm.job_category_id);
});

const hasActiveFilters = computed(() =>
	Object.values(filterForm).some((v) => v !== null && v !== ""),
);

function buildParams(page = 1) {
	const params = { page };
	if (searchQuery.value) params.search = searchQuery.value;
	Object.entries(filterForm).forEach(([key, value]) => {
		if (value !== null && value !== "") params[key] = value;
	});
	return params;
}

function reload(page = 1) {
	router.reload({
		only: ["staff", "filter_options", "filters"],
		data: buildParams(page),
		preserveState: true,
		preserveScroll: true,
		replace: true,
	});
}

let searchTimeout = null;
function onSearchInput() {
	clearTimeout(searchTimeout);
	searchTimeout = setTimeout(() => reload(1), 300);
}

watch(
	filterForm,
	() => {
		reload(1);
	},
	{ deep: true },
);

watch(
	() => filterForm.job_category_id,
	() => {
		if (filterForm.rank_id) {
			const stillValid = ranksForCategory.value.some(
				(r) => r.value === filterForm.rank_id,
			);
			if (!stillValid) filterForm.rank_id = null;
		}
	},
);

function resetFilters() {
	Object.keys(filterForm).forEach((key) => {
		filterForm[key] = null;
	});
}

function goToPage(page) {
	if (typeof page !== "number") return;
	if (page < 1 || page > (meta.value.last_page ?? 1)) return;
	reload(page);
}

function prevPage() {
	goToPage((meta.value.current_page ?? 1) - 1);
}

function nextPage() {
	goToPage((meta.value.current_page ?? 1) + 1);
}

const visiblePages = computed(() => {
	const pages = [];
	const total = meta.value.last_page ?? 1;
	const current = meta.value.current_page ?? 1;
	if (total <= 7) {
		for (let i = 1; i <= total; i++) pages.push(i);
	} else {
		pages.push(1);
		if (current > 3) pages.push("...");
		const start = Math.max(2, current - 1);
		const end = Math.min(total - 1, current + 1);
		for (let i = start; i <= end; i++) pages.push(i);
		if (current < total - 2) pages.push("...");
		pages.push(total);
	}
	return pages;
});

function getInitialsColor(name) {
	const colors = [
		"bg-red-500",
		"bg-orange-500",
		"bg-amber-500",
		"bg-yellow-500",
		"bg-lime-500",
		"bg-green-500",
		"bg-emerald-500",
		"bg-teal-500",
		"bg-cyan-500",
		"bg-sky-500",
		"bg-blue-500",
		"bg-indigo-500",
		"bg-violet-500",
		"bg-purple-500",
		"bg-fuchsia-500",
		"bg-pink-500",
		"bg-rose-500",
	];
	const charCode = (name?.charCodeAt(0) || 0) + (name?.charCodeAt(1) || 0);
	return colors[charCode % colors.length];
}

function exportToExcel() {
	const params = new URLSearchParams();
	Object.entries(buildParams(1)).forEach(([k, v]) => {
		if (k !== "page") params.append(k, v);
	});
	const baseUrl = route("export.unit.staff", { unit: props.unitId });
	const qs = params.toString();
	window.location = qs ? `${baseUrl}?${qs}` : baseUrl;
}
</script>

<template>
	<section>
		<div
			class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-4"
		>
			<h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
				Staff Directory
				<span class="text-sm font-normal text-gray-500 dark:text-gray-400">
					({{ meta.total ?? 0 }})
				</span>
			</h2>

			<div class="flex items-center gap-3">
				<div class="relative">
					<MagnifyingGlassIcon
						class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400"
					/>
					<input
						v-model="searchQuery"
						type="search"
						placeholder="Search staff..."
						class="block w-full sm:w-64 rounded-md border-0 py-2 pl-10 pr-3 text-gray-900 dark:text-gray-100 bg-white dark:bg-gray-800 ring-1 ring-inset ring-gray-300 dark:ring-gray-600 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-green-600 sm:text-sm sm:leading-6"
						@input="onSearchInput"
					/>
				</div>

				<a
					v-if="canDownload"
					class="inline-flex items-center gap-x-1.5 rounded-md bg-white dark:bg-gray-800 px-3 py-2 text-sm font-semibold text-gray-900 dark:text-gray-100 shadow-sm ring-1 ring-inset ring-gray-300 dark:ring-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer"
					@click.prevent="exportToExcel()"
				>
					<ArrowDownTrayIcon class="-ml-0.5 h-5 w-5 text-gray-400" />
					Export Staff List
				</a>
			</div>
		</div>

		<Disclosure v-slot="{ open }" as="div" class="mb-4">
			<div class="bg-white dark:bg-gray-800 shadow sm:rounded-lg">
				<DisclosureButton
					class="flex w-full items-center justify-between px-4 py-3 text-left hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors rounded-lg"
				>
					<div class="flex items-center gap-2">
						<FunnelIcon class="h-5 w-5 text-gray-500 dark:text-gray-400" />
						<span class="text-sm font-medium text-gray-900 dark:text-gray-100">
							Advanced Filters
						</span>
						<span
							v-if="hasActiveFilters"
							class="inline-flex items-center rounded-full bg-green-100 dark:bg-green-900 px-2.5 py-0.5 text-xs font-medium text-green-800 dark:text-green-200"
						>
							Active
						</span>
					</div>
					<ChevronDownIcon
						:class="[
							open ? 'rotate-180 transform' : '',
							'h-5 w-5 text-gray-500 dark:text-gray-400 transition-transform',
						]"
					/>
				</DisclosureButton>

				<DisclosurePanel class="px-4 pb-4 pt-2">
					<div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
						<SearchSelect
							v-model="filterForm.job_category_id"
							label="Job Category"
							placeholder="All Categories"
							:options="filterOptions.job_categories"
						/>
						<SearchSelect
							v-model="filterForm.rank_id"
							label="Rank / Job"
							placeholder="All Ranks"
							:options="ranksForCategory"
							searchable
						/>
						<SearchSelect
							v-if="filterOptions.sub_units.length > 0"
							v-model="filterForm.sub_unit_id"
							label="Sub-Unit"
							placeholder="All Sub-Units"
							:options="filterOptions.sub_units"
							searchable
						/>
						<SearchSelect
							v-model="filterForm.gender"
							label="Gender"
							placeholder="All Genders"
							:options="filterOptions.genders"
						/>
						<SearchDateInput
							v-model="filterForm.hire_date_from"
							label="Hired From"
							placeholder="Start Date"
						/>
						<SearchDateInput
							v-model="filterForm.hire_date_to"
							label="Hired To"
							placeholder="End Date"
							:min="filterForm.hire_date_from"
						/>
						<SearchNumberInput
							v-model="filterForm.age_from"
							label="Age From"
							placeholder="Min Age"
							:min="18"
							:max="100"
						/>
						<SearchNumberInput
							v-model="filterForm.age_to"
							label="Age To"
							placeholder="Max Age"
							:min="filterForm.age_from || 18"
							:max="100"
						/>
					</div>

					<div class="mt-4 flex flex-wrap gap-3">
						<button
							v-if="hasActiveFilters"
							type="button"
							class="inline-flex items-center gap-2 rounded-md bg-white dark:bg-gray-700 px-4 py-2 text-sm font-semibold text-gray-900 dark:text-gray-100 shadow-sm ring-1 ring-inset ring-gray-300 dark:ring-gray-600 hover:bg-gray-50 dark:hover:bg-gray-600"
							@click="resetFilters"
						>
							<XMarkIcon class="h-4 w-4" />
							Clear All Filters
						</button>
					</div>
				</DisclosurePanel>
			</div>
		</Disclosure>

		<div
			v-if="rows.length === 0"
			class="text-center py-12 bg-white dark:bg-gray-800 rounded-lg ring-1 ring-gray-900/5 dark:ring-gray-700"
		>
			<UsersIcon class="mx-auto h-12 w-12 text-gray-400 dark:text-gray-500" />
			<p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
				{{
					searchQuery || hasActiveFilters
						? "No staff found matching your search criteria."
						: "No staff assigned to this unit."
				}}
			</p>
			<button
				v-if="hasActiveFilters"
				type="button"
				class="mt-3 text-sm font-medium text-green-600 dark:text-green-400 hover:text-green-500"
				@click="resetFilters"
			>
				Clear filters
			</button>
		</div>

		<div
			v-else
			class="bg-white dark:bg-gray-800 rounded-lg shadow-sm ring-1 ring-gray-900/5 dark:ring-gray-700 overflow-hidden"
		>
			<ul role="list" class="divide-y divide-gray-100 dark:divide-gray-700">
				<li
					v-for="member in rows"
					:key="member.id"
					class="relative flex items-center gap-x-4 px-4 py-4 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors"
				>
					<div class="flex-shrink-0">
						<img
							v-if="member.image"
							:src="member.image"
							:alt="member.name"
							class="h-12 w-12 rounded-full object-cover ring-2 ring-white dark:ring-gray-700"
						/>
						<div
							v-else
							:class="[
								'h-12 w-12 rounded-full flex items-center justify-center text-white font-semibold text-sm',
								getInitialsColor(member.name),
							]"
						>
							{{ member.initials }}
						</div>
					</div>
					<div class="min-w-0 flex-1">
						<div class="flex items-center gap-x-3">
							<Link
								:href="route('staff.show', { staff: member.id })"
								class="text-sm font-semibold text-gray-900 dark:text-gray-100 hover:text-green-600 dark:hover:text-green-400"
							>
								{{ member.name }}
							</Link>
							<span
								v-if="member.rank?.name"
								class="inline-flex items-center rounded-md bg-green-50 dark:bg-green-900/30 px-2 py-1 text-xs font-medium text-green-700 dark:text-green-400 ring-1 ring-inset ring-green-600/20 dark:ring-green-500/30"
							>
								{{ member.rank.name }}
							</span>
						</div>
						<div
							class="mt-1 flex flex-wrap items-center gap-x-4 text-xs text-gray-500 dark:text-gray-400"
						>
							<span v-if="member.staff_number">
								Staff #: {{ member.staff_number }}
							</span>
							<span v-if="member.file_number">
								File #: {{ member.file_number }}
							</span>
						</div>
					</div>
					<div
						class="hidden sm:flex flex-col items-end gap-1 text-xs text-gray-500 dark:text-gray-400"
					>
						<span v-if="member.hire_date"> Hired: {{ member.hire_date }} </span>
						<span v-if="member.rank?.start_date">
							Rank since: {{ member.rank.start_date }}
						</span>
					</div>
				</li>
			</ul>

			<footer
				v-if="(meta.last_page ?? 1) > 1"
				class="bg-white dark:bg-gray-800 px-4 py-3 flex items-center justify-between border-t border-gray-200 dark:border-gray-700 sm:px-6"
			>
				<div class="flex-1 flex justify-between sm:hidden">
					<button
						type="button"
						:disabled="meta.current_page === 1"
						class="relative inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 text-sm font-medium rounded-md text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 disabled:opacity-50 disabled:cursor-not-allowed"
						@click="prevPage"
					>
						Previous
					</button>
					<button
						type="button"
						:disabled="meta.current_page === meta.last_page"
						class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 text-sm font-medium rounded-md text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 disabled:opacity-50 disabled:cursor-not-allowed"
						@click="nextPage"
					>
						Next
					</button>
				</div>
				<div
					class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between"
				>
					<p class="text-sm text-gray-700 dark:text-gray-300">
						Showing
						<span class="font-medium">{{ meta.from ?? 0 }}</span>
						to
						<span class="font-medium">{{ meta.to ?? 0 }}</span>
						of
						<span class="font-medium">{{ meta.total ?? 0 }}</span>
						results
					</p>
					<nav
						class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px"
						aria-label="Pagination"
					>
						<button
							type="button"
							:disabled="meta.current_page === 1"
							class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-sm font-medium text-gray-500 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600 disabled:opacity-50 disabled:cursor-not-allowed"
							@click="prevPage"
						>
							<span class="sr-only">Previous</span>
							<ChevronLeftIcon class="h-5 w-5" aria-hidden="true" />
						</button>
						<template v-for="(page, index) in visiblePages" :key="index">
							<span
								v-if="page === '...'"
								class="relative inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-sm font-medium text-gray-500 dark:text-gray-400"
							>
								...
							</span>
							<button
								v-else
								type="button"
								class="relative inline-flex items-center px-4 py-2 border text-sm font-medium cursor-pointer"
								:class="
									page === meta.current_page
										? 'bg-green-100 dark:bg-green-900/30 border-green-500 dark:border-green-600 text-green-600 dark:text-green-400 z-10'
										: 'bg-white dark:bg-gray-700 border-gray-300 dark:border-gray-600 text-gray-500 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600'
								"
								@click="goToPage(page)"
							>
								{{ page }}
							</button>
						</template>
						<button
							type="button"
							:disabled="meta.current_page === meta.last_page"
							class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-sm font-medium text-gray-500 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600 disabled:opacity-50 disabled:cursor-not-allowed"
							@click="nextPage"
						>
							<span class="sr-only">Next</span>
							<ChevronRightIcon class="h-5 w-5" aria-hidden="true" />
						</button>
					</nav>
				</div>
			</footer>
		</div>
	</section>
</template>
```

- [ ] **Step 2: Commit**

```bash
git add resources/js/Pages/Unit/partials/StaffDirectorySection.vue
git commit -m "feat: StaffDirectorySection drives pagination and filters server-side"
```

---

## Task 8: Wire Show.vue to the new props

**Files:**
- Modify: `resources/js/Pages/Unit/Show.vue`

- [ ] **Step 1: Replace the prop list and section wiring**

Open `resources/js/Pages/Unit/Show.vue`. Change the `defineProps` call (lines 25-29) to:

```js
const props = defineProps({
	unit: Object,
	stats: Object,
	subs: Array,
	staff: Object,
	rank_distribution: Array,
	filter_options: Object,
	filters: Object,
});
```

Remove the `handleSearch` helper (lines 67-74) since the directory component owns its state now.

Update the `UnitStatsSection` usage (line 94) to:

```vue
<UnitStatsSection :stats="props.stats" />
```

Update the `SubUnitsCardGrid` usage (lines 105-110) to read from `props.subs`:

```vue
<SubUnitsCardGrid
    v-if="props.subs?.length > 0"
    :subs="props.subs"
    :parent-name="props.unit.name"
    :can-download="permissions?.includes('download active staff data')"
/>
```

Update the `StaffDirectorySection` usage (lines 119-126) to:

```vue
<StaffDirectorySection
    :staff="props.staff"
    :filter-options="props.filter_options"
    :filters="props.filters"
    :unit-id="props.unit?.id"
    :unit-name="props.unit?.name"
    :can-download="permissions?.includes('download active staff data')"
/>
```

- [ ] **Step 2: Build and run the dev server**

Run: `npm run build`
Expected: build succeeds with no Vue compilation errors.

- [ ] **Step 3: Commit**

```bash
git add resources/js/Pages/Unit/Show.vue
git commit -m "feat: wire Unit/Show.vue to new stats, subs, and paginated staff props"
```

---

## Task 9: Verify everything end-to-end

**Files:** None — verification only.

- [ ] **Step 1: Run formatters and linters**

Run: `./vendor/bin/pint --dirty`
Expected: no errors; any formatting changes are acceptable to commit.

Run: `npm run lint`
Expected: no new errors.

If Pint made changes, commit them:

```bash
git add -A
git commit -m "style: pint formatting"
```

- [ ] **Step 2: Run the full test suite**

Run: `php artisan test`
Expected: all tests pass (including the new `UnitHierarchyTest`, `ShowTest`, and `StaffDirectoryTest`).

- [ ] **Step 3: Manual smoke test**

Start the servers:

```bash
php artisan serve &
npm run dev &
```

Then in a browser, as an authorized user:
1. Navigate to a unit with 3+ nesting levels and known staff counts.
2. Confirm the four Overview cards render with the new `StatCard` look and values match the sum of the full subtree (including grandchildren).
3. Expand the Sub-Units grid and confirm each card's Staff / Male / Female numbers match the subtree under that sub-unit.
4. Confirm the Rank Distribution counts match the full subtree.
5. In the Staff Directory:
   - Confirm the total count matches `stats.total`.
   - Change pages — URL updates, server round-trip happens, rows change.
   - Apply each filter (category, rank, sub-unit, gender, hire date range, age range) and confirm result counts change server-side.
   - Clear filters — full list returns.
   - Export — confirm the export URL includes current filter parameters.

- [ ] **Step 4: Final commit (if any stray changes)**

```bash
git status
```

If clean, the branch is ready to push.

```bash
git push -u origin fix/unit-show-recursive-stats-and-pagination
```
