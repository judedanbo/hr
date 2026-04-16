# Qualification Index Page Redesign — Design Spec

**Date:** 2026-04-15
**Area:** `resources/js/Pages/Qualification/*`, `app/Http/Controllers/QualificationController.php`
**Status:** Approved for planning

## Goal

Replace the bare tabular `/qualification` index with a modern, person-first card layout that retains full tabular view on demand. Add first-class search, status filtering, sort, and at-a-glance stats while staying within the app's existing green-accented, dark-mode-aware design language.

## Non-Goals

- No changes to the create / edit / delete / approve / attach flows.
- No changes to `Qualification` model, migrations, or policies.
- No API versioning or new public routes.
- No redesign of child pages (Add, Edit, Delete, AttachDocument) beyond whatever the card's action menu invokes today.

## Current State

`resources/js/Pages/Qualification/Index.vue` renders an unstyled heading "Qualifications" and delegates to `QualificationList.vue`, which is a raw `<table>` with no header actions, no search, no filters, and no empty-state affordance. The backend controller (`QualificationController@index`) paginates qualifications scoped via `visibleTo($user)`, reads a `search` query param into `filters` but never applies it, and exposes a single `can.approve` flag.

## User-Facing Design

### Page layout (top-to-bottom)

1. **Breadcrumb** — `Home / Qualifications` (`BreadCrump` component).
2. **Page header** — Title "Qualifications" + total count; search input (debounced 300ms, searches person name, staff number, institution, course, qualification number); grid/table view toggle.
3. **Stats strip** — Four tiles: Total, Pending, Approved, With documents (each with a subtle left color accent: gray, amber, green, indigo).
4. **Filter bar** — Segmented status pills (All · Pending · Approved) with counts; sort dropdown (Newest, Oldest, Year ↓, Year ↑, Institution).
5. **Results region** — Either grid of cards or the existing table, driven by the view toggle.
6. **Pagination** — Existing `Pagination` component.

Container matches `Staff/Index.vue`: `max-w-7xl mx-auto sm:px-6 lg:px-8`.

### Qualification card (person-first)

- **Header row:** avatar disc with initials (green-600/10 bg, green-700 text; dark: green-400) + person name (`text-base font-semibold`) + staff number (`text-xs text-gray-500`). Status badge pinned top-right using the backend-emitted `status_color` classes plus a leading `●`.
- **Body:** grad-cap icon + qualification title (bold); single meta line `Level · Institution · Year` in gray-600; qualification number in `tabular-nums text-gray-500 text-xs`.
- **Footer:** left — paperclip icon + `{n} documents` (clickable, opens existing `DocumentPreview` modal). If zero documents, show muted "No documents". Right — existing `SubMenu` with same action contract as today (Approve / Edit / Attach / Delete).
- **Chrome:** `rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800/60 shadow-sm hover:shadow-md hover:border-green-600/40 transition`.
- **Grid:** `grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4`.

### Table view

Existing `QualificationList.vue` retained as the table implementation, with minor polish for visual consistency (header styling tweaks, row hover) but no column or behavior changes. Used when `view === 'table'`.

### Empty state

When results are zero:
- Centered grad-cap icon in a gray circle.
- Heading "No qualifications found".
- Subtitle adapts to context: if any filter or search is active, show "Try adjusting your filters" plus a "Clear filters" link; otherwise a neutral subtitle.

### Theme tokens

- Primary: `green-600` (hover `green-500`) for active pill, toggle active state, and card accents.
- Neutrals: `gray-50…900` with existing dark variants (`dark:bg-gray-800/60`, `dark:border-gray-700`, etc.).
- Focus rings: `focus-visible:outline-green-600`.
- Status colors: keep backend-emitted `status_color` as the single source of truth; components do not re-map status → color.

## Backend Changes

File: `app/Http/Controllers/QualificationController.php`, method `index`.

### Query params

- `search` — string. Matches against `person.full_name`, `person.institution.staff.staff_number`, `qualifications.institution`, `qualifications.course`, `qualifications.qualification_number`. Case-insensitive `LIKE` with `%term%` on each field, `orWhere`-combined.
- `status` — `pending` | `approved` | null/absent (All). Filters on `Qualification::status` via `QualificationStatusEnum`.
- `sort` — `newest` (default) | `oldest` | `year_desc` | `year_asc` | `institution`.
- `view` — `grid` (default) | `table`. Passed through to frontend via `filters`; does not affect the query.

