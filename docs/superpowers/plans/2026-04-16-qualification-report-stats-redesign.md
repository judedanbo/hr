# Qualification Report — Stats Section Redesign Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Replace the four duplicated KPI card markup blocks on the qualification report index with a single uniform, reusable `StatCard` Vue component, and enrich the KPI payload with percentages, a sparkline for trend-over-time, and an "oldest pending" age signal.

**Architecture:** A presentational `StatCard.vue` component accepts `label`, `value`, `icon`, `accent`, `secondary`, `trend`, `sparkline` props and renders a dark-mode-aware card with a static Tailwind accent map. Backend changes reshape `QualificationReportController::kpis()` from flat ints to per-KPI objects, and extend `QualificationReportService::pendingApprovalsStats()` to add an `oldestDays` key. `trendByYear` (already computed) is reused as the sparkline series on the Total card.

**Tech Stack:** Laravel 11 (PHP 8.4), Vue 3 + Inertia v1, Tailwind v3, Heroicons v2, PHPUnit 11. Spec: `docs/superpowers/specs/2026-04-16-qualification-report-stats-redesign-design.md`.

**Branch:** `feature/qualifications-stats-redesign` (already created, spec committed).

---

## File map

- **Create:** `resources/js/Components/StatCard.vue` — new reusable KPI card.
- **Modify:** `app/Services/QualificationReportService.php:362-383` — extend `pendingApprovalsStats()` with `oldestDays`.
- **Modify:** `app/Http/Controllers/QualificationReportController.php:135-143` — reshape `kpis()`.
- **Modify:** `resources/js/Pages/Qualification/Reports/Index.vue:401-450` — replace KPI block with `StatCard` instances + computed helpers.
- **Modify:** `tests/Feature/QualificationReports/ServiceAggregationsTest.php:153-173` — extend existing pending-stats tests + add `oldestDays` cases.
- **Modify:** `tests/Feature/QualificationReports/IndexPageTest.php:26-45` — assert new `kpis` shape.

Note: `pendingApprovalsStats()` is also called from `QualificationDashboardController::widgets()` and `DataIntegrityController` — both consume the whole array, so **adding** an `oldestDays` key is backward compatible; no changes needed there.

---

## Task 1: Service — add `oldestDays` to `pendingApprovalsStats()`

**Files:**
- Modify: `app/Services/QualificationReportService.php:362-383`
- Modify test: `tests/Feature/QualificationReports/ServiceAggregationsTest.php:153-173`

- [ ] **Step 1: Write the failing tests**

Append two new tests at the end of the class in `tests/Feature/QualificationReports/ServiceAggregationsTest.php` (just before the closing `}`). Keep the existing `test_pending_approvals_stats_returns_count_and_sparkline` and `test_pending_approvals_sparkline_reflects_today_submissions` tests untouched — we only add to them.

Add these tests (and add the `Carbon` import at the top of the file: `use Illuminate\Support\Carbon;` — check existing imports first; if `Carbon` is already imported, skip that line):

```php
public function test_pending_approvals_stats_includes_oldest_days_null_when_no_pending(): void
{
    Qualification::factory()->approved()->count(2)->create();

    $result = app(QualificationReportService::class)->pendingApprovalsStats();

    $this->assertSame(0, $result['count']);
    $this->assertNull($result['oldestDays']);
}

public function test_pending_approvals_stats_returns_oldest_days_for_oldest_pending(): void
{
    Carbon::setTestNow('2026-04-16 12:00:00');

    Qualification::factory()->pending()->create(['created_at' => now()->subDays(3)]);
    Qualification::factory()->pending()->create(['created_at' => now()->subDays(10)]);
    Qualification::factory()->pending()->create(['created_at' => now()->subDays(1)]);

    $result = app(QualificationReportService::class)->pendingApprovalsStats();

    $this->assertSame(3, $result['count']);
    $this->assertSame(10, $result['oldestDays']);

    Carbon::setTestNow();
}
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `php artisan test --filter='pending_approvals_stats_includes_oldest_days_null_when_no_pending|pending_approvals_stats_returns_oldest_days_for_oldest_pending'`

Expected: FAIL — `Failed asserting that null matches expected 'oldestDays'` / missing array key `oldestDays`.

- [ ] **Step 3: Extend the service method**

Edit `app/Services/QualificationReportService.php`. Replace the entire `pendingApprovalsStats()` method (lines 359-383) with:

```php
/**
 * @return array{count: int, sparkline: array<int, int>, oldestDays: int|null} 30-day daily submissions, newest last; oldestDays is whole days since earliest pending record.
 */
