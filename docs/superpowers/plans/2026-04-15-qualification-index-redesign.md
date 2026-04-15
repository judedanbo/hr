# Qualification Index Redesign Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Replace the bare `/qualification` table with a modern, person-first card grid (with a retained table view), plus server-side search, status filter, sort, and stats strip — matching the app's green-accented, dark-mode-aware design language.

**Architecture:** Extend `QualificationController@index` to apply `search`/`status`/`sort` query params and return a `stats` prop computed against the user's visible scope. Rewrite `Pages/Qualification/Index.vue` as a thin orchestrator that binds URL-synced filter state into five new partials (`PageHeader`, `StatsStrip`, `FilterBar`, `QualificationCard`, `QualificationGrid`) and toggles between grid and the existing `QualificationList` table. No model, migration, route, or action-flow changes.

**Tech Stack:** Laravel 11 / PHP 8.4, PHPUnit 11, Inertia v1, Vue 3.5 `<script setup>`, Tailwind 3, HeadlessUI, `@vueuse/core`, `@heroicons/vue`.

**Spec reference:** `docs/superpowers/specs/2026-04-15-qualification-index-redesign-design.md`

**Status note — Rejected pill:** The spec names Pending and Approved pills. `QualificationStatusEnum` also has `Rejected`. The UI includes a fourth `Rejected` pill so rejected rows are filterable (otherwise they'd only appear under "All"). This is the only deviation from the spec's UI section and is flagged here explicitly.

---

## File Structure

### Backend
- Modify: `app/Http/Controllers/QualificationController.php` — method `index` only; rewrite the query to apply filters + return `stats` prop.
- Create: `tests/Feature/QualificationIndexTest.php` — feature test covering prop shape, search, status filter, sort, stats scope, view passthrough.

### Frontend (new partials)
- Create: `resources/js/Pages/Qualification/partials/PageHeader.vue` — title + total + debounced search input + grid/table view toggle.
- Create: `resources/js/Pages/Qualification/partials/StatsStrip.vue` — four stat tiles.
- Create: `resources/js/Pages/Qualification/partials/FilterBar.vue` — status pills (All/Pending/Approved/Rejected) + sort Listbox.
- Create: `resources/js/Pages/Qualification/partials/QualificationCard.vue` — single card component.
- Create: `resources/js/Pages/Qualification/partials/QualificationGrid.vue` — grid wrapper + empty state.

### Frontend (modified)
- Modify: `resources/js/Pages/Qualification/Index.vue` — rewrite as orchestrator.
- Modify: `resources/js/Pages/Qualification/QualificationList.vue` — minor visual polish (header hover states, row hover) only; no column / behavior changes.

---

## Task 1: Backend — index controller accepts filters + returns stats

**Files:**
- Modify: `app/Http/Controllers/QualificationController.php:23-65`
- Test: `tests/Feature/QualificationIndexTest.php`

### Task 1.1 — Write failing test for expanded prop shape

- [ ] **Step 1: Create the test file**

Create `tests/Feature/QualificationIndexTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Enums\QualificationStatusEnum;
use App\Models\Institution;
use App\Models\InstitutionPerson;
use App\Models\Person;
use App\Models\Qualification;
use App\Models\User;
use Inertia\Testing\AssertableInertia;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class QualificationIndexTest extends TestCase
{
    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Permission::firstOrCreate(['name' => 'view staff qualification']);
        Permission::firstOrCreate(['name' => 'approve staff qualification']);

        $role = Role::firstOrCreate(['name' => 'qualification-admin']);
        $role->givePermissionTo(['view staff qualification', 'approve staff qualification']);

        $this->admin = User::factory()->create();
        $this->admin->assignRole($role);
    }

    protected function seedQualification(array $attrs = []): Qualification
    {
        // The index query filters to persons that `whereHas('institution')`.
        // Create a Person, attach an Institution, and ensure the join row exists.
        //
        // IF `InstitutionPerson::factory()` does not exist in this codebase,
        // replace the `InstitutionPerson::factory()->create([...])` call below
        // with an `InstitutionPerson::create([...])` passing whatever required
        // columns (job_id, unit_id, start_date, etc.) the table needs.
        // Check `database/factories/` and `database/migrations/` for the shape.
        $person = Person::factory()->create();
        $institution = Institution::factory()->create();
        InstitutionPerson::factory()->create([
            'person_id' => $person->id,
            'institution_id' => $institution->id,
        ]);

        return Qualification::factory()->create(array_merge(
            ['person_id' => $person->id],
            $attrs,
        ));
    }

    public function test_index_returns_expected_prop_keys(): void
    {
        $this->seedQualification();

        $this->actingAs($this->admin)
            ->get(route('qualification.index'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Qualification/Index')
                ->has('qualifications.data')
                ->has('filters', fn ($f) => $f
                    ->hasAll(['search', 'status', 'sort', 'view'])
                )
                ->has('stats', fn ($s) => $s
                    ->hasAll(['total', 'pending', 'approved', 'with_documents'])
                )
                ->has('can.approve')
            );
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=test_index_returns_expected_prop_keys`
Expected: FAIL — `filters` is missing `status`/`sort`/`view`, and `stats` prop does not exist.

- [ ] **Step 3: Rewrite `QualificationController@index`**

Replace the `index` method in `app/Http/Controllers/QualificationController.php` with:

```php
public function index(Request $request)
{
    $user = auth()->user();

    $search = $request->string('search')->toString() ?: null;
    $status = $request->string('status')->toString() ?: null;
    $sort = $request->string('sort')->toString() ?: 'newest';
    $view = $request->string('view')->toString() ?: 'grid';

    $baseQuery = Qualification::query()
        ->visibleTo($user)
        ->whereHas('person', fn ($q) => $q->whereHas('institution'));

    $stats = [
        'total' => (clone $baseQuery)->count(),
        'pending' => (clone $baseQuery)->where('status', QualificationStatusEnum::Pending)->count(),
        'approved' => (clone $baseQuery)->where('status', QualificationStatusEnum::Approved)->count(),
        'with_documents' => (clone $baseQuery)->has('documents')->count(),
    ];

    $query = (clone $baseQuery)->with(['person.institution', 'documents']);

    if ($search !== null) {
        $query->where(function ($q) use ($search) {
            $like = '%'.$search.'%';
            $q->where('institution', 'like', $like)
                ->orWhere('course', 'like', $like)
                ->orWhere('qualification', 'like', $like)
                ->orWhere('qualification_number', 'like', $like)
                ->orWhereHas('person', function ($pq) use ($like) {
                    $pq->where('full_name', 'like', $like)
                        ->orWhereHas('institution.staff', fn ($sq) => $sq->where('staff_number', 'like', $like));
                });
        });
    }

    if ($status !== null && QualificationStatusEnum::tryFrom($status)) {
        $query->where('status', QualificationStatusEnum::from($status));
    }

    match ($sort) {
        'oldest' => $query->orderBy('created_at', 'asc'),
        'year_desc' => $query->orderBy('year', 'desc'),
        'year_asc' => $query->orderBy('year', 'asc'),
        'institution' => $query->orderBy('institution', 'asc'),
        default => $query->orderBy('created_at', 'desc'),
    };

    return Inertia::render('Qualification/Index', [
        'qualifications' => $query
            ->paginate()
            ->withQueryString()
            ->through(fn ($q) => $this->transformQualification($q)),
        'filters' => [
            'search' => $search,
            'status' => $status,
            'sort' => $sort,
            'view' => $view,
        ],
        'stats' => $stats,
        'can' => [
            'approve' => $user->can('approve staff qualification'),
        ],
    ]);
}

protected function transformQualification(Qualification $qualification): array
{
    return [
        'id' => $qualification->id,
        'person' => $qualification->person->full_name,
        'staff_number' => optional(
            optional($qualification->person->institution->first())->staff
        )->staff_number,
        'course' => $qualification->course,
        'institution' => $qualification->institution,
        'qualification' => $qualification->qualification,
        'qualification_number' => $qualification->qualification_number,
        'level' => $qualification->level
            ? \App\Enums\QualificationLevelEnum::tryFrom($qualification->level)?->label() ?? $qualification->level
            : null,
        'pk' => $qualification->pk,
        'year' => $qualification->year,
        'status' => $qualification->status?->label(),
        'status_value' => $qualification->status?->value,
        'status_color' => $qualification->status?->color(),
        'created_at' => $qualification->created_at,
        'documents' => $qualification->documents->map(fn ($doc) => [
            'id' => $doc->id,
            'document_title' => $doc->document_title,
            'file_name' => $doc->file_name,
            'file_type' => $doc->file_type,
        ]),
    ];
}
```

Also add the `Request` import if not present:

```php
use Illuminate\Http\Request;
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test --filter=test_index_returns_expected_prop_keys`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add app/Http/Controllers/QualificationController.php tests/Feature/QualificationIndexTest.php
git commit -m "feat(qualification): accept search/status/sort/view filters and return stats"
```

### Task 1.2 — Stats reflect visible scope, not filtered view

- [ ] **Step 1: Add test**

Append to `tests/Feature/QualificationIndexTest.php`:

```php
public function test_stats_reflect_visible_scope_not_filtered_view(): void
{
    $this->seedQualification(['status' => QualificationStatusEnum::Pending->value]);
    $this->seedQualification(['status' => QualificationStatusEnum::Pending->value]);
    $this->seedQualification(['status' => QualificationStatusEnum::Approved->value]);

    $this->actingAs($this->admin)
        ->get(route('qualification.index', ['status' => 'approved']))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('stats.total', 3)
            ->where('stats.pending', 2)
            ->where('stats.approved', 1)
            ->has('qualifications.data', 1)
        );
}
```

- [ ] **Step 2: Run test**

Run: `php artisan test --filter=test_stats_reflect_visible_scope_not_filtered_view`
Expected: PASS (implementation from 1.1 clones the base query before applying `search`/`status`, so stats are unfiltered).

- [ ] **Step 3: Commit**

```bash
git add tests/Feature/QualificationIndexTest.php
git commit -m "test(qualification): stats reflect visible scope, not filtered view"
```

### Task 1.3 — Search matches person name, institution, course, qualification number

- [ ] **Step 1: Add test**

Append to `tests/Feature/QualificationIndexTest.php`:

```php
public function test_search_matches_institution_and_course(): void
{
    $this->seedQualification(['institution' => 'Accra Technical University']);
    $this->seedQualification(['course' => 'Computer Science']);
    $this->seedQualification(['institution' => 'KNUST']);

    $this->actingAs($this->admin)
        ->get(route('qualification.index', ['search' => 'Accra']))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('qualifications.data', 1)
            ->where('qualifications.data.0.institution', 'Accra Technical University')
        );

    $this->actingAs($this->admin)
        ->get(route('qualification.index', ['search' => 'Computer']))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('qualifications.data', 1)
            ->where('qualifications.data.0.course', 'Computer Science')
        );
}

