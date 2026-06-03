# Date-Format Retrofit — Frontend (Cycle 2c-2) Design

**Date:** 2026-06-03
**Status:** Approved for planning
**Cycle:** 2c-2 of the app-settings effort (frontend half). Pairs with 2c-1 (backend, merged in #48). Consumes the `app.date_format` shared Inertia prop from Cycle 2a.

## Goal

Make frontend **display** dates follow the configured `date_format` setting by translating the PHP format string to date-fns in JS and applying it through a shared composable. Functional date formatting (date-input `yyyy-MM-dd` values, number `toLocaleString`, date math) is left untouched.

## New Component

**`resources/js/composables/useDateFormat.js`** exports two things:

### `phpToDateFns(phpFormat)` — pure translator

Maps PHP date tokens to date-fns tokens, character by character:

| PHP | date-fns | | PHP | date-fns |
|---|---|---|---|---|
| `d` | `dd` | | `Y` | `yyyy` |
| `j` | `d` | | `y` | `yy` |
| `D` | `EEE` | | `H` | `HH` |
| `l` | `EEEE` | | `G` | `H` |
| `m` | `MM` | | `h` | `hh` |
| `n` | `M` | | `g` | `h` |
| `M` | `MMM` | | `i` | `mm` |
| `F` | `MMMM` | | `s` | `ss` |
| | | | `A` / `a` | `a` |

Rules:
- Non-alphabetic characters (spaces, `,`, `-`, `/`, `:`) pass through unchanged.
- A PHP backslash-escaped char (`\X`) becomes a date-fns escaped literal.
- Any alphabetic character NOT in the map is wrapped in single quotes (date-fns escapes literals that way) so `format()` never throws on an unknown token.
- Example: `'d M Y'` → `'dd MMM yyyy'`; `'d F Y'` → `'dd MMMM yyyy'`; `'Y-m-d'` → `'yyyy-MM-dd'`.

### `useDateFormat()` — composable

```js
import { usePage } from "@inertiajs/vue3";
import { format, parseISO, isValid } from "date-fns";

export function phpToDateFns(phpFormat) { /* the translator above */ }

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

- Reads the reactive `app.date_format` shared prop; falls back to `'d M Y'`.
- `formatDate(value)` accepts an ISO string or a `Date`, returns `''` for null/invalid.

## The Retrofit (~24 display sites)

Per-site edits (not a blind sed): each affected component imports the composable (`const { formatDate } = useDateFormat()`) and swaps the display expression.

- **date-fns display family (~19):** `format(new Date(x), 'dd MMMM, yyyy' | 'd MMMM, yyyy' | 'EEEE dd MMMM, yyyy')` → `formatDate(x)`. Where date-fns `format` is then unused in the file, drop it from the import.
- **`toLocaleDateString` DOB (5):** `new Date(dob).toLocaleDateString('en-GB', {...})` → `formatDate(dob)` (files: `Components/Staff/ActiveFilters.vue`, `Pages/Person/Address.vue`, `Pages/Staff/NewShow.vue`, `Pages/Staff/Notes.vue`, `Pages/Notes/Qualifications.vue`).

### Left alone
- date-fns `'yyyy-MM-dd'` (37 sites) — date-input values, must stay machine format.
- Number `toLocaleString` (85) — not dates.
- `parseISO`/`addDays`/`subYears`/`format` used for date math or input binding, not display.

## Testing

No JS unit test (no vitest in the repo, per the chosen approach). Verification:
- `npm run build` compiles cleanly.
- One **Dusk browser test** (`tests/Browser/DateFormatTest.php`) that sets `date_format` to 2–3 values and asserts a frontend-rendered display date matches:
  - `'Y-m-d'` → a known DOB renders as e.g. `2024-06-28`.
  - `'d F Y'` → renders as `28 June 2024`.
  - (default `'d M Y'` → `28 Jun 2024`.)
  This exercises the translator's main tokens (`d`, `M`/`F`, `Y`, `m`) end-to-end through the UI. Run via the Sail/selenium setup (pause Vite, serve built assets — see the project memory on Dusk).

## Default-Behavior Note

Default `date_format='d M Y'` → date-fns `dd MMM yyyy`. The current display sites use `dd MMMM, yyyy` (full month + comma), so by default the displayed dates change appearance (e.g. `28 June, 2024` → `28 Jun 2024`), unifying with the backend (2c-1).

## Out of Scope

- Functional `yyyy-MM-dd` date inputs, number `toLocaleString`, date math.
- This completes the date-format retrofit (2c). No further 2c cycles planned.