public function pendingApprovalsStats(): array
{
    return $this->remember('pendingApprovalsStats', new QualificationReportFilter, function () {
        $count = Qualification::query()->pending()->count();

        $since = now()->subDays(29)->startOfDay();
        $daily = Qualification::query()
            ->pending()
            ->where('created_at', '>=', $since)
            ->selectRaw('DATE(created_at) AS d, COUNT(*) AS n')
            ->groupBy('d')
            ->pluck('n', 'd');

        $sparkline = [];
        for ($i = 29; $i >= 0; $i--) {
            $date = now()->subDays($i)->toDateString();
            $sparkline[] = (int) ($daily[$date] ?? 0);
        }

        $oldest = Qualification::query()->pending()->min('created_at');
        $oldestDays = $oldest
            ? (int) \Illuminate\Support\Carbon::parse($oldest)->diffInDays(now())
            : null;

        return ['count' => $count, 'sparkline' => $sparkline, 'oldestDays' => $oldestDays];
    });
}
```

- [ ] **Step 4: Run the new tests to verify they pass**

Run: `php artisan test --filter='pending_approvals_stats_includes_oldest_days_null_when_no_pending|pending_approvals_stats_returns_oldest_days_for_oldest_pending'`

Expected: PASS (2 tests).

- [ ] **Step 5: Run the full pending-stats test group to confirm existing tests still pass**

Run: `php artisan test --filter='pending_approvals'`

Expected: PASS (all four pending-stats tests).

- [ ] **Step 6: Commit**

```bash
./vendor/bin/pint --dirty
git add app/Services/QualificationReportService.php tests/Feature/QualificationReports/ServiceAggregationsTest.php
git commit -m "feat: return oldestDays from pendingApprovalsStats"
```

---

## Task 2: Controller — reshape `kpis()` payload

**Files:**
- Modify: `app/Http/Controllers/QualificationReportController.php:135-143`
- Modify test: `tests/Feature/QualificationReports/IndexPageTest.php:26-45`

- [ ] **Step 1: Write the failing test**

Edit `tests/Feature/QualificationReports/IndexPageTest.php`. Replace the `test_user_with_permission_can_view_page` method with the expanded version below (keep `test_authenticated_user_without_permission_is_denied` untouched). Add the necessary imports at the top if missing: `use App\Models\InstitutionPerson;` and `use App\Models\Qualification;`.

```php
public function test_user_with_permission_can_view_page(): void
{
    $user = User::factory()->create();
    $user->givePermissionTo(['qualifications.reports.view', 'qualifications.reports.view.all']);

    $this->actingAs($user->fresh())
        ->get('/qualifications/reports')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Qualification/Reports/Index')
            ->has('levelDistribution')
            ->has('byUnit')
            ->has('topInstitutions')
            ->has('trendByYear')
            ->has('staffList')
            ->has('kpis.totalQualifications.value')
            ->has('kpis.staffCovered.value')
            ->has('kpis.staffCovered.total')
            ->has('kpis.pending.value')
            ->has('kpis.pending.oldestDays')
            ->has('kpis.withoutQualifications.value')
            ->has('kpis.withoutQualifications.total')
            ->has('filterOptions')
            ->has('filters')
        );
}

public function test_kpis_payload_carries_active_staff_total_and_numeric_values(): void
{
    $user = User::factory()->create();
    $user->givePermissionTo(['qualifications.reports.view', 'qualifications.reports.view.all']);

    // 3 active staff, 1 separated
    InstitutionPerson::factory()->count(3)->create(['end_date' => null]);
    InstitutionPerson::factory()->create(['end_date' => now()->subMonth()]);

    $this->actingAs($user->fresh())
        ->get('/qualifications/reports')
        ->assertInertia(fn (Assert $page) => $page
            ->where('kpis.staffCovered.total', 3)
            ->where('kpis.withoutQualifications.total', 3)
            ->where('kpis.pending.oldestDays', null)
        );
}
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `php artisan test --filter='IndexPageTest'`

Expected: FAIL — property `kpis.totalQualifications.value` missing (because current shape is `kpis.totalQualifications` → int, not an object).

- [ ] **Step 3: Replace the `kpis()` method in the controller**

Edit `app/Http/Controllers/QualificationReportController.php`. Replace the entire `kpis()` method (lines 134-143) with:

```php
/** @return array<string, array<string, int|null>> */
private function kpis(QualificationReportFilter $filter): array
{
    $activeStaff = InstitutionPerson::query()->whereNull('end_date')->count();
    $pendingStats = $this->service->pendingApprovalsStats();

    return [
        'totalQualifications' => [
            'value' => Qualification::query()->approved()->count(),
        ],
        'staffCovered' => [
            'value' => Qualification::query()->approved()->distinct('person_id')->count('person_id'),
            'total' => $activeStaff,
        ],
        'pending' => [
            'value' => $pendingStats['count'],
            'oldestDays' => $pendingStats['oldestDays'] ?? null,
        ],
        'withoutQualifications' => [
            'value' => $this->service->staffWithoutQualifications($filter)->count(),
            'total' => $activeStaff,
        ],
    ];
}
```

The `InstitutionPerson` import is already present at the top of this file (line 8) — no new use statement needed.

- [ ] **Step 4: Run the tests to verify they pass**

Run: `php artisan test --filter='IndexPageTest'`

Expected: PASS (both tests).

- [ ] **Step 5: Run the full qualification-report test group to confirm no regressions**

Run: `php artisan test --filter='QualificationReports'`

Expected: PASS (all tests in `tests/Feature/QualificationReports/`).

- [ ] **Step 6: Commit**

```bash
./vendor/bin/pint --dirty
git add app/Http/Controllers/QualificationReportController.php tests/Feature/QualificationReports/IndexPageTest.php
git commit -m "feat: reshape qualification report kpis payload"
```

---

## Task 3: Create the `StatCard.vue` component

**Files:**
- Create: `resources/js/Components/StatCard.vue`

This task has no unit-test step — the repo has no Vue test harness and the component is presentational. Visual verification is covered in Task 5.

- [ ] **Step 1: Create the file**

Create `resources/js/Components/StatCard.vue` with this exact content:

```vue
<script setup>
import { computed } from "vue";

const props = defineProps({
	label: { type: String, required: true },
	value: { type: [Number, String], required: true },
	icon: { type: Object, default: null },
	accent: {
		type: String,
		default: "slate",
		validator: (v) => ["indigo", "emerald", "amber", "red", "slate"].includes(v),
	},
	secondary: { type: String, default: null },
	trend: { type: Object, default: null }, // { direction: 'up'|'down'|'flat', text: string }
	sparkline: { type: Array, default: null },
});

const accentClasses = {
	indigo: {
		border: "border-indigo-500 dark:border-indigo-400",
		iconBg: "bg-indigo-50 dark:bg-indigo-900/40",
		iconText: "text-indigo-600 dark:text-indigo-300",
		spark: "#6366f1",
	},
	emerald: {
		border: "border-emerald-500 dark:border-emerald-400",
		iconBg: "bg-emerald-50 dark:bg-emerald-900/40",
		iconText: "text-emerald-600 dark:text-emerald-300",
		spark: "#059669",
	},
	amber: {
		border: "border-amber-500 dark:border-amber-400",
		iconBg: "bg-amber-50 dark:bg-amber-900/40",
		iconText: "text-amber-600 dark:text-amber-300",
		spark: "#d97706",
	},
	red: {
		border: "border-red-500 dark:border-red-400",
		iconBg: "bg-red-50 dark:bg-red-900/40",
		iconText: "text-red-600 dark:text-red-300",
		spark: "#dc2626",
	},
	slate: {
		border: "border-slate-400 dark:border-slate-500",
		iconBg: "bg-slate-100 dark:bg-slate-800",
		iconText: "text-slate-600 dark:text-slate-300",
		spark: "#64748b",
	},
};

const accentCfg = computed(() => accentClasses[props.accent] ?? accentClasses.slate);

const formattedValue = computed(() => {
	if (typeof props.value === "number") return props.value.toLocaleString();
	return props.value;
});

const sparklinePath = computed(() => {
	const series = props.sparkline;
	if (!Array.isArray(series) || series.length < 2) return null;
	const max = Math.max(...series);
	const min = Math.min(...series);
	const range = max - min || 1;
	const stepX = 100 / (series.length - 1);
	return series
		.map((v, i) => {
			const x = (i * stepX).toFixed(2);
			const y = (22 - ((v - min) / range) * 20).toFixed(2);
			return `${i === 0 ? "" : " "}${x},${y}`;
		})
		.join("");
});

const trendClasses = computed(() => {
	if (!props.trend) return "";
	switch (props.trend.direction) {
		case "up":
			return "bg-emerald-50 dark:bg-emerald-900/40 text-emerald-700 dark:text-emerald-300";
		case "down":
			return "bg-red-50 dark:bg-red-900/40 text-red-700 dark:text-red-300";
		default:
			return "bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300";
	}
});
</script>

<template>
	<div
		class="bg-white dark:bg-gray-800 rounded-lg shadow-sm ring-1 ring-gray-900/5 dark:ring-gray-700 p-4 border-l-4"
		:class="accentCfg.border"
	>
		<div class="flex items-start justify-between gap-2">
			<div
				class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400 font-medium"
			>
				{{ label }}
			</div>
			<div
				v-if="icon"
				class="h-8 w-8 rounded-md flex items-center justify-center flex-shrink-0"
				:class="accentCfg.iconBg"
				:aria-label="label"
			>
				<component :is="icon" class="h-4 w-4" :class="accentCfg.iconText" />
			</div>
		</div>
		<div class="mt-2 text-2xl font-bold text-gray-900 dark:text-white tabular-nums">
			{{ formattedValue }}
		</div>
		<svg
			v-if="sparklinePath"
			class="mt-2 w-full h-6"
			viewBox="0 0 100 22"
			preserveAspectRatio="none"
			aria-hidden="true"
		>
			<polyline
				fill="none"
				stroke-width="2"
				stroke-linecap="round"
				stroke-linejoin="round"
				:stroke="accentCfg.spark"
				:points="sparklinePath"
			/>
		</svg>
		<div
			v-if="secondary"
			class="mt-1 text-xs text-gray-500 dark:text-gray-400"
		>
			{{ secondary }}
		</div>
		<div v-if="trend" class="mt-2">
			<span
				class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium"
				:class="trendClasses"
			>
				<span v-if="trend.direction === 'up'">↑</span>
				<span v-else-if="trend.direction === 'down'">↓</span>
				<span v-else>→</span>
				{{ trend.text }}
			</span>
		</div>
	</div>
</template>
```