public function test_search_matches_qualification_number(): void
{
    $this->seedQualification(['qualification_number' => 'GHN-2019-00412']);
    $this->seedQualification(['qualification_number' => 'UST-2020-11111']);

    $this->actingAs($this->admin)
        ->get(route('qualification.index', ['search' => 'GHN-2019']))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('qualifications.data', 1)
            ->where('qualifications.data.0.qualification_number', 'GHN-2019-00412')
        );
}
```

- [ ] **Step 2: Run tests**

Run: `php artisan test --filter=QualificationIndexTest`
Expected: All four tests PASS.

- [ ] **Step 3: Commit**

```bash
git add tests/Feature/QualificationIndexTest.php
git commit -m "test(qualification): search matches institution, course, and qualification number"
```

### Task 1.4 — Sort by year and institution

- [ ] **Step 1: Add test**

Append:

```php
public function test_sort_year_desc_orders_by_year(): void
{
    $this->seedQualification(['year' => '2018']);
    $this->seedQualification(['year' => '2024']);
    $this->seedQualification(['year' => '2020']);

    $this->actingAs($this->admin)
        ->get(route('qualification.index', ['sort' => 'year_desc']))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('qualifications.data.0.year', '2024')
            ->where('qualifications.data.1.year', '2020')
            ->where('qualifications.data.2.year', '2018')
        );
}

