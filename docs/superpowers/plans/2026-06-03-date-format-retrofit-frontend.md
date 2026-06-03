# Date-Format Retrofit — Frontend (Cycle 2c-2) Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Make frontend display dates follow the configured `date_format` setting by translating the PHP format to date-fns in JS (`phpToDateFns`) and applying it via a `useDateFormat()` composable, retrofitting the ~19 date-fns display sites + 5 `toLocaleDateString` DOB sites.

**Architecture:** A pure `phpToDateFns()` translator (own file, node-checkable) + a `useDateFormat()` composable reading the `app.date_format` shared prop. Each display component swaps its local date formatter for the composable's `formatDate()`. Functional `yyyy-MM-dd` inputs and number `toLocaleString` are left alone.

**Tech Stack:** Vue 3 `<script setup>`, Inertia v1, date-fns ^3.6, Laravel Dusk (browser test).

**Testing note:** No JS test runner exists. The risky pure translator is sanity-checked via `node` (not a committed test). Components are build-verified (`npm run build`). End-to-end behavior is verified by a Dusk browser test. The full PHPUnit suite (unaffected by frontend changes) is confirmed green in the Sail container.

**Branch:** `feature/date-format-frontend` (created; design committed there).

**Default-behavior note:** default `date_format='d M Y'` → date-fns `dd MMM yyyy`, so display dates change from `dd MMMM, yyyy` (e.g. `28 June, 2024`) to `28 Jun 2024` by default — unifying with the merged backend (2c-1).

---

## File Structure

- Create: `resources/js/composables/phpToDateFns.js` — pure translator (no imports; node-checkable).
- Create: `resources/js/composables/useDateFormat.js` — composable (imports the translator + date-fns + Inertia).
- Modify: 19 components using date-fns display `format(...)` + 5 using `toLocaleDateString` DOB.
- Create: `tests/Browser/DateFormatTest.php` — Dusk verification.

---

## Task 1: The translator + composable

**Files:**
- Create: `resources/js/composables/phpToDateFns.js`
- Create: `resources/js/composables/useDateFormat.js`

- [ ] **Step 1: Create the pure translator** `resources/js/composables/phpToDateFns.js`:

```js
const PHP_TO_DATE_FNS = {
	d: "dd",
	j: "d",
	D: "EEE",
	l: "EEEE",
	m: "MM",
	n: "M",
	M: "MMM",
	F: "MMMM",
	y: "yy",
	Y: "yyyy",
	H: "HH",
	G: "H",
	h: "hh",
	g: "h",
	i: "mm",
	s: "ss",
	A: "a",
	a: "a",
};

/**
 * Translate a PHP date() format string into a date-fns format string.
 * Unmapped letters are escaped (single-quoted) so date-fns never throws on
 * an unknown token; non-letters pass through; `\X` is a PHP literal escape.
 */
export function phpToDateFns(phpFormat) {
	let result = "";
	for (let i = 0; i < phpFormat.length; i++) {
		const ch = phpFormat[i];
		if (ch === "\\") {
			const next = phpFormat[i + 1] ?? "";
			if (next) {
				result += `'${next}'`;
				i++;
			}
			continue;
		}
		if (Object.prototype.hasOwnProperty.call(PHP_TO_DATE_FNS, ch)) {
			result += PHP_TO_DATE_FNS[ch];
		} else if (/[A-Za-z]/.test(ch)) {
			result += `'${ch}'`;
		} else {
			result += ch;
		}
	}
	return result;
}
```

- [ ] **Step 2: Sanity-check the translator with node**

Run:
```bash
node --input-type=module -e "import('./resources/js/composables/phpToDateFns.js').then(m => { const t = m.phpToDateFns; const cases = [['d M Y','dd MMM yyyy'],['Y-m-d','yyyy-MM-dd'],['d F Y','dd MMMM yyyy'],['d M, Y','dd MMM, yyyy'],['l, d F Y','EEEE, dd MMMM yyyy'],['d M Y H:i','dd MMM yyyy HH:mm']]; let ok=true; for (const [i,o] of cases){ const g=t(i); if(g!==o){ ok=false; console.log('FAIL', JSON.stringify(i), '->', JSON.stringify(g), 'expected', JSON.stringify(o)); } } console.log(ok ? 'ALL PASS' : 'FAILURES'); });"
```
Expected: `ALL PASS`. If any FAIL line prints, fix the translator and re-run.