- [ ] **Step 2: Run lint to confirm file parses cleanly**

Run: `npm run lint -- resources/js/Components/StatCard.vue`

Expected: PASS (no errors).

If the lint command doesn't accept a file argument on this project, just run `npm run lint` and confirm no new warnings reference `StatCard.vue`.

- [ ] **Step 3: Commit**

```bash
git add resources/js/Components/StatCard.vue
git commit -m "feat: add reusable StatCard component"
```

---

## Task 4: Wire `StatCard` into the qualification report index

**Files:**
- Modify: `resources/js/Pages/Qualification/Reports/Index.vue`

- [ ] **Step 1: Add imports in the `<script setup>` block**

Open `resources/js/Pages/Qualification/Reports/Index.vue`. Locate the existing import block at the top (lines 1-16). Add these imports below the existing imports (before `const props = defineProps(...)` on line 18):

```js
import StatCard from "@/Components/StatCard.vue";
import {
	AcademicCapIcon,
	UserGroupIcon,
	ClockIcon,
	ExclamationTriangleIcon,
} from "@heroicons/vue/24/outline";
```

- [ ] **Step 2: Add derived-display computed refs**

In the same `<script setup>`, add the following block immediately after the `activeFilters` computed (after the closing `});` on line 152, before `function removeFilter(key)` on line 154):

```js
const sparklineSeries = computed(() => {
	const t = props.trendByYear ?? {};
	const entries = Object.entries(t)
		.map(([year, count]) => [Number(year), Number(count)])
		.filter(([y, c]) => Number.isFinite(y) && Number.isFinite(c))
		.sort((a, b) => a[0] - b[0]);
	if (entries.length < 2) return null;
	return entries.map(([, c]) => c);
});

const sparklineSummary = computed(() => {
	const series = sparklineSeries.value;
	if (!series) return null;
	const first = series[0];
	const last = series[series.length - 1];
	if (first === 0) return `${series.length} yrs of history`;
	const pct = (((last - first) / first) * 100).toFixed(0);
	const sign = pct >= 0 ? "+" : "";
	return `${sign}${pct}% over ${series.length} yrs`;
});

const coveredSecondary = computed(() => {
	const total = props.kpis?.staffCovered?.total ?? 0;
	const value = props.kpis?.staffCovered?.value ?? 0;
	if (total === 0) return null;
	return `${((value / total) * 100).toFixed(1)}% of active staff`;
});

const gapsSecondary = computed(() => {
	const total = props.kpis?.withoutQualifications?.total ?? 0;
	const value = props.kpis?.withoutQualifications?.value ?? 0;
	if (total === 0) return null;
	return `${((value / total) * 100).toFixed(1)}% of active staff`;
});

const pendingSecondary = computed(() => {
	const value = props.kpis?.pending?.value ?? 0;
	if (value === 0) return "None pending";
	const totalQuals = props.kpis?.totalQualifications?.value ?? 0;
	const pct = totalQuals > 0 ? ((value / totalQuals) * 100).toFixed(1) : null;
	const oldest = props.kpis?.pending?.oldestDays;
	const parts = [];
	if (pct !== null) parts.push(`${pct}% of total`);
	if (oldest !== null && oldest !== undefined) {
		parts.push(oldest === 0 ? "oldest today" : `oldest ${oldest}d`);
	}
	return parts.join(" · ") || null;
});
```