public function test_sort_institution_orders_alphabetically(): void
{
    $this->seedQualification(['institution' => 'Zed Institute']);
    $this->seedQualification(['institution' => 'Alpha University']);
    $this->seedQualification(['institution' => 'Middle College']);

    $this->actingAs($this->admin)
        ->get(route('qualification.index', ['sort' => 'institution']))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('qualifications.data.0.institution', 'Alpha University')
            ->where('qualifications.data.1.institution', 'Middle College')
            ->where('qualifications.data.2.institution', 'Zed Institute')
        );
}
```

- [ ] **Step 2: Run tests**

Run: `php artisan test --filter=QualificationIndexTest`
Expected: All PASS.

- [ ] **Step 3: Commit**

```bash
git add tests/Feature/QualificationIndexTest.php
git commit -m "test(qualification): sort by year desc and institution alphabetical"
```

### Task 1.5 — View param passthrough and unauthorized access

- [ ] **Step 1: Add test**

Append:

```php
public function test_view_param_is_echoed_back_in_filters(): void
{
    $this->seedQualification();

    $this->actingAs($this->admin)
        ->get(route('qualification.index', ['view' => 'table']))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('filters.view', 'table')
        );
}

public function test_unauthorized_user_is_forbidden(): void
{
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('qualification.index'))
        ->assertForbidden();
}
```

- [ ] **Step 2: Run tests**

Run: `php artisan test --filter=QualificationIndexTest`
Expected: All PASS.

- [ ] **Step 3: Format PHP and commit**

```bash
./vendor/bin/pint --dirty
git add app/Http/Controllers/QualificationController.php tests/Feature/QualificationIndexTest.php
git commit -m "test(qualification): view param passthrough and 403 for unauthorized"
```

---

## Task 2: Frontend — StatsStrip component

**Files:**
- Create: `resources/js/Pages/Qualification/partials/StatsStrip.vue`

- [ ] **Step 1: Create the file**

```vue
<script setup>
import {
    DocumentChartBarIcon,
    ClockIcon,
    CheckCircleIcon,
    PaperClipIcon,
} from "@heroicons/vue/24/outline";
import { computed } from "vue";

const props = defineProps({
    stats: {
        type: Object,
        required: true,
    },
});

const tiles = computed(() => [
    {
        key: "total",
        label: "Total",
        value: props.stats.total,
        icon: DocumentChartBarIcon,
        accent: "bg-gray-400",
        iconColor: "text-gray-500 dark:text-gray-300",
    },
    {
        key: "pending",
        label: "Pending",
        value: props.stats.pending,
        icon: ClockIcon,
        accent: "bg-amber-500",
        iconColor: "text-amber-500 dark:text-amber-400",
    },
    {
        key: "approved",
        label: "Approved",
        value: props.stats.approved,
        icon: CheckCircleIcon,
        accent: "bg-green-600",
        iconColor: "text-green-600 dark:text-green-400",
    },
    {
        key: "with_documents",
        label: "With documents",
        value: props.stats.with_documents,
        icon: PaperClipIcon,
        accent: "bg-indigo-500",
        iconColor: "text-indigo-500 dark:text-indigo-400",
    },
]);

const formatted = (n) => new Intl.NumberFormat().format(n ?? 0);
</script>

