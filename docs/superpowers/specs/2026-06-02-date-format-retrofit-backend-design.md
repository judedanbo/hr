# Date-Format Retrofit — Cycle 2c-1 (Backend) Design

**Date:** 2026-06-02
**Status:** Approved for planning
**Cycle:** 2c-1 of the app-settings effort (backend half). Consumes the `date_format` setting from Cycle 2a. The frontend half is **2c-2** (separate spec/plan).

## Goal

Make server-rendered **display** dates follow the configurable `GeneralSettings::date_format` setting, via Carbon macros. Scope is `app/Http/Controllers` plus one model accessor. Functional date formatting (form values, query keys, API payloads, exports, forensic timestamps) is deliberately left untouched.

## The Macros

Register in `App\Providers\AppServiceProvider::boot()`, resolving the setting per call:

```php
\Illuminate\Support\Carbon::macro('displayDate', function () {
    /** @var \Carbon\CarbonInterface $this */
    return $this->format(app(\App\Settings\GeneralSettings::class)->date_format);
});

\Illuminate\Support\Carbon::macro('displayDateTime', function () {
    /** @var \Carbon\CarbonInterface $this */
    return $this->format(app(\App\Settings\GeneralSettings::class)->date_format.' H:i');
});
```

- `displayDate()` → the configured date format (default `'d M Y'`).
- `displayDateTime()` → the configured date format + `' H:i'`.
- Registered on `Illuminate\Support\Carbon` (Laravel's date class, returned by model casts). A test verifies the macro also resolves on a `Carbon\Carbon::parse(...)` value; if it does not (separate macro registry), the implementation also registers on `Carbon\Carbon` and `Carbon\CarbonImmutable`. The macros must be callable on every date instance the retrofitted call sites use.
- `GeneralSettings` carries PHP defaults (Cycle 2a), so the macro never crashes on a fresh DB with no settings rows.

## The Retrofit

### Mechanical (sed) — unambiguous display family, in `app/Http/Controllers`

| From | To |
|---|---|
| `->format('d M Y')` | `->displayDate()` |
| `->format('d F, Y')` | `->displayDate()` |
| `->format('d M, Y')` | `->displayDate()` |
| `->format('d F Y')` | `->displayDate()` |
| `->format('d M Y H:i')` | `->displayDateTime()` |

The sed skips commented lines (`/^[[:space:]]*\/\//!`). Null-safe `?->format(...)` becomes `?->displayDate()` automatically (only the `->format('...')` segment is replaced).

Plus the one model accessor `app/Models/InstitutionPerson.php:698` (`->format('d M Y')` → `->displayDate()`).

### Manual audit — the 28 `'Y-m-d'` controller sites

Read each of the 28 `->format('Y-m-d')` sites under `app/Http/Controllers` and classify:
- **Display** — the formatted value is rendered to a human (an Inertia prop shown in the UI, or a Blade/view string). → `->displayDate()`.
- **Functional** — the value is a form-input default/value, a query/`whereDate`/filter key, an API/JSON field consumed programmatically, an array key, or used in a comparison/computation. → **leave unchanged**.

The per-site classification is made at implementation time and surfaced in the spec-compliance review (each retrofitted `Y-m-d` site must be justified as display; each left site noted as functional).

### Explicitly out of scope (left unchanged)

- `app/Exports` (machine-readable spreadsheet cells, incl. its `'Y-m-d'` sites).
- The 3 `'d M Y H:i:s'` sites (forensic/audit precision — seconds retained).
- Every functional `'Y-m-d'`.

## Default-Behavior Note

`date_format` defaults to `'d M Y'`, so the ~94 `'d M Y'` sites produce **identical output** after the retrofit — existing tests asserting `'d M Y'`-formatted strings stay green. Only the `'d F, Y'` / `'d M, Y'` / `'d F Y'` sites and the audited display-`'Y-m-d'` sites change appearance (unifying to the default `'d M Y'`). Tests asserting those specific formats are updated to the unified default.

## Testing

- `tests/Feature/DisplayDateMacroTest.php`:
  - `displayDate()` on a known Carbon returns the configured format; changing `GeneralSettings::date_format` (e.g. to `'Y-m-d'`) changes the output.
  - `displayDateTime()` returns the configured format plus `' H:i'`.
  - The macro resolves both on a model-cast date (e.g. `$person->retirement_date->displayDate()`) and on `\Illuminate\Support\Carbon::parse('2024-06-28')->displayDate()`.
- A representative endpoint test: a controller that renders a date (e.g. user roles `start_date`, currently `->format('d M Y')`) returns the configured format and changes when the setting changes.
- Broad suite sweep in the Sail container (`docker exec hr-laravel.test-1 php artisan test`) to catch any test asserting a now-unified date string; update those expectations to the configured default.

## Out of Scope / Follow-up

- **2c-2 (frontend):** a JS date util translating the `date_format` setting (PHP tokens) to date-fns, plus retrofit of frontend display dates. Its own brainstorm → spec → plan.
- Exports, functional `Y-m-d`, and `H:i:s` forensic timestamps remain as-is.