- [ ] **Step 3: Replace the old KPI grid block in the template**

In the `<template>`, locate the existing KPI grid block at lines 401-450 (the `<div class="grid grid-cols-2 md:grid-cols-4 gap-4">` containing four duplicated card divs). Replace the entire block — from the opening `<div class="grid ...` on line 401 through its closing `</div>` on line 450 — with:

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

- [ ] **Step 4: Run frontend lint**

Run: `npm run lint`

Expected: PASS (no errors related to `Index.vue` or `StatCard.vue`).

- [ ] **Step 5: Run the index page feature test to confirm backend + frontend still integrate**

Run: `php artisan test --filter='IndexPageTest'`

Expected: PASS.

- [ ] **Step 6: Start the dev server and visually verify**

Open two terminals:

```bash
# Terminal 1
php artisan serve
```

```bash
# Terminal 2
npm run dev
```

Then in a browser, log in as a user with `qualifications.reports.view` and `qualifications.reports.view.all` permissions and visit `/qualifications/reports`.

Verify:
- Four cards render in one row on desktop (≥768px), two columns on tablet (640-768px), single column below 640px.
- Left border color is distinct per card (indigo / emerald / amber / red).
- Icon tile shows in the top-right corner of each card with matching tinted background.
- Total Qualifications shows a small sparkline below the value (only if 2+ trend years of data exist).
- Staff Covered and Staff Without Quals show `"X.X% of active staff"`.
- Pending shows `"X.X% of total · oldest Nd"` when there are pending records, or `"None pending"` when there are none.
- Toggle dark mode (if the app has a toggle — otherwise inspect via DevTools adding `class="dark"` to `<html>`): all text, backgrounds, borders, and accents remain legible with correct dark variants.

If any of these fail, fix the issue and re-run steps 4 and 5.

- [ ] **Step 7: Commit**

```bash
./vendor/bin/pint --dirty
git add resources/js/Pages/Qualification/Reports/Index.vue
git commit -m "feat: use StatCard in qualification report stats row"
```

---

## Task 5: Final quality gate

- [ ] **Step 1: Run the full qualification-reports test group**

Run: `php artisan test --filter='QualificationReports'`

Expected: PASS (all tests — both existing and new).

- [ ] **Step 2: Run the full application test suite**

Run: `php artisan test`

Expected: PASS (no regressions introduced elsewhere).

- [ ] **Step 3: Run Pint on any remaining dirty files**

Run: `./vendor/bin/pint --dirty`

Expected: `No lint errors found` or "x files fixed". If files are fixed, commit them:

```bash
git add -u
git commit -m "chore: pint formatting"
```

- [ ] **Step 4: Run ESLint + Prettier**

Run: `npm run lint && npm run format`

Expected: PASS.

If Prettier reformats files, commit them:

```bash
git add -u
git commit -m "chore: prettier formatting"
```

- [ ] **Step 5: Summarise the branch state**

Run: `git log --oneline main..HEAD`

Expected at least these commits on `feature/qualifications-stats-redesign`:
1. `docs: spec for qualification report stats section redesign` (pre-existing)
2. `docs: implementation plan for stats redesign` (added in the plan-commit step below)
3. `feat: return oldestDays from pendingApprovalsStats`
4. `feat: reshape qualification report kpis payload`
5. `feat: add reusable StatCard component`
6. `feat: use StatCard in qualification report stats row`
7. Optional formatting commits from steps 3-4 of this task

The branch is now ready to open as a pull request. Do NOT push or create the PR unless the user explicitly asks.