<template>
    <div class="grid grid-cols-2 gap-3 md:grid-cols-4">
        <div
            v-for="tile in tiles"
            :key="tile.key"
            class="relative flex items-center gap-3 overflow-hidden rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800/60"
        >
            <span
                class="absolute inset-y-0 left-0 w-1"
                :class="tile.accent"
                aria-hidden="true"
            />
            <component
                :is="tile.icon"
                class="h-6 w-6 shrink-0"
                :class="tile.iconColor"
            />
            <div class="min-w-0">
                <div
                    class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400"
                >
                    {{ tile.label }}
                </div>
                <div
                    class="text-2xl font-semibold tabular-nums text-gray-900 dark:text-gray-50"
                >
                    {{ formatted(tile.value) }}
                </div>
            </div>
        </div>
    </div>
</template>
```

- [ ] **Step 2: Commit**

```bash
git add resources/js/Pages/Qualification/partials/StatsStrip.vue
git commit -m "feat(qualification): add StatsStrip partial"
```

---

## Task 3: Frontend — PageHeader component

**Files:**
- Create: `resources/js/Pages/Qualification/partials/PageHeader.vue`

- [ ] **Step 1: Create the file**

```vue
<script setup>
import { ref, watch } from "vue";
import { useDebounceFn } from "@vueuse/core";
import {
    MagnifyingGlassIcon,
    Squares2X2Icon,
    Bars3Icon,
} from "@heroicons/vue/24/outline";

const props = defineProps({
    total: { type: Number, default: 0 },
    searchValue: { type: String, default: "" },
    view: { type: String, default: "grid" },
});

const emit = defineEmits(["update:search", "update:view"]);

const searchLocal = ref(props.searchValue ?? "");

watch(
    () => props.searchValue,
    (v) => {
        if (v !== searchLocal.value) {
            searchLocal.value = v ?? "";
        }
    },
);

const emitSearch = useDebounceFn((val) => emit("update:search", val), 300);

const onInput = (e) => {
    searchLocal.value = e.target.value;
    emitSearch(searchLocal.value);
};

const setView = (next) => {
    if (next !== props.view) {
        emit("update:view", next);
    }
};
</script>

<template>
    <div
        class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between"
    >
        <div>
            <h1
                class="text-2xl font-semibold tracking-tight text-gray-900 dark:text-gray-50"
            >
                Qualifications
            </h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                {{ total.toLocaleString() }} record{{ total === 1 ? "" : "s" }}
            </p>
        </div>

        <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
            <label class="relative block">
                <span class="sr-only">Search qualifications</span>
                <MagnifyingGlassIcon
                    class="pointer-events-none absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-gray-400"
                />
                <input
                    type="search"
                    :value="searchLocal"
                    placeholder="Search person, institution, course…"
                    class="w-full rounded-lg border-gray-300 bg-white py-2 pl-10 pr-3 text-sm text-gray-900 shadow-sm placeholder:text-gray-400 focus:border-green-600 focus:ring-green-600 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-50 dark:placeholder:text-gray-500 sm:w-72"
                    @input="onInput"
                />
            </label>

            <div
                role="group"
                aria-label="View mode"
                class="inline-flex self-start rounded-lg border border-gray-300 bg-white p-0.5 dark:border-gray-600 dark:bg-gray-800"
            >
                <button
                    type="button"
                    :aria-pressed="view === 'grid'"
                    class="inline-flex items-center gap-1.5 rounded-md px-2.5 py-1.5 text-sm font-medium transition"
                    :class="
                        view === 'grid'
                            ? 'bg-green-600 text-white shadow-sm'
                            : 'text-gray-600 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700'
                    "
                    @click="setView('grid')"
                >
                    <Squares2X2Icon class="h-4 w-4" />
                    Grid
                </button>
                <button
                    type="button"
                    :aria-pressed="view === 'table'"
                    class="inline-flex items-center gap-1.5 rounded-md px-2.5 py-1.5 text-sm font-medium transition"
                    :class="
                        view === 'table'
                            ? 'bg-green-600 text-white shadow-sm'
                            : 'text-gray-600 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700'
                    "
                    @click="setView('table')"
                >
                    <Bars3Icon class="h-4 w-4" />
                    Table
                </button>
            </div>
        </div>
    </div>
</template>
```

- [ ] **Step 2: Commit**

```bash
git add resources/js/Pages/Qualification/partials/PageHeader.vue
git commit -m "feat(qualification): add PageHeader partial with debounced search and view toggle"
```

---

## Task 4: Frontend — FilterBar component

**Files:**
- Create: `resources/js/Pages/Qualification/partials/FilterBar.vue`

- [ ] **Step 1: Create the file**

```vue
<script setup>
import { computed } from "vue";
import {
    Listbox,
    ListboxButton,
    ListboxOption,
    ListboxOptions,
} from "@headlessui/vue";
import { ChevronUpDownIcon, CheckIcon } from "@heroicons/vue/20/solid";

const props = defineProps({
    status: { type: String, default: null },
    sort: { type: String, default: "newest" },
    stats: { type: Object, required: true },
});

const emit = defineEmits(["update:status", "update:sort"]);

const statusPills = computed(() => [
    { key: null, label: "All", count: props.stats.total },
    { key: "pending", label: "Pending", count: props.stats.pending },
    { key: "approved", label: "Approved", count: props.stats.approved },
    {
        key: "rejected",
        label: "Rejected",
        count: Math.max(
            0,
            (props.stats.total ?? 0) -
                (props.stats.pending ?? 0) -
                (props.stats.approved ?? 0),
        ),
    },
]);