- [ ] **Step 3: Create the composable** `resources/js/composables/useDateFormat.js`:

```js
import { usePage } from "@inertiajs/vue3";
import { format, parseISO, isValid } from "date-fns";
import { phpToDateFns } from "@/composables/phpToDateFns";

export { phpToDateFns };

/**
 * Returns a `formatDate(value)` bound to the app's configured date_format.
 * Accepts an ISO date string or a Date; returns '' for null/invalid input.
 */
export function useDateFormat() {
	const page = usePage();

	const formatDate = (value) => {
		if (!value) {
			return "";
		}
		const date = value instanceof Date ? value : parseISO(String(value));
		if (!isValid(date)) {
			return "";
		}
		return format(date, phpToDateFns(page.props.app?.date_format ?? "d M Y"));
	};

	return { formatDate };
}
```

- [ ] **Step 4: Build**

Run: `npm run build`
Expected: compiles cleanly (confirms the `@/composables/...` import path and date-fns/Inertia imports resolve). If it fails, report BLOCKED with the error.

- [ ] **Step 5: Commit**

```bash
git add resources/js/composables/phpToDateFns.js resources/js/composables/useDateFormat.js
git commit -m "feat: phpToDateFns translator and useDateFormat composable"
```

---

## Task 2: Retrofit the display sites

**Files (24):**
- date-fns display (19): `Pages/Institution/Staff.vue`, `Pages/Institution/Sta.vue`, `Pages/Person/Show.vue`, `Pages/Person/PersonAddresses.vue`, `Pages/Person/PersonContacts.vue`, `Pages/Person/Index.vue`, `Pages/Staff/StaffContacts.vue`, `Pages/Staff/StaffUnits.vue`, `Pages/Staff/StaffJobs.vue`, `Pages/Staff/Staff.vue`, `Pages/Staff/Show.vue`, `Pages/Staff/StaffRanks.vue`, `Pages/Status/Index.vue`, `Pages/Report/Recruitment/StaffTableRow.vue`, `Pages/User/StaffContacts.vue`, `Pages/User/StaffJobs.vue`, `Pages/User/Staff.vue`, `Pages/User/StaffRanks.vue`, `Pages/User/StaffUnits.vue` (all under `resources/js/`)
- `toLocaleDateString` DOB (5): `Components/Staff/ActiveFilters.vue`, `Pages/Person/Address.vue`, `Pages/Staff/NewShow.vue`, `Pages/Staff/Notes.vue`, `Pages/Notes/Qualifications.vue`

- [ ] **Step 1: Retrofit each date-fns display component**

For EACH of the 19 date-fns files, the pattern is a local helper:
```js
import { format } from "date-fns";
...
const formatDate = (date) => {
	return format(date, "dd MMMM, yyyy");   // or "d MMMM, yyyy" / "EEEE dd MMMM, yyyy"
};
```
Edit it to:
1. Add `import { useDateFormat } from "@/composables/useDateFormat";`.
2. Remove the local `const formatDate = (date) => { return format(date, "..."); };` and replace it with `const { formatDate } = useDateFormat();`.
3. If `format` from `date-fns` is now unused in the file, remove the `import { format } from "date-fns";` line (or remove only `format` from a multi-import). If the file also uses a `getDate`/`new Date` wrapper that is still referenced by the template, leave it — the composable's `formatDate` accepts both a `Date` and a string.

Verify per file with `php`-free check: `grep -n "format(" <file>` should show no `format(date, "dd MMMM` display calls remain.

- [ ] **Step 2: Retrofit each `toLocaleDateString` DOB component**

