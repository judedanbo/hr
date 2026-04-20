# Split Help Files Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Split the monolithic HELP.md into 20 individual markdown files and rebuild the Help page with tabbed navigation, full-text search, and content highlighting.

**Architecture:** Split markdown files on disk in `docs/help/`. The controller reads all files, converts to HTML, passes an array to a single Vue page. The Vue page renders a search box, horizontal scrollable tabs, and the active section content — all client-side with no page reloads.

**Tech Stack:** Laravel (HelpController), Vue 3 (Help/Index.vue), Tailwind CSS, Inertia.js

**Spec:** `docs/superpowers/specs/2026-04-20-split-help-files-design.md`

---

## File Structure

| File | Responsibility |
|------|---------------|
| `docs/help/01-getting-started.md` through `docs/help/20-glossary.md` | 20 individual help sections |
| `app/Http/Controllers/HelpController.php` | Read all markdown files, convert to HTML, pass sections array to Inertia |
| `resources/js/Pages/Help/Index.vue` | Tabbed help page with search box, tab bar, content area, URL hash sync |
| `public/help-screenshots` | Symlink to `../../docs/screenshots` |
| `tests/Feature/HelpPageTest.php` | Feature test for the help page sections prop |

---

### Task 1: Create docs/help/ directory and split HELP.md

**Files:**
- Create: `docs/help/01-getting-started.md` through `docs/help/20-glossary.md` (20 files)
- Delete: `docs/HELP.md`

- [ ] **Step 1: Create the docs/help directory**

```bash
mkdir -p docs/help
```

- [ ] **Step 2: Split HELP.md into individual files**

Read `docs/HELP.md` and split it into 20 files. Each file gets the content between its `## Heading` and the next `## Heading` (or end of file). Do NOT include the Table of Contents section or the intro paragraph or the footer — those are handled by the Vue page now.

The section boundaries in the current file (line numbers):

