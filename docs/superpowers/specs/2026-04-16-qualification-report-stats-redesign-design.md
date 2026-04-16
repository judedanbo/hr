# Qualification Report — Stats Section Redesign

**Date:** 2026-04-16
**Branch:** `feature/qualifications-stats-redesign`
**Page affected:** `resources/js/Pages/Qualification/Reports/Index.vue`

## Goal

Replace the four duplicated KPI card markup blocks in the qualification report index with a single uniform, reusable `StatCard` component. Add richer secondary context (percentages, a sparkline on totals, and an age signal on pending) while respecting the application's existing dark-mode theme and visual language (ring + shadow + rounded-lg pattern used elsewhere on the page).

## Scope

**In scope**

- New generic component `resources/js/Components/StatCard.vue`.
- Re-shape `QualificationReportController::kpis()` to return richer data (value + derived context per KPI).
- Extend `QualificationReportService::pendingApprovalsStats()` to also return `oldestDays`.
- Wire the new component into `Qualification/Reports/Index.vue` with derived display strings for each card.
- Update/add feature tests asserting the new `kpis` payload shape.
- Dark-mode support matching existing `dark:` usage on the page.

**Out of scope**

- Retrofitting `StatCard` into other report pages (Recruitment, etc.) — the component is built to be reusable, but this PR only uses it on the qualification report.
- Click-to-filter behavior on cards (Option B from brainstorming). The component leaves room for `@click` but the consumer will not wire it in this change.
- Visual or data changes to any chart below the KPI row.

## Component design

### `resources/js/Components/StatCard.vue`

A presentational Vue 3 `<script setup>` component. Single root element.

**Props**

| Prop | Type | Required | Default | Purpose |
| --- | --- | --- | --- | --- |
| `label` | `String` | yes | — | Uppercase caption shown above the value. |
| `value` | `Number \| String` | yes | — | Primary metric. If numeric, rendered via `toLocaleString()`. |
| `icon` | `Object` (Vue component) | no | `null` | Heroicon component shown in the top-right icon tile. |
| `accent` | `'indigo' \| 'emerald' \| 'amber' \| 'red' \| 'slate'` | no | `'slate'` | Drives left-border color and icon tile tint. |
| `secondary` | `String` | no | `null` | Small grey text under the value (e.g. "93.3% of active staff"). |
| `trend` | `{ direction: 'up' \| 'down' \| 'flat', text: String }` | no | `null` | Optional colored trend chip below the secondary line. |
| `sparkline` | `Array<Number>` | no | `null` | Optional inline SVG sparkline drawn at the width of the card. |

**Visual structure**

- Outer: `bg-white dark:bg-gray-800 rounded-lg shadow-sm ring-1 ring-gray-900/5 dark:ring-gray-700 p-4 border-l-4`.
- Left border color: mapped from `accent` (e.g. indigo-500 in light mode, indigo-400 in dark mode).
- Flex column inside: label row (label + icon tile right-aligned) → value → optional sparkline → optional secondary line → optional trend chip.
- Icon tile: `h-8 w-8 rounded-md` with tinted background (`bg-indigo-50 dark:bg-indigo-900/40` etc.) and the Heroicon rendered with `text-{accent}-600 dark:text-{accent}-300`.
- Sparkline: small SVG with `<polyline>`, stroke matches accent, height ~24px, width 100%, `preserveAspectRatio="none"`.
- Trend chip: colored pill; green for `up`, red for `down`, grey for `flat`.

**Tailwind JIT constraint**

Dynamic class names like `text-${accent}-600` are purged by Tailwind. Resolve via a static class map in the component (`const accentClasses = { indigo: { border: 'border-indigo-500 dark:border-indigo-400', iconBg: 'bg-indigo-50 dark:bg-indigo-900/40', iconText: 'text-indigo-600 dark:text-indigo-300', spark: '#6366f1' }, ... }`). All classes in the map are plain string literals so the JIT scanner picks them up.

**Accessibility**

- Card root has no interactive role (presentational).
- `aria-label` on the icon tile contains the same text as `label` so screen readers don't read the icon separately.
- Sparkline is decorative: `aria-hidden="true"`. The essential info is duplicated in `secondary` text.

## Backend data changes

### `QualificationReportController::kpis()`

Current shape (flat ints):

```php
[
    'totalQualifications'   => int,
    'staffCovered'          => int,
    'pending'               => int,
    'withoutQualifications' => int,
]
```

New shape:

```php
[
    'totalQualifications'   => ['value' => int],
    'staffCovered'          => ['value' => int, 'total' => int],
    'pending'               => ['value' => int, 'oldestDays' => int | null],
    'withoutQualifications' => ['value' => int, 'total' => int],
]
```

`total` on `staffCovered` and `withoutQualifications` is the count of `InstitutionPerson::whereNull('end_date')` (active staff), computed once and reused for both. When `total` is `0`, the frontend falls back to rendering only the raw value without a percentage line (guards against divide-by-zero).

### `QualificationReportService::pendingApprovalsStats()`

Today the method returns `['count' => int]`. Extend it to also return `oldestDays` — the integer number of whole days between `now()` and the earliest `created_at` across pending qualifications, or `null` if there are no pending records.