For EACH of the 5 DOB files, the pattern is:
```js
const formattedDob = (dob) => {
	if (!dob) return "";
	return new Date(dob).toLocaleDateString("en-GB", { day: "numeric", month: "short", year: "numeric" });
};
```
Edit to add `import { useDateFormat } from "@/composables/useDateFormat";` and `const { formatDate } = useDateFormat();`, then replace the local helper body so it delegates:
```js
const formattedDob = (dob) => formatDate(dob);
```
(Keep the `formattedDob` name so the template is unchanged. If a file names it differently, e.g. an inline `toLocaleDateString` in a computed, replace that expression with `formatDate(<the date value>)`.)
`ActiveFilters.vue` uses `toLocaleDateString` inside a function returning a label — replace that `date.toLocaleDateString('en-GB', {...})` with `formatDate(date)`.

- [ ] **Step 3: Confirm no display date-fns / toLocaleDateString remains**

Run:
```bash
grep -rn "dd MMMM, yyyy\|d MMMM, yyyy\|EEEE dd MMMM, yyyy" resources/js | grep -E "format\(" || echo "(no date-fns display literals)"
grep -rn "toLocaleDateString" resources/js || echo "(no toLocaleDateString)"
```
Expected: the date-fns display literals are gone; `toLocaleDateString` either gone or only in non-DOB/number contexts you intentionally left (there should be none from the 5 listed). The 37 `yyyy-MM-dd` date-fns sites MUST remain (don't touch them).

- [ ] **Step 4: Build**

Run: `npm run build`
Expected: compiles cleanly. If a file errors (e.g. removed `format` still referenced, or a leftover unused import warning that breaks build), fix that file and rebuild.

- [ ] **Step 5: Commit**

```bash
git add resources/js
git commit -m "refactor: render frontend display dates via useDateFormat"
```

---

## Task 3: Dusk browser test

**Files:**
- Create: `tests/Browser/DateFormatTest.php`

> Dusk setup gotcha (from project memory): the selenium container can't reach the host Vite dev server. Run with built assets — `npm run build` first, then pause the in-container Vite (`VPID=$(docker exec hr-laravel.test-1 pgrep -f 'node.*vite' | head -1); docker exec hr-laravel.test-1 sh -c "kill -STOP $VPID"`; back up & remove `public/hot`), run the Dusk test, then resume Vite (`kill -CONT $VPID`) and restore `public/hot`. A super-admin to `loginAs`: `richard.brobbey@audit.gov.gh`. New permissions must be seeded on the dev DB first if needed (not needed here).

- [ ] **Step 1: Write the Dusk test**

Create `tests/Browser/DateFormatTest.php`. It must: create (or use) a record with a known display date, set `GeneralSettings::date_format` to a value, visit a page that renders that date via a retrofitted component, and assert the rendered text. Use a page from the retrofit list that shows a known date. Concretely, target a staff/person date-of-birth or a status date the test controls. Structure:

```php
<?php

namespace Tests\Browser;

use App\Models\User;
use App\Settings\GeneralSettings;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class DateFormatTest extends DuskTestCase
{
    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::where('email', 'richard.brobbey@audit.gov.gh')->firstOrFail();
    }

    public function test_frontend_renders_dates_in_configured_format(): void
    {
        $original = app(GeneralSettings::class)->date_format;

        try {
            // pick a page + record with a known, controllable display date and assert
            // the rendered text for at least two formats:
            //   'd F Y'  -> e.g. '28 June 2024'
            //   'Y-m-d'  -> e.g. '2024-06-28'
            // (Use a factory-created person/status with date_of_birth/start_date set to
            //  a fixed date, navigate via loginAs($this->admin), and assertSee the
            //  formatted string after setting the GeneralSettings::date_format each time.)
            $this->markTestIncomplete('Wire the concrete page + fixture during implementation.');
        } finally {
            $settings = app(GeneralSettings::class);
            $settings->date_format = $original;
            $settings->save();
        }
    }
}
```

During implementation, replace the `markTestIncomplete` body with a real fixture + assertions: create a record with a fixed date, for each of `'d F Y'` and `'Y-m-d'` set `GeneralSettings::date_format`, save, `loginAs($this->admin)->visit(<route>)`, and `assertSee('28 June 2024')` / `assertSee('2024-06-28')` (or use a `dusk` selector). Restore the original format in `finally`. Pick the simplest retrofitted page whose date you can control via a factory (a person DOB on a profile/show page, or a status date).

- [ ] **Step 2: Run the Dusk test (built assets; pause Vite)**

```bash
npm run build
cp public/hot /tmp/hot.save 2>/dev/null
VPID=$(docker exec hr-laravel.test-1 pgrep -f 'node.*vite' | head -1)
docker exec hr-laravel.test-1 sh -c "kill -STOP $VPID"
rm -f public/hot
docker exec hr-laravel.test-1 php artisan dusk --filter=DateFormatTest 2>&1 | tail -20
docker exec hr-laravel.test-1 sh -c "kill -CONT $VPID"
cp /tmp/hot.save public/hot 2>/dev/null
```
Expected: PASS. If it fails, read the failure screenshot under `tests/Browser/screenshots/` (a blank page means an asset/JS error — check the console log). Fix the component or the translator and re-run. This is the real end-to-end check of `phpToDateFns` + `useDateFormat`.

- [ ] **Step 3: Commit**

```bash
git add tests/Browser/DateFormatTest.php
git commit -m "test: Dusk coverage for configurable frontend date format"
```

---

## Task 4: Final verification

- [ ] **Step 1: Build**

Run: `npm run build`
Expected: clean.

- [ ] **Step 2: PHP suite unaffected (sanity)**

Run: `docker exec hr-laravel.test-1 php artisan test 2>&1 | tail -6`
Expected: green (frontend changes don't affect PHPUnit; this confirms nothing else regressed, e.g. the new Dusk test file doesn't break collection — note Dusk tests are a separate suite and won't run here).

- [ ] **Step 3: Confirm scope boundaries held**

Run:
```bash
echo "functional yyyy-MM-dd date-fns sites still present (expect ~37):"
grep -rn "yyyy-MM-dd" resources/js | grep -E "format\(" | wc -l
echo "no display date-fns literals remain:"
grep -rn "dd MMMM, yyyy\|d MMMM, yyyy\|EEEE dd MMMM, yyyy" resources/js | grep -E "format\(" || echo "(none)"
```
Expected: ~37 `yyyy-MM-dd` preserved; no display literals.

- [ ] **Step 4: Lint changed JS (optional, non-blocking)**

Run: `npx eslint resources/js/composables/useDateFormat.js resources/js/composables/phpToDateFns.js 2>&1 | tail -5 || true`
(ESLint config targets `public/` in this repo, so this may be a no-op — do not block on it.)

- [ ] **Step 5: Manual smoke (ask the user to run)**

With the app running, set "Date format" on `/settings/app` to `Y-m-d` (then `d F Y`), open a page with a display date (a person profile DOB, transfer history, status list) and confirm it re-renders in the chosen format. Confirm date-input fields (edit forms) are unaffected.

---

## Self-Review

**Spec coverage:**
- `phpToDateFns()` translator with the token map + escape rule → Task 1 (own file, node-checked). ✓
- `useDateFormat()` composable reading `app.date_format`, null/invalid → `''` → Task 1. ✓
- Retrofit 19 date-fns display sites + 5 `toLocaleDateString` DOB sites → Task 2 (explicit file list + per-pattern recipe). ✓
- Leave functional `yyyy-MM-dd` + number `toLocaleString` → Task 2 Step 3, Task 4 Step 3 (verified preserved). ✓
- Dusk test exercising 2–3 formats → Task 3. ✓
- Build verification (no JS unit runner) → every task. ✓

**Placeholder scan:** The Dusk test (Task 3) intentionally ships a `markTestIncomplete` skeleton with explicit instructions to wire a concrete fixture/page during implementation — this is a genuine judgment step (the exact page/route + a controllable date fixture is chosen at implementation time), not a vague placeholder; the assertions (`'d F Y'`→`28 June 2024`, `'Y-m-d'`→`2024-06-28`) and the run mechanics are fully specified. No other placeholders. ✓

**Type/name consistency:** `phpToDateFns` defined in Task 1 (`phpToDateFns.js`), imported by `useDateFormat.js` and re-exported; `useDateFormat()`/`formatDate` used in Task 2 edits and Task 3. Import path `@/composables/useDateFormat` consistent. The default `'d M Y'` fallback matches the backend default and the node-check cases. ✓

**Out of scope:** functional `yyyy-MM-dd`, number `toLocaleString`, date math. This completes Cycle 2c.
