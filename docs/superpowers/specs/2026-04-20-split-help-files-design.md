# Split Help Files Design Spec

**Date:** 2026-04-20
**Approach:** Server-side split with client-side tabs, search, and highlighting
**Scope:** Break HELP.md into 20 individual files, rebuild the Help page with tabbed navigation and full-text search

---

## Context

The help system currently consists of a single `docs/HELP.md` (1,030 lines, 20 sections) rendered as one long HTML page. As the documentation grows, this becomes harder to maintain and navigate. Splitting into individual files improves maintainability, and adding tabs with search improves the user experience.

**Current architecture:**
- `docs/HELP.md` — single markdown file
- `HelpController::index()` — reads file, converts to HTML via `Str::markdown()`, passes to Inertia
- `Help/Index.vue` — renders HTML in a `<article>` with Tailwind prose styling
- Route: `GET /help` → `help.index` (auth + password_changed middleware)

## Design

### 1. File Structure

Split `docs/HELP.md` into 20 individual files in `docs/help/`:

```
docs/help/
├── 01-getting-started.md
├── 02-user-roles.md
├── 03-dashboard-navigation.md
├── 04-notifications.md
├── 05-staff-directory.md
├── 06-my-profile.md
├── 07-staff-management.md
├── 08-photo-approvals.md
├── 09-units-departments.md
├── 10-ranks-job-categories.md
├── 11-staff-transitions.md
├── 12-qualifications-reports.md
├── 13-reports-exports.md
├── 14-user-management.md
├── 15-data-integrity.md
├── 16-common-tasks.md
├── 17-faq.md
├── 18-getting-help.md
├── 19-keyboard-shortcuts.md
└── 20-glossary.md
```

**Naming convention:** `{order}-{slug}.md`. Numeric prefix controls display order. Slug becomes the URL hash fragment.

**Content rules:**
- Each file contains only its section content
- Title comes from the first `## Heading` in each file
- No Table of Contents section (tabs replace it)
- No footer/version info (moved to the Vue page)
- Image references use `/help-screenshots/filename.png` (absolute paths)

The original `docs/HELP.md` is deleted after the split.

### 2. Screenshot Path Fix

Create a symlink so screenshots are web-accessible:

```
public/help-screenshots → ../../docs/screenshots
```

Update all image references in the split markdown files from `screenshots/filename.png` to `/help-screenshots/filename.png`.

### 3. Backend — HelpController

The controller reads all `.md` files from `docs/help/`, sorts by filename, extracts metadata, converts to HTML, and passes everything to the Vue page.

```php
public function index(): Response
{
    $files = glob(base_path('docs/help/*.md'));
    sort($files);

    $sections = collect($files)->map(function ($file) {
        $markdown = file_get_contents($file);
        $slug = preg_replace('/^\d+-/', '', pathinfo($file, PATHINFO_FILENAME));

        // Extract title from first ## heading
        preg_match('/^##\s+(.+)$/m', $markdown, $matches);
        $title = $matches[1] ?? Str::headline($slug);

        return [
            'slug'  => $slug,
            'title' => $title,
            'html'  => Str::markdown($markdown),
        ];
    })->values()->all();

    return Inertia::render('Help/Index', [
        'sections' => $sections,
    ]);
}
```

**Key points:**
- Single route stays as `GET /help` → `help.index` (unchanged)
- No new routes needed
- Prop changes from `content` (string) to `sections` (array of `{slug, title, html}`)
- `sort($files)` ensures numeric prefix ordering

### 4. Frontend — Help/Index.vue

Complete redesign of the page with three main UI elements:

#### 4a. Search Box

- Text input at the top of the page, full width
- Debounced (300ms) to avoid jank
- When the user types:
  - Filters the tab list to tabs whose title or content contains the search term (case-insensitive)
  - Highlights matching text in the active section with `<mark>` tags
- Clearing the input restores all tabs and removes highlights
- Search operates on raw text (HTML tags stripped) to avoid matching tag attributes

#### 4b. Tab Bar

- Horizontal scrollable row of tab buttons below the search box
- Each tab shows the section title
- Active tab styled with green underline/background (matches existing app theme)
- Clicking a tab switches the visible content instantly (no page reload)
- When search is active:
  - Only matching tabs are visible
  - Each visible tab shows a match count badge (e.g., "Notifications (3)")
  - If the active tab is filtered out, auto-switch to the first visible tab
- Overflow: horizontal scroll with `overflow-x-auto`, subtle scroll indicators

#### 4c. Content Area

- Displays the HTML of the active section
- Same Tailwind `prose` styling as current page:
  `prose prose-lg dark:prose-invert max-w-none prose-headings:text-gray-900 dark:prose-headings:text-gray-100 prose-a:text-green-600 dark:prose-a:text-green-400 prose-code:text-green-600 dark:prose-code:text-green-400`
- When search is active, matching terms are highlighted with:
  `<mark class="bg-yellow-200 dark:bg-yellow-700 rounded px-0.5">`
- Screenshots render inline via absolute `/help-screenshots/` paths

#### 4d. URL Hash

- Active tab slug is reflected in URL hash: `/help#notifications`
- On page load, if a hash is present, activate the matching tab
- Browser back/forward navigates between viewed tabs
- Page refresh preserves the active tab
- Default (no hash): first tab (Getting Started)

### 5. Search Implementation

All client-side, no server round-trips.

**Tab filtering:**
- Strip HTML from each section's `html` to get plain text
- Check if the search term exists in the title or plain text (case-insensitive)
- Hide non-matching tabs

**Content highlighting:**
- On the active section's HTML, use a regex to find the search term within text nodes only (not inside HTML tags)
- Wrap matches with `<mark>` element
- Computed property — does not mutate the original HTML data

**Match counting:**
- Count occurrences of the search term in each section's plain text
- Display as badge on the tab: `"Notifications (3)"`

### 6. Version Footer

The current footer (`Last Updated: April 2026 / Version 2026.04`) moves from the markdown into the Vue page template as a static element below the content area. This avoids duplicating it across 20 files.

### 7. What Stays the Same

- Route: `GET /help` → `help.index` — unchanged
- Sidebar navigation link in `NewNav.vue` — no changes
- Auth middleware (`auth`, `password_changed`) — unchanged
- Breadcrumbs — unchanged

### 8. Testing

**Feature test:** `GET /help` returns 200 with `sections` prop containing 20 items, each with `slug`, `title`, and `html` keys.

**Dusk test update:** The existing `HelpScreenshotTest` does not test the help page itself, so no Dusk changes needed. A future pass could add a help page screenshot showing the tabbed layout.

## Out of Scope

- Per-section URLs (`/help/notifications`) — not needed since all content loads on one page
- Server-side search API — all search is client-side
- Help page screenshot in Dusk tests — can be added later
- Role-based content filtering — all users see all sections