### Stats aggregation

Compute `stats` against the same base query (scoped via `visibleTo($user)` and the `whereHas('person.institution')` constraint) **before** applying `search`/`status`/`sort`. Stats reflect the user's visible universe, not the filtered view, so the pills can show meaningful counts.

```
stats = {
  total: int,
  pending: int,
  approved: int,
  with_documents: int,
}
```

Implementation: `selectRaw` with conditional `COUNT`s on a single query to avoid 4 round-trips.

### Props returned to Inertia

```
qualifications: LengthAwarePaginator  (unchanged shape per-item)
filters: { search, status, sort, view }
stats:   { total, pending, approved, with_documents }
can:     { approve }
```

Existing per-item fields remain unchanged; no new fields required on the transformed row.

## Frontend Changes

### New components

- `resources/js/Pages/Qualification/partials/PageHeader.vue` — title, total, search input (debounced via `@vueuse/core` `useDebounceFn`), grid/table toggle.
- `resources/js/Pages/Qualification/partials/StatsStrip.vue` — 4 tiles; accepts `stats` prop.
- `resources/js/Pages/Qualification/partials/FilterBar.vue` — status pills (segmented) + sort `Listbox` (HeadlessUI).
- `resources/js/Pages/Qualification/partials/QualificationCard.vue` — single card.
- `resources/js/Pages/Qualification/partials/QualificationGrid.vue` — grid wrapper + empty state, emits the same action events as `QualificationList.vue`.

### Modified components

- `resources/js/Pages/Qualification/Index.vue` — rewritten as orchestrator: holds URL-synced filter state, switches between grid and table views, wires events to existing router calls for approve/edit/delete/attach (no controller changes).
- `resources/js/Pages/Qualification/QualificationList.vue` — minor visual polish only.

### State & persistence

- All filter state (search, status, sort, view) lives in the URL query string, driven by `router.get(route('qualification.index'), params, { preserveState: true, replace: true, preserveScroll: true })`. Shareable links; browser back/forward work.
- The `view` param is additionally mirrored to `localStorage['qualification.view']`. On first visit with no `view` query param, `Index.vue` reads localStorage and, if present, issues a single replace-navigation to sync the URL.

### Interactions

- Typing in search: debounced 300ms, then router request.
- Clicking status pill: immediate router request.
- Changing sort: immediate router request.
- Toggling view: immediate router request + localStorage write.
- Clicking documents count on a card: opens existing `DocumentPreview` modal with that card's documents.
- Clicking action menu item: emits to `Index.vue`, which dispatches to existing approve/edit/delete/attach flows exactly as today.

## Accessibility

- Search input has an associated label (sr-only ok).
- View toggle is a `role="group"` with two buttons; active state conveyed via `aria-pressed`.
- Status pills are `role="radiogroup"` with `role="radio"` children.
- Sort dropdown uses HeadlessUI `Listbox` (keyboard & aria out of the box).
- Cards are not links; interactive elements within them (documents count, action menu) are focusable with visible focus rings.
- Color is never the sole carrier of status — the pill always includes the status label text.

## Testing

File: `tests/Feature/QualificationIndexTest.php` (create if missing; feature test, PHPUnit per project convention).

Cases:
1. Index returns expected prop keys: `qualifications`, `filters`, `stats`, `can`.
2. `stats` counts match the user's visible scope, not the filtered view.
3. `search` matches against person name, staff number, institution, course, and qualification number (one case each).
4. `status=pending` excludes approved records; `status=approved` excludes pending.
5. `sort=year_desc` returns rows in descending year order; `sort=institution` returns alphabetical.
6. `view` param is echoed back in `filters` and does not alter the dataset.
7. Unauthorized user receives 403 (policy / gate already covered — assert unchanged).

Existing qualification tests must continue to pass; no test may be removed.

## Rollout

Single PR. No feature flag (purely additive on the backend; frontend is a visual rework of an internal page). Run:

```
./vendor/bin/pint --dirty
npm run lint
php artisan test --filter=Qualification
npm run build
```

before merging.

## Open questions

None at spec time. If the database proves slow on `search` across joined `person.institution.staff`, add composite indexes on `qualifications(status)`, `qualifications(year)`, and verify `people.full_name` / `institution_person.staff_number` have usable indexes — follow-up, not in scope here.