const sortOptions = [
    { key: "newest", label: "Newest" },
    { key: "oldest", label: "Oldest" },
    { key: "year_desc", label: "Year (newest first)" },
    { key: "year_asc", label: "Year (oldest first)" },
    { key: "institution", label: "Institution A–Z" },
];

const selectedSort = computed(
    () => sortOptions.find((o) => o.key === props.sort) ?? sortOptions[0],
);

const isActive = (key) => (props.status ?? null) === (key ?? null);
</script>

<template>
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div
            role="radiogroup"
            aria-label="Filter by status"
            class="inline-flex flex-wrap gap-1 rounded-lg border border-gray-200 bg-white p-1 dark:border-gray-700 dark:bg-gray-800"
        >
            <button
                v-for="pill in statusPills"
                :key="pill.key ?? 'all'"
                type="button"
                role="radio"
                :aria-checked="isActive(pill.key)"
                class="inline-flex items-center gap-1.5 rounded-md px-3 py-1.5 text-sm font-medium transition"
                :class="
                    isActive(pill.key)
                        ? 'bg-green-600 text-white shadow-sm'
                        : 'text-gray-600 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700'
                "
                @click="emit('update:status', pill.key)"
            >
                {{ pill.label }}
                <span
                    class="inline-flex min-w-[1.5rem] justify-center rounded-full px-1.5 text-xs tabular-nums"
                    :class="
                        isActive(pill.key)
                            ? 'bg-white/20 text-white'
                            : 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300'
                    "
                >
                    {{ pill.count ?? 0 }}
                </span>
            </button>
        </div>

        <Listbox
            :model-value="selectedSort"
            @update:model-value="(opt) => emit('update:sort', opt.key)"
        >
            <div class="relative">
                <ListboxButton
                    class="inline-flex w-full items-center justify-between gap-2 rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-green-600 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700 sm:w-56"
                >
                    <span class="truncate">
                        Sort: {{ selectedSort.label }}
                    </span>
                    <ChevronUpDownIcon class="h-4 w-4 text-gray-400" />
                </ListboxButton>
                <ListboxOptions
                    class="absolute right-0 z-10 mt-1 w-56 overflow-auto rounded-lg border border-gray-200 bg-white py-1 text-sm shadow-lg ring-1 ring-black/5 focus:outline-none dark:border-gray-700 dark:bg-gray-800"
                >
                    <ListboxOption
                        v-for="opt in sortOptions"
                        :key="opt.key"
                        v-slot="{ active, selected }"
                        :value="opt"
                    >
                        <li
                            class="flex cursor-pointer items-center justify-between px-3 py-2"
                            :class="
                                active
                                    ? 'bg-green-50 text-green-700 dark:bg-green-900/30 dark:text-green-300'
                                    : 'text-gray-700 dark:text-gray-200'
                            "
                        >
                            {{ opt.label }}
                            <CheckIcon
                                v-if="selected"
                                class="h-4 w-4 text-green-600 dark:text-green-400"
                            />
                        </li>
                    </ListboxOption>
                </ListboxOptions>
            </div>
        </Listbox>
    </div>
</template>
```

- [ ] **Step 2: Commit**

```bash
git add resources/js/Pages/Qualification/partials/FilterBar.vue
git commit -m "feat(qualification): add FilterBar partial with status pills and sort listbox"
```

---

## Task 5: Frontend — QualificationCard component

**Files:**
- Create: `resources/js/Pages/Qualification/partials/QualificationCard.vue`

- [ ] **Step 1: Create the file**

```vue
<script setup>
import { computed } from "vue";
import {
    AcademicCapIcon,
    PaperClipIcon,
} from "@heroicons/vue/24/outline";
import SubMenu from "@/Components/SubMenu.vue";

const props = defineProps({
    qualification: { type: Object, required: true },
    canEdit: { type: Boolean, default: false },
    canDelete: { type: Boolean, default: false },
    canApprove: { type: Boolean, default: false },
    canAttach: { type: Boolean, default: false },
});

const emit = defineEmits([
    "open-documents",
    "edit",
    "delete",
    "approve",
    "attach",
]);

const initials = computed(() => {
    const name = (props.qualification.person ?? "").trim();
    if (!name) return "?";
    const parts = name.split(/\s+/);
    return (
        (parts[0]?.[0] ?? "") + (parts[parts.length - 1]?.[0] ?? "")
    ).toUpperCase();
});

const isPending = computed(
    () => props.qualification.status_value === "pending",
);

const documentCount = computed(
    () => props.qualification.documents?.length ?? 0,
);

const menuItems = computed(() => {
    const items = [];
    if (props.canApprove && isPending.value) items.push("Approve");
    if (props.canEdit && isPending.value) items.push("Edit");
    if (props.canAttach) items.push("Attach");
    if (props.canDelete) items.push("Delete");
    return items;
});

const onMenu = (action) => {
    const map = {
        Approve: "approve",
        Edit: "edit",
        Attach: "attach",
        Delete: "delete",
    };
    emit(map[action], props.qualification);
};

const metaLine = computed(() => {
    const parts = [
        props.qualification.level,
        props.qualification.institution,
        props.qualification.year,
    ].filter(Boolean);
    return parts.join(" · ");
});
</script>