| File | Section Title | Start Line | End Before Line |
|------|-------------|------------|-----------------|
| `01-getting-started.md` | Getting Started | 31 (## Getting Started) | 63 (## User Roles) |
| `02-user-roles.md` | User Roles Overview | 63 | 111 |
| `03-dashboard-navigation.md` | Dashboard & Navigation | 111 | 151 |
| `04-notifications.md` | Notifications | 151 | 190 |
| `05-staff-directory.md` | Staff Directory | 190 | 242 |
| `06-my-profile.md` | My Profile | 242 | 340 |
| `07-staff-management.md` | Staff Management | 340 | 441 |
| `08-photo-approvals.md` | Photo Approvals | 441 | 477 |
| `09-units-departments.md` | Units & Departments | 477 | 517 |
| `10-ranks-job-categories.md` | Ranks & Job Categories | 517 | 546 |
| `11-staff-transitions.md` | Staff Transitions | 546 | 617 |
| `12-qualifications-reports.md` | Qualifications Reports | 617 | 694 |
| `13-reports-exports.md` | Reports & Exports | 694 | 757 |
| `14-user-management.md` | User Management | 757 | 814 |
| `15-data-integrity.md` | Data Integrity | 814 | 858 |
| `16-common-tasks.md` | Common Tasks | 858 | 903 |
| `17-faq.md` | Frequently Asked Questions | 903 | 957 |
| `18-getting-help.md` | Getting Help | 957 | 996 |
| `19-keyboard-shortcuts.md` | Keyboard Shortcuts | 996 | 1007 |
| `20-glossary.md` | Glossary | 1007 | 1026 |

For each file:
1. Extract the content from the start line to (end before line - 1)
2. Include the `## Heading` line
3. Remove any trailing `---` separator at the end of the section (these were section dividers in the monolithic file)
4. Replace all `(screenshots/` with `(/help-screenshots/` in image references

Use a script or manual extraction. Each resulting file should start with its `## Heading` and contain only that section's content.

- [ ] **Step 3: Verify all 20 files exist and have content**

```bash
ls docs/help/*.md | wc -l
```

Expected: `20`

```bash
for f in docs/help/*.md; do echo "$(basename $f): $(wc -l < $f) lines"; done
```

Verify each file has a reasonable number of lines (no empty files, no file containing the entire document).

- [ ] **Step 4: Verify image references are updated**

```bash
grep -r "screenshots/" docs/help/ | grep -v "help-screenshots"
```

Expected: No output (all references should use `/help-screenshots/`).

- [ ] **Step 5: Delete the original HELP.md**

```bash
rm docs/HELP.md
```

- [ ] **Step 6: Commit**

```bash
git add docs/help/ && git rm docs/HELP.md
git commit -m "docs: split HELP.md into 20 individual section files"
```

---

### Task 2: Create screenshot symlink

**Files:**
- Create: `public/help-screenshots` (symlink)

- [ ] **Step 1: Create the symlink**

On Windows (requires admin or developer mode):

```bash
cd public && mklink /D help-screenshots ..\\docs\\screenshots
```

Or using Git bash relative symlink:

```bash
cd public && ln -s ../docs/screenshots help-screenshots
```

Verify:

```bash
ls public/help-screenshots/
```

Expected: Lists the PNG screenshot files.

- [ ] **Step 2: Add symlink to git**

```bash
git add public/help-screenshots
git commit -m "chore: add symlink for help screenshots"
```

---

### Task 3: Update HelpController

**Files:**
- Modify: `app/Http/Controllers/HelpController.php`

- [ ] **Step 1: Write the feature test**

Create `tests/Feature/HelpPageTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HelpPageTest extends TestCase
{
    public function test_help_page_returns_sections_array(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/help');

        $response->assertStatus(200);
        $response->assertInertia(function ($page) {
            $page->component('Help/Index')
                ->has('sections', 20)
                ->has('sections.0', function ($section) {
                    $section->has('slug')
                        ->has('title')
                        ->has('html');
                });
        });
    }

    public function test_help_sections_are_ordered(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/help');

        $response->assertInertia(function ($page) {
            $page->has('sections', 20);

            $sections = $page->toArray()['props']['sections'];
            $this->assertEquals('getting-started', $sections[0]['slug']);
            $this->assertEquals('glossary', $sections[19]['slug']);
        });
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

```bash
php artisan test --filter=HelpPageTest
```

Expected: FAIL — the controller still returns `content` string, not `sections` array.

- [ ] **Step 3: Update the controller**

Replace the contents of `app/Http/Controllers/HelpController.php` with:

```php
<?php

namespace App\Http\Controllers;

use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class HelpController extends Controller
{
    public function index(): Response
    {
        $files = glob(base_path('docs/help/*.md'));
        sort($files);

        $sections = collect($files)->map(function ($file) {
            $markdown = file_get_contents($file);
            $slug = preg_replace('/^\d+-/', '', pathinfo($file, PATHINFO_FILENAME));

            preg_match('/^##\s+(.+)$/m', $markdown, $matches);
            $title = $matches[1] ?? Str::headline($slug);

            return [
                'slug' => $slug,
                'title' => $title,
                'html' => Str::markdown($markdown),
            ];
        })->values()->all();

        return Inertia::render('Help/Index', [
            'sections' => $sections,
        ]);
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

```bash
php artisan test --filter=HelpPageTest
```

Expected: 2 tests pass.

- [ ] **Step 5: Commit**

```bash
git add app/Http/Controllers/HelpController.php tests/Feature/HelpPageTest.php
git commit -m "feat: update HelpController to serve split markdown sections"
```

---

### Task 4: Rebuild Help/Index.vue

**Files:**
- Modify: `resources/js/Pages/Help/Index.vue`

This is the largest task — a full rewrite of the Vue page.

- [ ] **Step 1: Replace Help/Index.vue with the new implementation**

Replace the entire contents of `resources/js/Pages/Help/Index.vue` with:

```vue
<script setup>
import { ref, computed, watch, onMounted } from "vue";
import { Head } from "@inertiajs/vue3";
import NewAuthenticated from "@/Layouts/NewAuthenticated.vue";
import BreadCrump from "@/Components/BreadCrump.vue";
import { MagnifyingGlassIcon, XMarkIcon } from "@heroicons/vue/24/outline";

const props = defineProps({
    sections: {
        type: Array,
        required: true,
    },
});

const breadcrumbs = [{ name: "Help", url: route("help.index") }];

// --- State ---
const searchQuery = ref("");
const activeSlug = ref(props.sections[0]?.slug ?? "");
let debounceTimer = null;
const debouncedQuery = ref("");

// --- Plain text cache for search ---
const sectionPlainText = computed(() =>
    props.sections.map((s) => ({
        slug: s.slug,
        text: s.html.replace(/<[^>]*>/g, " ").toLowerCase(),
        title: s.title.toLowerCase(),
    }))
);

// --- Search debounce ---
watch(searchQuery, (val) => {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => {
        debouncedQuery.value = val.trim();
    }, 300);
});

// --- Filtered sections ---
const filteredSections = computed(() => {
    const q = debouncedQuery.value.toLowerCase();
    if (!q) return props.sections;

    return props.sections.filter((s) => {
        const pt = sectionPlainText.value.find((p) => p.slug === s.slug);
        return pt && (pt.title.includes(q) || pt.text.includes(q));
    });
});

// --- Match counts per section ---
const matchCounts = computed(() => {
    const q = debouncedQuery.value.toLowerCase();
    if (!q) return {};

    const counts = {};
    sectionPlainText.value.forEach((pt) => {
        let count = 0;
        let idx = 0;
        while ((idx = pt.text.indexOf(q, idx)) !== -1) {
            count++;
            idx += q.length;
        }
        if (pt.title.includes(q)) count++;
        counts[pt.slug] = count;
    });
    return counts;
});

// --- Active section with highlighted content ---
const activeSection = computed(() => {
    return props.sections.find((s) => s.slug === activeSlug.value) ?? props.sections[0];
});

const displayHtml = computed(() => {
    if (!activeSection.value) return "";
    const html = activeSection.value.html;
    const q = debouncedQuery.value.trim();
    if (!q) return html;

    // Highlight search terms in text nodes only (not inside HTML tags)
    const escaped = q.replace(/[.*+?^${}()|[\]\\]/g, "\\$&");
    const regex = new RegExp(`(>)([^<]*?)(${escaped})([^<]*?)(<)`, "gi");

    let result = html;
    // Multiple passes to catch overlapping matches in same text node
    for (let i = 0; i < 3; i++) {
        result = result.replace(regex, (match, open, before, term, after, close) => {
            return `${open}${before}<mark class="bg-yellow-200 dark:bg-yellow-700 rounded px-0.5">${term}</mark>${after}${close}`;
        });
    }
    return result;
});

// --- Auto-switch tab when filtered out ---
watch(filteredSections, (filtered) => {
    if (filtered.length > 0 && !filtered.find((s) => s.slug === activeSlug.value)) {
        activeSlug.value = filtered[0].slug;
    }
});

// --- URL hash sync ---
function updateHash() {
    window.location.hash = activeSlug.value;
}

watch(activeSlug, updateHash);

onMounted(() => {
    const hash = window.location.hash.slice(1);
    if (hash && props.sections.find((s) => s.slug === hash)) {
        activeSlug.value = hash;
    }

    window.addEventListener("hashchange", () => {
        const h = window.location.hash.slice(1);
        if (h && props.sections.find((s) => s.slug === h)) {
            activeSlug.value = h;
        }
    });
});

function selectTab(slug) {
    activeSlug.value = slug;
    searchQuery.value = "";
    debouncedQuery.value = "";
}

function clearSearch() {
    searchQuery.value = "";
    debouncedQuery.value = "";
}
</script>

<template>
    <Head title="Help & Documentation" />

    <NewAuthenticated>
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                    <BreadCrump :links="breadcrumbs" />
                    <h1 class="text-3xl font-bold text-gray-900 dark:text-gray-50 my-4">
                        Help & Documentation
                    </h1>
                </div>
            </div>

            <!-- Search Box -->
            <div class="mt-4 relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <MagnifyingGlassIcon class="h-5 w-5 text-gray-400" />
                </div>
                <input
                    v-model="searchQuery"
                    type="text"
                    placeholder="Search help topics..."
                    class="block w-full pl-10 pr-10 py-3 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 placeholder-gray-400 focus:ring-2 focus:ring-green-500 focus:border-green-500 sm:text-sm"
                />
                <button
                    v-if="searchQuery"
                    @click="clearSearch"
                    class="absolute inset-y-0 right-0 pr-3 flex items-center"
                >
                    <XMarkIcon class="h-5 w-5 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300" />
                </button>
            </div>

            <!-- Tab Bar -->
            <div class="mt-4 overflow-x-auto border-b border-gray-200 dark:border-gray-700">
                <nav class="flex gap-1 min-w-max px-1" aria-label="Help topics">
                    <button
                        v-for="section in filteredSections"
                        :key="section.slug"
                        @click="selectTab(section.slug)"
                        :class="[
                            'whitespace-nowrap px-4 py-2.5 text-sm font-medium rounded-t-lg transition-colors',
                            activeSlug === section.slug
                                ? 'bg-white dark:bg-gray-800 text-green-600 dark:text-green-400 border border-b-0 border-gray-200 dark:border-gray-700 -mb-px'
                                : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700/50',
                        ]"
                    >
                        {{ section.title }}
                        <span
                            v-if="debouncedQuery && matchCounts[section.slug]"
                            class="ml-1.5 inline-flex items-center rounded-full bg-green-100 dark:bg-green-900 px-2 py-0.5 text-xs font-medium text-green-700 dark:text-green-300"
                        >
                            {{ matchCounts[section.slug] }}
                        </span>
                    </button>
                </nav>
            </div>

            <!-- No results -->
            <div
                v-if="filteredSections.length === 0"
                class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-b-lg p-12 text-center"
            >
                <p class="text-gray-500 dark:text-gray-400 text-lg">
                    No help topics match "<strong>{{ debouncedQuery }}</strong>"
                </p>
                <button
                    @click="clearSearch"
                    class="mt-4 text-green-600 dark:text-green-400 hover:underline text-sm"
                >
                    Clear search
                </button>
            </div>

            <!-- Content Area -->
            <div
                v-else
                class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-b-lg"
            >
                <div class="p-6">
                    <article
                        class="prose prose-lg dark:prose-invert max-w-none prose-headings:text-gray-900 dark:prose-headings:text-gray-100 prose-a:text-green-600 dark:prose-a:text-green-400 prose-code:text-green-600 dark:prose-code:text-green-400"
                        v-html="displayHtml"
                    ></article>
                </div>
            </div>

            <!-- Footer -->
            <div class="mt-4 mb-8 text-center text-sm text-gray-400 dark:text-gray-500">
                <p>Last Updated: April 2026 &middot; HR Management System &middot; Version 2026.04</p>
            </div>
        </div>
    </NewAuthenticated>
</template>
```

- [ ] **Step 2: Build frontend to check for compile errors**

```bash
npm run build
```

Expected: Build succeeds with no errors.

- [ ] **Step 3: Commit**

```bash
git add resources/js/Pages/Help/Index.vue
git commit -m "feat: rebuild Help page with tabbed navigation and search"
```

---

### Task 5: Run Pint and verify

**Files:**
- Possibly modify: any PHP files touched

- [ ] **Step 1: Run Laravel Pint**

```bash
./vendor/bin/pint --dirty
```

If any files were reformatted, commit:

```bash
git add -u
git commit -m "style: format with Pint"
```

- [ ] **Step 2: Run the feature test**

```bash
php artisan test --filter=HelpPageTest
```

Expected: 2 tests pass.

- [ ] **Step 3: Run the full test suite**

```bash
php artisan test
```

Expected: All tests pass (no regressions).

---

### Task 6: Manual verification and final commit

**Files:**
- Read: all split files, the Vue page, the controller

- [ ] **Step 1: Verify the help page works in the browser**

Start the dev server (`php artisan serve` + `npm run dev`) and navigate to `http://127.0.0.1:8000/help`.

Verify:
1. All 20 tabs appear in the tab bar
2. Clicking tabs switches content instantly
3. The search box filters tabs as you type
4. Search highlights matching text within the content
5. Match count badges appear on tabs during search
6. URL hash updates when switching tabs (e.g., `#notifications`)
7. Navigating directly to `/help#my-profile` loads the correct tab
8. Screenshots render correctly in sections that have them
9. Dark mode styling works
10. "No results" state appears for nonsense searches
11. Clearing search restores all tabs

- [ ] **Step 2: Fix any issues found**

If any issues are found, fix them and commit:

```bash
git add -u
git commit -m "fix: address help page review issues"
```

- [ ] **Step 3: Verify no broken references**

```bash
grep -r "HELP.md" app/ resources/ routes/ tests/ 2>/dev/null
```

Expected: No references to the old `docs/HELP.md` remain in application code. (Note: spec and plan files in `docs/superpowers/` may reference it historically — that's fine.)