Implementation sketch:

```php
$oldest = Qualification::query()->pending()->min('created_at');
return [
    'count'      => $count,
    'oldestDays' => $oldest ? Carbon::parse($oldest)->diffInDays(now()) : null,
];
```

If `pendingApprovalsStats()` is called elsewhere (grep before implementing), verify those callers tolerate the extra key — an associative array with an added key is backward-compatible for array-access consumers.

### Sparkline data source

The page already receives `trendByYear` from the controller. We reuse it as the sparkline series on the Total Qualifications card — no new backend call is needed. A `computed()` in the Vue page extracts an ordered array of counts (chronologically sorted by year) and a short summary string like `"+8% over <n> years"` derived from first vs last value. If `trendByYear` has fewer than 2 points, omit the sparkline entirely and fall back to secondary text only.

## Page wiring — `Qualification/Reports/Index.vue`

Replace the existing KPI grid block (currently lines 401–450 — four duplicated `<div class="bg-white ...">` cards) with:

```vue
<div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4">
  <StatCard
    label="Total Qualifications"
    :value="kpis.totalQualifications.value"
    :icon="AcademicCapIcon"
    accent="indigo"
    :sparkline="sparklineSeries"
    :secondary="sparklineSummary"
  />
  <StatCard
    label="Staff Covered"
    :value="kpis.staffCovered.value"
    :icon="UserGroupIcon"
    accent="emerald"
    :secondary="coveredSecondary"
  />
  <StatCard
    label="Pending"
    :value="kpis.pending.value"
    :icon="ClockIcon"
    accent="amber"
    :secondary="pendingSecondary"
  />
  <StatCard
    label="Staff Without Quals"
    :value="kpis.withoutQualifications.value"
    :icon="ExclamationTriangleIcon"
    accent="red"
    :secondary="gapsSecondary"
  />
</div>
```

### Derived display strings (computed refs in `<script setup>`)

- `coveredSecondary` — `"${pct}% of active staff"` where `pct = (covered / total * 100).toFixed(1)`. If `total === 0`, returns `null`.
- `gapsSecondary` — same formula for `withoutQualifications`. Returns `null` if `total === 0`.
- `pendingSecondary` — `"${pct}% of total"` plus `" · oldest ${oldestDays}d"` when `oldestDays` is non-null. If pending is zero, returns `"None pending"`.
- `sparklineSeries` — `Object.entries(trendByYear).sort(...).map(([,n]) => n)`, or `null` if fewer than 2 data points.
- `sparklineSummary` — `"+${pct}% over ${years} years"` computed from first vs last value in series, or `null` when series is absent.

All computeds are null-safe against missing props.

### Grid responsiveness

- `< 640px` (mobile): 1 column, full-width cards.
- `640–768px`: 2 columns.
- `≥ 768px`: 4 columns (matches current layout).

## Tests

### Feature test updates

`tests/Feature/QualificationReportControllerTest.php` (or closest existing test — verify filename before editing):

- Update the existing index-render assertion to expect the new `kpis` payload shape: `kpis.totalQualifications.value`, `kpis.staffCovered.total`, `kpis.pending.oldestDays`, `kpis.withoutQualifications.total`.
- Add a case with zero pending records asserting `kpis.pending.oldestDays === null`.
- Add a case where active staff count is zero asserting the response still renders (no crash on divide-by-zero — frontend handles null `total`, but confirm backend emits `total: 0`).

### Service unit test

Add a test for `QualificationReportService::pendingApprovalsStats()` `oldestDays`:

- When no pending qualifications exist → `oldestDays` is `null`.
- When pending qualifications exist with a `created_at` 10 days ago → `oldestDays` is `10`.

### Vue component

This repo has no Vue unit test harness. `StatCard` is exercised indirectly by the feature test that renders the Inertia page. No new JS tooling is introduced.

## Dark mode

Every Tailwind class in `StatCard` that has a light-mode color also has a matching `dark:` variant, following the exact pattern already used on this page (`bg-white dark:bg-gray-800`, `text-gray-500 dark:text-gray-400`, `ring-gray-900/5 dark:ring-gray-700`). The accent class map includes both light and dark shades per accent.

## Commit plan

1. Add `StatCard.vue` component (standalone, no wiring yet).
2. Update `QualificationReportController::kpis()` and `QualificationReportService::pendingApprovalsStats()` for new payload shape.
3. Wire `StatCard` into `Qualification/Reports/Index.vue`, remove old duplicated markup.
4. Update/add feature and service tests.
5. Run `./vendor/bin/pint --dirty`, `npm run lint`, `php artisan test --filter=QualificationReport`.

## Risks / things to verify during implementation

- Grep for other callers of `pendingApprovalsStats()` before changing its return shape.
- Verify `QualificationReportControllerTest` exists under that name — if not, update the nearest applicable test file and mention the actual path in the implementation plan.
- Confirm Heroicons package exports `AcademicCapIcon`, `UserGroupIcon`, `ClockIcon`, `ExclamationTriangleIcon` under `@heroicons/vue/24/outline` (already used elsewhere in this project).
- Carbon `diffInDays` behavior — ensure it returns a positive integer regardless of argument order, or use `abs()` defensively.