<template>
    <article
        class="group flex h-full flex-col rounded-xl border border-gray-200 bg-white p-4 shadow-sm transition hover:border-green-600/40 hover:shadow-md dark:border-gray-700 dark:bg-gray-800/60"
    >
        <header class="flex items-start justify-between gap-3">
            <div class="flex min-w-0 items-center gap-3">
                <div
                    class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-green-600/10 text-sm font-semibold text-green-700 dark:text-green-400"
                    aria-hidden="true"
                >
                    {{ initials }}
                </div>
                <div class="min-w-0">
                    <h3
                        class="truncate text-base font-semibold text-gray-900 dark:text-gray-50"
                    >
                        {{ qualification.person }}
                    </h3>
                    <p
                        v-if="qualification.staff_number"
                        class="truncate text-xs text-gray-500 dark:text-gray-400"
                    >
                        {{ qualification.staff_number }}
                    </p>
                </div>
            </div>
            <span
                class="inline-flex shrink-0 items-center gap-1 rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium dark:bg-gray-700/60"
                :class="qualification.status_color"
            >
                <span class="text-current">●</span>
                {{ qualification.status }}
            </span>
        </header>

        <div class="mt-4 flex items-start gap-2">
            <AcademicCapIcon
                class="mt-0.5 h-5 w-5 shrink-0 text-gray-400 dark:text-gray-500"
            />
            <div class="min-w-0">
                <p
                    class="truncate text-sm font-semibold text-gray-900 dark:text-gray-100"
                >
                    {{ qualification.qualification || qualification.course }}
                </p>
                <p
                    v-if="metaLine"
                    class="mt-0.5 truncate text-xs text-gray-600 dark:text-gray-400"
                >
                    {{ metaLine }}
                </p>
                <p
                    v-if="qualification.qualification_number"
                    class="mt-1 truncate text-xs tabular-nums text-gray-500 dark:text-gray-500"
                >
                    No. {{ qualification.qualification_number }}
                </p>
            </div>
        </div>

        <footer
            class="mt-4 flex items-center justify-between border-t border-gray-100 pt-3 dark:border-gray-700"
        >
            <button
                type="button"
                class="inline-flex items-center gap-1.5 text-xs text-gray-500 transition hover:text-green-700 disabled:cursor-not-allowed disabled:opacity-60 dark:text-gray-400 dark:hover:text-green-400"
                :disabled="documentCount === 0"
                @click="emit('open-documents', qualification)"
            >
                <PaperClipIcon class="h-4 w-4" />
                <span v-if="documentCount > 0">
                    {{ documentCount }} document{{ documentCount === 1 ? "" : "s" }}
                </span>
                <span v-else>No documents</span>
            </button>

            <SubMenu
                v-if="menuItems.length > 0"
                :can-edit="canEdit && isPending"
                :can-delete="canDelete"
                :can-approve="canApprove && isPending"
                :can-attach="canAttach"
                :items="menuItems"
                @item-clicked="onMenu"
            />
        </footer>
    </article>
</template>
```

- [ ] **Step 2: Commit**

```bash
git add resources/js/Pages/Qualification/partials/QualificationCard.vue
git commit -m "feat(qualification): add person-first QualificationCard partial"
```

---

## Task 6: Frontend — QualificationGrid + empty state

**Files:**
- Create: `resources/js/Pages/Qualification/partials/QualificationGrid.vue`

- [ ] **Step 1: Create the file**

```vue
<script setup>
import { AcademicCapIcon } from "@heroicons/vue/24/outline";
import QualificationCard from "./QualificationCard.vue";

defineProps({
    qualifications: { type: Array, default: () => [] },
    hasFilters: { type: Boolean, default: false },
    canEdit: { type: Boolean, default: false },
    canDelete: { type: Boolean, default: false },
    canApprove: { type: Boolean, default: false },
    canAttach: { type: Boolean, default: false },
});

const emit = defineEmits([
    "open-documents",
    "edit",
    "delete",
    "approve",
    "attach",
    "clear-filters",
]);
</script>

<template>
    <div v-if="qualifications.length > 0" class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3">
        <QualificationCard
            v-for="q in qualifications"
            :key="q.id"
            :qualification="q"
            :can-edit="canEdit"
            :can-delete="canDelete"
            :can-approve="canApprove"
            :can-attach="canAttach"
            @open-documents="(v) => emit('open-documents', v)"
            @edit="(v) => emit('edit', v)"
            @delete="(v) => emit('delete', v)"
            @approve="(v) => emit('approve', v)"
            @attach="(v) => emit('attach', v)"
        />
    </div>

    <div
        v-else
        class="flex flex-col items-center justify-center rounded-xl border border-dashed border-gray-300 bg-white py-16 text-center dark:border-gray-700 dark:bg-gray-800/40"
    >
        <div
            class="flex h-14 w-14 items-center justify-center rounded-full bg-gray-100 dark:bg-gray-700"
        >
            <AcademicCapIcon class="h-7 w-7 text-gray-400 dark:text-gray-300" />
        </div>
        <h3 class="mt-4 text-sm font-semibold text-gray-900 dark:text-gray-50">
            No qualifications found
        </h3>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
            <template v-if="hasFilters">
                Try adjusting your filters or search term.
            </template>
            <template v-else>
                Records will appear here once staff add qualifications.
            </template>
        </p>
        <button
            v-if="hasFilters"
            type="button"
            class="mt-4 text-sm font-medium text-green-700 hover:text-green-600 dark:text-green-400"
            @click="emit('clear-filters')"
        >
            Clear filters
        </button>
    </div>
