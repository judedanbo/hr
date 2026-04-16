# Unit Show — Recursive Stats, Accurate Sub-Unit Figures, Paginated Staff Directory

**Status:** Draft — pending approval
**Branch:** `fix/unit-show-recursive-stats-and-pagination`
**Date:** 2026-04-16

## Problem

The Unit show page (`resources/js/Pages/Unit/Show.vue`) currently has four issues:

1. **Overview cards** use bespoke markup instead of the reusable `StatCard.vue` component (`resources/js/Components/StatCard.vue`). The rest of the app has been migrating toward `StatCard` (see qualification-reports redesign); Unit show is out of step.
2. **Overview totals are only 2 levels deep.** `UnitController@show` computes totals as `$unit->staff + $sub->staff + $sub->subs->staff`. Anything nested deeper is silently missed. Same limitation applies to male/female figures.
3. **Sub-unit card counts and rank distribution are also 2-level-only.** Per-card `staff_count / male / female` and the rank-distribution array both come from the same 2-level aggregation.
4. **Staff Directory is incomplete and client-paginated.** The directory receives only the direct unit's staff (no nested descendants) and slices the array on the client. For units with many descendants, staff further down the tree are invisible; for units with many direct staff, the full list is shipped to the browser.

## Goals

- Overview section uses `StatCard.vue` for all four tiles.
- Total / Male / Female figures reflect the **full** descendant subtree, at any depth.
- Each sub-unit card's Staff / Male / Female figures reflect that sub-unit's **full** subtree.
- Rank distribution counts every active rank holder in the full subtree.
- Staff Directory shows staff from the unit and all descendants, paginated on the server with server-side search and filters.

## Non-Goals

- No denormalized/cached aggregate columns. Reads are computed live per request.
- No change to the hero section, office section, or modal plumbing.
- No change to the export endpoint's semantics beyond ensuring it scopes to descendant unit IDs (it already does via `export.unit.staff`; verify, don't redesign).
- No introduction of Inertia v2 features (deferred props, polling, etc.).

## Design

### Architecture

One small service encapsulates tree-walking; the controller composes aggregate queries on top of it.

```
UnitController@show ──┐
                      ├─► UnitHierarchy::descendantIds($unit) ─► [int]
UnitController@staff ─┘         (BFS over units.unit_id)

UnitController@show:
  1. descendantIds() once
  2. three aggregate queries (total, male, female) with whereIn
  3. rank distribution query with whereIn
  4. per-sub-unit aggregates keyed by immediate-child subtree
  5. initial paginated staff page via the staff loader
```

### Backend Components

#### `app/Services/UnitHierarchy.php` (new)

```php
final class UnitHierarchy
{
    /**
     * Return the ID of $unit plus every descendant unit's ID, at any depth.
     *
     * @return int[]
     */
    public function descendantIds(Unit $unit): array;

    /**
     * For each immediate child of $unit, return its subtree IDs (child + descendants).
     *
     * @return array<int, int[]>  keyed by child unit id
     */
    public function descendantIdsGroupedByChild(Unit $unit): array;
}
```

Implementation: iterative BFS over `units` table filtered by `unit_id IN (...)` and `end_date IS NULL`. One query per depth level; typical depth 3–4. No recursive CTE — keeps it portable across MySQL and any test DB without special flags.

#### `UnitController@show` (rewrite)

1. Load `$unit` with `institution`, `parent`, `currentOffice.district.region`, and immediate `subs` (no nested eager-loading of staff).
2. `$allIds = $hierarchy->descendantIds($unit)`.
3. `$childIdMap = $hierarchy->descendantIdsGroupedByChild($unit)`.
4. **Root aggregates** — one query over `staff_unit` joined to `institution_people` and `people`, grouped to return total/male/female in a single pass. Scope: `unit_id IN $allIds`, active pivot, active staff.
5. **Sub-unit aggregates** — one grouped query that joins each staff row to its descendant-child-id bucket. Returns `[childId => ['staff' => n, 'male' => n, 'female' => n]]`. Avoids N+1.
6. **Rank distribution** — existing `Job` query adapted: `whereIn('staff_unit.unit_id', $allIds)` on an active-staff join, grouped by rank, ordered by `job_categories.level`.
7. **Initial staff page** — delegate to the same loader that powers `@staff` (see below) so the first render uses identical logic.
8. Shape the Inertia response:

```php
Inertia::render('Unit/Show', [
    'unit'           => [ id, name, type, institution, parent, current_office ],
    'stats'          => [ 'total' => n, 'male' => n, 'female' => n,
                          'direct_subs' => n, 'total_descendants' => n ],
    'subs'           => [ { id, name, type, subs, staff_count, male_staff, female_staff, office } ... ],
    'rank_distribution' => [ { id, name, full_name, count } ... ],
    'staff'          => LengthAwarePaginator,     // Laravel paginator JSON
    'filter_options' => [ job_categories, ranks, sub_units, genders ],
    'filters'        => [ ...echoed request filters... ],
]);
```

#### `UnitController@staff` (new) — `GET /units/{unit}/staff`

Inertia partial endpoint. Vue calls it with `router.reload({ only: ['staff', 'filter_options'], data: {...} })`.

- Accepts: `search`, `job_category_id`, `rank_id`, `sub_unit_id`, `gender` (M|F), `hire_date_from`, `hire_date_to`, `age_from`, `age_to`, `page`.
- Validated via `app/Http/Requests/StaffDirectoryFilterRequest.php` (new).
- Query: `InstitutionPerson::query()->active()` joined to `staff_unit` with `whereIn('units.id', $allIds)`, applies filters, sorts by `job_categories.level`, paginates 15. Eager-loads `person`, `ranks.category`, and the first active `units` entry.
- Returns: `['staff' => $paginator->through(fn ($s) => [...shape...]), 'filter_options' => [...]]`.
- Maps to the same row shape the current frontend consumes (`id, name, gender, dob, initials, hire_date, staff_number, file_number, image, rank, unit`).

Filter options are derived from the descendant set (not from the current page) so dropdowns stay accurate.

#### Route

```php
Route::get('/units/{unit}/staff', [UnitController::class, 'staff'])
    ->middleware(['auth', 'can:view,unit'])
    ->name('unit.staff');
```

### Frontend Components

#### `resources/js/Components/StatCard.vue`

Add `"pink"` to the `accent` validator and to `accentClasses`. The primitive is used across the app; adding one color variant has no ripple.

#### `resources/js/Pages/Unit/partials/UnitStatsSection.vue` (rewrite)

- Drop bespoke card markup.
- Accept a `stats` prop: `{ total, male, female, direct_subs, total_descendants }`.
- Render four `StatCard`s via `v-for`:
  - Total Staff — `emerald`, icon `UsersIcon`
  - Male Staff — `indigo`, icon `UserGroupIcon`
  - Female Staff — `pink`, icon `UserGroupIcon`
  - Sub-Units — `slate`, icon `Square3Stack3DIcon`, secondary text: `"{total_descendants} nested"` when `total_descendants > direct_subs`.

#### `resources/js/Pages/Unit/partials/StaffDirectorySection.vue` (rewrite to server-driven)

Props change:
- `staff` becomes a Laravel paginator object (`{ data, links, meta: { current_page, last_page, from, to, total, per_page } }`).
- `filterOptions` (new): `{ job_categories: [{value,label}], ranks: [{value,label,category_id}], sub_units: [{value,label}], genders: [{value,label}] }`.
- Remove: `jobCategories` computed derived from `staff`, `ranks` computed, local `filteredStaff`, local pagination state, local `paginatedStaff`/`visiblePages`, local `watch` that resets page.

Behavior:
- `searchQuery` and `filterForm` become the canonical input state. A single `reload()` helper calls `router.reload({ only: ['staff', 'filter_options'], data: buildParams(), preserveState: true, preserveScroll: true, replace: true })`.
- `searchQuery` debounced 300ms before reloading; filter changes reload immediately.
- Page navigation sets `page` in the params and calls `reload()`.
- Pagination footer renders from `staff.meta` (from/to/total, current_page, last_page).
- Remove the `@search` emit — the component owns its own state now.
- Empty state and clear-filters behavior unchanged visually.

