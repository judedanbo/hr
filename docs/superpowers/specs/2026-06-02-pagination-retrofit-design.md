# Pagination Retrofit (Cycle 2b) — Design

**Date:** 2026-06-02
**Status:** Approved for planning
**Cycle:** 2b of the app-settings effort. Consumes the `pagination_size` setting shipped in Cycle 2a. Cycle 2c (date-format retrofit) remains separate.

## Goal

Replace the ~41 hardcoded `paginate(N)` / bare `paginate()` call sites across 26 controllers with a single configurable page size sourced from `GeneralSettings::pagination_size` (default 10), plus an optional per-request `?per_page=` override. After this cycle, the "Records per page" setting on `/settings/app` actually controls list page sizes app-wide.

## The Helper

New `app/helpers.php`, registered in `composer.json` under `autoload.files` (alongside the existing `bootstrap/mbstring-polyfill.php`), then `composer dump-autoload`:

```php
<?php

if (! function_exists('per_page')) {
    /**
     * Resolve the page size for a paginated list: a clamped `?per_page=`
     * query override (5–100), otherwise the configured pagination size.
     */
    function per_page(): int
    {
        $requested = request()->query('per_page');

        if (is_numeric($requested)) {
            return (int) max(5, min(100, (int) $requested));
        }

        return app(\App\Settings\GeneralSettings::class)->pagination_size;
    }
}
```

- No `?per_page` (or non-numeric) → the configured `pagination_size` setting.
- `?per_page=N` → clamped to 5–100 (`1000`→100, `2`→5).
- The setting is always in range (the settings form validates `min:5|max:100`), so the fallback needs no clamping.
- `function_exists` guard keeps it safe under repeated autoload loading.

## The Retrofit

Every `->paginate(...)` in `app/Http/Controllers` becomes `->paginate(per_page())`, preserving any chained calls. Current distribution:

| Current | Count | After |
|---|---|---|
| `paginate()` (default 15) | 23 | `paginate(per_page())` |
| `paginate(10)` | 10 | `paginate(per_page())` |
| `paginate(20)` | 6 | `paginate(per_page())` |
| `paginate(5)` | 1 | `paginate(per_page())` |
| `paginate(15)` | 1 | `paginate(per_page())` |

This is **uniform**: all lists — including the audit log (was 20) and the staff directory (bare `paginate()`, was 15) — now follow `pagination_size`. Chained variants are preserved, e.g. `->paginate(per_page())->withQueryString()->through(...)`.

The exact call sites are discovered at implementation time with `grep -rEn "->paginate\(" app/Http/Controllers` (the count above is the current snapshot); the retrofit edits whatever that scan returns.

## Tests

**New:**
- `tests/Feature/PerPageHelperTest.php` — exercises `per_page()` within a request context:
  - no param → returns the setting (default 10).
  - `?per_page=25` → 25.
  - `?per_page=1000` → 100 (clamped high).
  - `?per_page=2` → 5 (clamped low).
  - `?per_page=abc` (non-numeric) → the setting.
  - changing `GeneralSettings::pagination_size` changes the no-param result.
- A representative endpoint test: the staff directory list paginates at the configured `pagination_size`, and honors `?per_page=`.

**Update existing** (assertions that shift from 15/20 to the new default of 10):
- `tests/Feature/Unit/StaffDirectoryTest.php::test_staff_endpoint_paginates_at_fifteen_per_page` — currently asserts `staff.meta.per_page == 15` and `has('staff.data', 15)`. Update to the configured default (10) and rename to reflect "configured page size"; add an assertion that `?per_page=` overrides it.
- Audit `tests/Feature/Unit/ShowTest.php`, `tests/Feature/QualificationReports/ServiceAggregationsTest.php`, and `tests/Feature/StaffAdvancedSearchTest.php` for any per-page/count assertions tied to 15/20 and update them to the new default. (StaffAdvancedSearchTest currently has no concrete data-count assertions, so it likely needs no change — confirm during implementation.)

## Notes / Out of Scope

- **No frontend changes.** Pagination controls render from the paginator's `meta`/`links`, so page-size changes flow through automatically; no Vue edits.
- **Performance:** `per_page()` resolves `GeneralSettings` once per paginated request (spatie settings cache is off by default) — negligible relative to the list query, and optimizable later by enabling the settings cache.
- **Non-controller paginators:** only `app/Http/Controllers` is in scope. If any models define a custom `$perPage`, they are out of scope for this cycle (none currently override it for the affected lists).
- **Cycle 2c** (date-format retrofit, ~180 sites) is a separate spec/plan.