</template>
```

- [ ] **Step 2: Commit**

```bash
git add resources/js/Pages/Qualification/partials/QualificationGrid.vue
git commit -m "feat(qualification): add QualificationGrid partial with empty state"
```

---

## Task 7: Rewrite Index.vue as orchestrator

**Files:**
- Modify: `resources/js/Pages/Qualification/Index.vue` (complete rewrite)

- [ ] **Step 1: Replace the file contents**

```vue
<script setup>
import { computed, onMounted, ref } from "vue";
import { Head, router, usePage } from "@inertiajs/vue3";
import { useToggle } from "@vueuse/core";
import MainLayout from "@/Layouts/NewAuthenticated.vue";
import Pagination from "@/Components/Pagination.vue";
import Modal from "@/Components/NewModal.vue";
import { useNavigation } from "@/Composables/navigation";

import PageHeader from "./partials/PageHeader.vue";
import StatsStrip from "./partials/StatsStrip.vue";
import FilterBar from "./partials/FilterBar.vue";
import QualificationGrid from "./partials/QualificationGrid.vue";
import QualificationList from "./QualificationList.vue";
import DocumentPreview from "./partials/DocumentPreview.vue";

const props = defineProps({
    qualifications: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
    stats: {
        type: Object,
        default: () => ({ total: 0, pending: 0, approved: 0, with_documents: 0 }),
    },
    can: { type: Object, default: () => ({}) },
});

// Mirror current behavior: Edit / Attach / Delete are always visible in the
// row menu (server-side policies gate the actions). Approve is visible only
// when the user has the approve permission AND the row is Pending.
const canEdit = computed(() => true);
const canDelete = computed(() => true);
const canAttach = computed(() => true);
const canApprove = computed(() => !!props.can?.approve);

const navigation = computed(() => useNavigation(props.qualifications));

const hasFilters = computed(
    () => !!(props.filters.search || props.filters.status),
);