#### `resources/js/Pages/Unit/partials/SubUnitsCardGrid.vue`

No structural change. The props shape is identical; the numbers are simply correct now.

#### `resources/js/Pages/Unit/Show.vue`

- New props: `stats`, `filter_options`, `subs` (flat — the section that used `props.unit.subs` now uses `props.subs`).
- Remove the `handleSearch` handler and the `@search` binding.
- Pass `stats` into `UnitStatsSection`.
- Pass `staff` paginator and `filter_options` into `StaffDirectorySection`.

### Data Flow

```
User changes filter in StaffDirectorySection
  -> router.reload({ only: ['staff', 'filter_options'], data: {...} })
  -> Laravel hits UnitController@staff (or @show on hard navigation)
  -> UnitHierarchy::descendantIds($unit)
  -> Paginated query with whereIn + filter predicates
  -> Returns partial props: staff + filter_options
  -> Inertia merges; Vue re-renders table + pagination footer
```

### Error Handling

- `StaffDirectoryFilterRequest` returns 422 on invalid inputs; the Vue component surfaces `$page.props.errors` next to the offending filter (existing pattern in the codebase).
- `UnitHierarchy` treats an empty descendant set as `[$unit->id]` — so a leaf unit still aggregates its own staff correctly.
- Authorization: both `show` and `staff` use the existing `UnitPolicy@view`.

### Testing

**Unit tests (new):** `tests/Unit/Services/UnitHierarchyTest.php`
- 3-level tree returns all IDs.
- Soft-deleted / `end_date` set units are excluded.
- Leaf unit returns `[ownId]`.
- `descendantIdsGroupedByChild` buckets correctly when grandchildren exist under multiple branches.

**Feature tests (new/updated):** `tests/Feature/Unit/ShowTest.php`
- Seeds a unit tree: root → 2 children → 2 grandchildren each (5 units, 4 leaves); places known staff counts per node (mix of M/F).
- Asserts `stats.total / male / female` match the hand-computed totals from the full subtree.
- Asserts each sub-unit card shows its subtree total, not just its direct count.
- Asserts `rank_distribution` sums match across all levels.
- Asserts `staff` paginator returns staff from all depths, sorted by `job_categories.level`.
- Asserts each server-side filter (`gender`, `rank_id`, `sub_unit_id`, `hire_date_from/to`, `age_from/to`, `search`) narrows the result set as expected.
- Asserts an unauthorized user gets a redirect/403.

**Tests to keep passing:** the existing Unit controller test suite — confirm nothing regresses.

## Trade-offs and Alternatives Considered

- **Recursive CTE vs BFS loop.** CTE is one query but MySQL-specific and harder to test locally. The BFS loop runs one query per tree level; trees in this system are typically 3–4 levels, so the cost is trivial.
- **Denormalized `total_staff / total_male / total_female` columns on `units`.** Fastest reads but requires an observer or batch job, and drift is a real risk whenever staff move between units. Not worth it until read latency is demonstrated to be a problem.
- **Client-side pagination over a flattened descendant list.** Simplest to implement; breaks for large institutions (thousands of staff shipped to the browser). Rejected.
- **Inline staff loading in `@show` vs separate `@staff` action.** A single action with multiple Inertia partial keys would work, but splitting keeps `@show` focused on rendering and `@staff` focused on filtering. Mirrors the existing `export.unit.staff` split.

## Acceptance Criteria

1. Overview section renders four `StatCard`s with accurate totals across the full descendant subtree.
2. Sub-unit cards show recursive Staff / Male / Female figures.
3. Rank distribution sums across the full subtree.
4. Staff Directory is paginated server-side (15/page), lists staff from the unit and all descendants, and all filters operate server-side.
5. All existing Unit show tests pass. New tests (service + feature) pass.
6. `vendor/bin/pint --dirty` clean. `npm run lint` clean.