const applyFilters = (next) => {
    const merged = {
        search: props.filters.search ?? null,
        status: props.filters.status ?? null,
        sort: props.filters.sort ?? "newest",
        view: props.filters.view ?? "grid",
        ...next,
    };
    const params = Object.fromEntries(
        Object.entries(merged).filter(
            ([, v]) => v !== null && v !== undefined && v !== "",
        ),
    );
    router.get(route("qualification.index"), params, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
};

const onSearch = (value) => applyFilters({ search: value || null });
const onStatus = (value) => applyFilters({ status: value || null });
const onSort = (value) => applyFilters({ sort: value || "newest" });
const onView = (value) => {
    try {
        localStorage.setItem("qualification.view", value);
    } catch (_) {
        // localStorage unavailable — ignore
    }
    applyFilters({ view: value || "grid" });
};
const clearFilters = () =>
    applyFilters({ search: null, status: null, sort: "newest" });

onMounted(() => {
    if (props.filters.view) return;
    try {
        const stored = localStorage.getItem("qualification.view");
        if (stored && stored !== "grid") {
            applyFilters({ view: stored });
        }
    } catch (_) {
        // ignore
    }
});

// Document preview
const showPreview = ref(false);
const togglePreview = useToggle(showPreview);
const selectedDocs = ref([]);
const docIndex = ref(0);
const currentDoc = computed(() => selectedDocs.value[docIndex.value] ?? null);
const openDocuments = (qualification) => {
    selectedDocs.value = qualification.documents ?? [];
    docIndex.value = 0;
    if (selectedDocs.value.length > 0) togglePreview();
};
const nextDoc = () => {
    if (docIndex.value < selectedDocs.value.length - 1) docIndex.value++;
};
const prevDoc = () => {
    if (docIndex.value > 0) docIndex.value--;
};

// Row actions — reuse the existing controller routes
const approveQualification = (q) => {
    router.patch(
        route("qualification.approve", { qualification: q.id }),
        {},
        { preserveScroll: true },
    );
};
const editQualification = (q) => {
    router.get(route("qualification.edit", { qualification: q.id }));
};
const deleteQualification = (q) => {
    if (!confirm("Delete this qualification?")) return;
    router.delete(route("qualification.destroy", { qualification: q.id }), {
        preserveScroll: true,
    });
};
const attachDocument = (q) => {
    router.get(route("qualification.attach", { qualification: q.id }));
};
</script>

<template>
    <MainLayout>
        <Head title="Qualifications" />
        <main class="mx-auto max-w-7xl space-y-5 px-4 py-6 sm:px-6 lg:px-8">
            <PageHeader
                :total="qualifications.total"
                :search-value="filters.search ?? ''"
                :view="filters.view ?? 'grid'"
                @update:search="onSearch"
                @update:view="onView"
            />

            <StatsStrip :stats="stats" />

            <FilterBar
                :status="filters.status ?? null"
                :sort="filters.sort ?? 'newest'"
                :stats="stats"
                @update:status="onStatus"
                @update:sort="onSort"
            />

            <QualificationGrid
                v-if="(filters.view ?? 'grid') === 'grid'"
                :qualifications="qualifications.data"
                :has-filters="hasFilters"
                :can-edit="canEdit"
                :can-delete="canDelete"
                :can-approve="canApprove"
                :can-attach="canAttach"
                @open-documents="openDocuments"
                @edit="editQualification"
                @delete="deleteQualification"
                @approve="approveQualification"
                @attach="attachDocument"
                @clear-filters="clearFilters"
            />

            <div v-else class="rounded-xl border border-gray-200 bg-white p-2 shadow-sm dark:border-gray-700 dark:bg-gray-800/60">
                <QualificationList
                    :qualifications="qualifications.data"
                    :can-edit="canEdit"
                    :can-delete="canDelete"
                    :can-approve="canApprove"
                    :can-attach="canAttach"
                    :can-add-staff-qualification="canEdit"
                    @editQualification="editQualification"
                    @deleteQualification="deleteQualification"
                    @approveQualification="approveQualification"
                    @attachDocument="attachDocument"
                />
            </div>

            <Pagination :navigation="navigation" />
        </main>

        <Modal :show="showPreview" @close="togglePreview">
            <DocumentPreview
                v-if="currentDoc"
                :url="'/storage/qualifications/' + currentDoc.file_name"
                :type="currentDoc.file_type"
                :title="currentDoc.document_title"
                :current-index="docIndex"
                :total-count="selectedDocs.length"
                @prev="prevDoc"
                @next="nextDoc"
            />
        </Modal>
    </MainLayout>
</template>
```

- [ ] **Step 2: Verify the route names exist**

Run: `php artisan route:list --path=qualification`
Expected: lists `qualification.approve`, `qualification.edit`, `qualification.destroy`, `qualification.attach`.

If any name is different in this project (e.g. `qualification.update` is the edit redirect target), adjust the corresponding handler in the `<script setup>` block to match the actual route name before continuing. Do NOT invent routes.

- [ ] **Step 3: Build assets**

Run: `npm run build`
Expected: build succeeds with no errors.

- [ ] **Step 4: Run the full qualification feature test**

Run: `php artisan test --filter=QualificationIndexTest`
Expected: all tests PASS (Inertia component name is still `Qualification/Index`).

- [ ] **Step 5: Commit**

```bash
git add resources/js/Pages/Qualification/Index.vue
git commit -m "feat(qualification): rewrite Index as orchestrator with grid/table views"
```

---

## Task 8: Table-view polish (minor)

**Files:**
- Modify: `resources/js/Pages/Qualification/QualificationList.vue`

- [ ] **Step 1: Polish table hover and row spacing**

In `resources/js/Pages/Qualification/QualificationList.vue`, find the data-row `<tr>` on line 171 and update its class list from:

```vue
class="dark:border-gray-400/30 hidden sm:table-row"
```

to:

```vue
class="hidden border-b border-gray-100 transition hover:bg-gray-50 dark:border-gray-700 dark:hover:bg-gray-800/50 sm:table-row"
```

Also wrap the table in a horizontal-scroll container. Change the root div on line 111 from:

```vue
<div class="flow-root sm:mx-0 w-full px-4">
```

to:

```vue
<div class="w-full overflow-x-auto">
```

- [ ] **Step 2: Run lint + build**

```bash
npm run lint
npm run build
```

Expected: no new errors.

- [ ] **Step 3: Commit**

```bash
git add resources/js/Pages/Qualification/QualificationList.vue
git commit -m "style(qualification): polish table hover states and scroll container"
```

---

## Task 9: Final verification

- [ ] **Step 1: Format PHP**

```bash
./vendor/bin/pint --dirty
```

- [ ] **Step 2: Lint and format JS**

```bash
npm run lint
npm run format
```

- [ ] **Step 3: Run the full qualification test suite**

```bash
php artisan test --filter=Qualification
```

Expected: all PASS, no failures, no skipped tests that used to run.

- [ ] **Step 4: Build production assets**

```bash
npm run build
```

Expected: build succeeds.

- [ ] **Step 5: Manual browser check**

Start the dev servers if not already running:

```bash
php artisan serve
npm run dev
```

In a browser, visit `/qualification` while logged in as a user with `view staff qualification` permission, and verify:

1. Page renders with header, stats strip, status pills, sort dropdown, card grid.
2. Typing in the search field triggers a debounced navigation (~300ms) and updates the URL.
3. Clicking each status pill (All / Pending / Approved / Rejected) updates the result set and the URL.
4. Changing sort updates the order.
5. Clicking "Table" toggles to table view; reloading the page keeps the table view (localStorage).
6. Clicking a card's document count (when > 0) opens the existing preview modal.
7. The action submenu Approve / Edit / Attach / Delete still works (approve only visible on Pending rows).
8. Dark mode toggle produces readable contrast across header, tiles, pills, and cards.
9. Empty state appears when filters match no rows and the "Clear filters" link resets filters.

- [ ] **Step 6: Final commit if anything else changed**

```bash
git status
# If anything lingering (pint / lint fixes), commit:
git add -A
git commit -m "chore(qualification): formatting and lint cleanup"
```

---

## Done criteria

- All tests in `QualificationIndexTest` pass; existing qualification tests still pass.
- `/qualification` renders the new card grid by default, supports search/status/sort/view, and shows accurate stat counts against the user's visible scope.
- Table view is still available via the toggle and renders without regressions.
- No lint, Pint, or build errors.
- No changes to qualification create/edit/delete/approve/attach flows, routes, or models.
