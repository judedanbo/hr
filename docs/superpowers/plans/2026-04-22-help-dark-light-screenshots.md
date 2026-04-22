# Help Dark/Light Mode Screenshots Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Serve mode-appropriate help screenshots that match the user's current light/dark display setting.

**Architecture:** Existing screenshots move to a `dark/` subdirectory; new light screenshots go in `light/`. HelpController post-processes markdown HTML to add `data-light-src` and `data-dark-src` attributes. Vue frontend reactively swaps image `src` based on `useDark()`.

**Tech Stack:** Laravel (PHP), Dusk (browser tests), Vue 3, VueUse (`useDark`), Tailwind CSS class-based dark mode.

---

### Task 1: Migrate Existing Screenshots to `dark/` Subdirectory

**Files:**
- Move: `docs/screenshots/*.png` to `docs/screenshots/dark/`
- Create: `docs/screenshots/light/` (empty placeholder with `.gitkeep`)

- [ ] **Step 1: Create the dark subdirectory and move existing images**

```bash
mkdir -p docs/screenshots/dark
mv docs/screenshots/*.png docs/screenshots/dark/
```

- [ ] **Step 2: Create the light subdirectory with a gitkeep**

```bash
mkdir -p docs/screenshots/light
touch docs/screenshots/light/.gitkeep
```

- [ ] **Step 3: Verify the symlink still works**

The symlink `public/help-screenshots → ../docs/screenshots` should now expose `/help-screenshots/dark/` and `/help-screenshots/light/` automatically.

```bash
ls -la public/help-screenshots/dark/
ls -la public/help-screenshots/light/
```

Expected: dark directory lists 14 PNG files, light directory shows `.gitkeep`.

- [ ] **Step 4: Commit**

```bash
git add docs/screenshots/dark/ docs/screenshots/light/.gitkeep
git add -u docs/screenshots/  # stages the deletions of root-level PNGs
git commit -m "chore: move existing screenshots to dark/ subdirectory

Prepares the directory structure for dual light/dark screenshot sets.
Light directory created with .gitkeep placeholder."
```

---

### Task 2: Update HelpController to Post-Process Image Tags

**Files:**
- Modify: `app/Http/Controllers/HelpController.php`
- Test: `tests/Feature/HelpControllerTest.php`

- [ ] **Step 1: Write the feature test**

Create the test file:

```bash
php artisan make:test HelpControllerTest --no-interaction
```

Write the test in `tests/Feature/HelpControllerTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HelpControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function testHelpPageTransformsScreenshotImageTags(): void
    {
        $this->seed();
        $user = User::first();

        $response = $this->actingAs($user)->get(route('help.index'));

        $response->assertStatus(200);

        $sections = $response->viewData('page')['props']['sections'];

        // Find a section that has a screenshot reference
        $sectionWithImage = collect($sections)->first(function ($section) {
            return str_contains($section['html'], 'data-light-src');
        });

        $this->assertNotNull($sectionWithImage, 'At least one section should have transformed image tags');

        // Verify the transformed img tag has correct attributes
        $html = $sectionWithImage['html'];
        $this->assertStringContainsString('data-light-src="/help-screenshots/light/', $html);
        $this->assertStringContainsString('data-dark-src="/help-screenshots/dark/', $html);
        $this->assertStringContainsString('src="/help-screenshots/light/', $html);

        // Verify no un-transformed help-screenshots references remain (root-level path)
        $this->assertDoesNotMatchRegularExpression(
            '#src="/help-screenshots/[^ld][^a-z]*\.png"#',
            $html,
            'No root-level screenshot paths should remain'
        );
    }

    /** @test */
    public function testHelpPageDoesNotTransformNonScreenshotImages(): void
    {
        $this->seed();
        $user = User::first();

        $response = $this->actingAs($user)->get(route('help.index'));

        $response->assertStatus(200);

        // The response should load successfully regardless of image content
        $sections = $response->viewData('page')['props']['sections'];
        $this->assertNotEmpty($sections);
    }
}
```

- [ ] **Step 2: Run the test to verify it fails**

```bash
php artisan test --filter=testHelpPageTransformsScreenshotImageTags
```

Expected: FAIL — no `data-light-src` attributes exist yet in the HTML.

- [ ] **Step 3: Implement the post-processing in HelpController**

Modify `app/Http/Controllers/HelpController.php`:

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

            $html = Str::markdown($markdown);
            $html = $this->transformScreenshotPaths($html);

            return [
                'slug' => $slug,
                'title' => $title,
                'html' => $html,
            ];
        })->values()->all();

        return Inertia::render('Help/Index', [
            'sections' => $sections,
        ]);
    }

    /**
     * Transform help screenshot img tags to include light/dark data attributes.
     *
     * Converts: <img src="/help-screenshots/filename.png" alt="..." />
     * Into:     <img src="/help-screenshots/light/filename.png" alt="..."
     *                data-light-src="/help-screenshots/light/filename.png"
     *                data-dark-src="/help-screenshots/dark/filename.png" />
     */
    private function transformScreenshotPaths(string $html): string
    {
        return preg_replace(
            '#<img\s+src="/help-screenshots/([^"/]+\.png)"([^>]*)/?>#',
            '<img src="/help-screenshots/light/$1"$2 data-light-src="/help-screenshots/light/$1" data-dark-src="/help-screenshots/dark/$1" />',
            $html
        );
    }
}
```

- [ ] **Step 4: Run the test to verify it passes**

```bash
php artisan test --filter=HelpControllerTest
```

Expected: Both tests PASS.

- [ ] **Step 5: Run Pint**

```bash
./vendor/bin/pint app/Http/Controllers/HelpController.php tests/Feature/HelpControllerTest.php
```

- [ ] **Step 6: Commit**

```bash
git add app/Http/Controllers/HelpController.php tests/Feature/HelpControllerTest.php
git commit -m "feat: transform help screenshot paths for light/dark mode

HelpController now post-processes markdown HTML to add data-light-src
and data-dark-src attributes to help screenshot images. Default src
points to light variant as no-JS fallback."
```

---

### Task 3: Update Help/Index.vue to Swap Images Based on Dark Mode

**Files:**
- Modify: `resources/js/Pages/Help/Index.vue`

- [ ] **Step 1: Add useDark import and swapScreenshots function**

At the top of the `<script setup>`, add the import:

```javascript
import { ref, computed, watch, onMounted, nextTick } from "vue";
import { useDark } from "@vueuse/core";
```

After the existing `onMounted` block (around line 121), add:

```javascript
// --- Dark/light screenshot swapping ---
const isDark = useDark();

function swapScreenshots() {
    const container = document.querySelector("[data-help-content]");
    if (!container) return;

    const attr = isDark.value ? "data-dark-src" : "data-light-src";
    container.querySelectorAll("img[data-light-src]").forEach((img) => {
        img.src = img.getAttribute(attr);
    });
}

watch(isDark, () => nextTick(swapScreenshots));
watch(displayHtml, () => nextTick(swapScreenshots));
```

Update the existing `onMounted` to also call `swapScreenshots`:

Find the existing `onMounted` block:

```javascript
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
```

Replace with:

```javascript
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

    nextTick(swapScreenshots);
});
```

- [ ] **Step 2: Add `data-help-content` attribute to the article element**

Find in the template:

```html
<article
    class="prose prose-lg dark:prose-invert max-w-none prose-headings:text-gray-900 dark:prose-headings:text-gray-100 prose-a:text-green-600 dark:prose-a:text-green-400 prose-code:text-green-600 dark:prose-code:text-green-400"
    v-html="displayHtml"
></article>
```

Replace with:

```html
<article
    data-help-content
    class="prose prose-lg dark:prose-invert max-w-none prose-headings:text-gray-900 dark:prose-headings:text-gray-100 prose-a:text-green-600 dark:prose-a:text-green-400 prose-code:text-green-600 dark:prose-code:text-green-400"
    v-html="displayHtml"
></article>
```

- [ ] **Step 3: Verify the full file is correct**

The complete `<script setup>` imports should now be:

```javascript
import { ref, computed, watch, onMounted, nextTick } from "vue";
import { Head } from "@inertiajs/vue3";
import { useDark } from "@vueuse/core";
import NewAuthenticated from "@/Layouts/NewAuthenticated.vue";
import BreadCrump from "@/Components/BreadCrump.vue";
import { MagnifyingGlassIcon, XMarkIcon } from "@heroicons/vue/24/outline";
```

- [ ] **Step 4: Build frontend and verify no errors**

```bash
npm run build
```

Expected: Build succeeds with no errors.

- [ ] **Step 5: Commit**

```bash
git add resources/js/Pages/Help/Index.vue
git commit -m "feat: reactively swap help screenshots based on dark mode

Uses useDark() from VueUse to detect mode changes and swap img src
attributes between light and dark variants. Triggers on mode toggle,
content change (tab switch), and initial mount."
```

---

### Task 4: Update Dusk Test for Mode-Aware Screenshot Capture

**Files:**
- Modify: `tests/Browser/HelpScreenshotTest.php`

- [ ] **Step 1: Add mode property and update setUp**

At the top of the class, replace the existing `$user` property and `setUp`:

```php
protected User $user;

protected string $screenshotMode;

protected function setUp(): void
{
    parent::setUp();

    $this->screenshotMode = env('SCREENSHOT_MODE', 'light');

    Browser::$storeScreenshotsAt = base_path('tests/Browser/screenshots/' . $this->screenshotMode);

    if (! is_dir(Browser::$storeScreenshotsAt)) {
        mkdir(Browser::$storeScreenshotsAt, 0755, true);
    }

    $this->user = User::where('email', 'screenshots@help.test')->firstOrFail();
}
```

Add the import for Browser at the top of the file:

```php
use Laravel\Dusk\Browser;
```

Note: `Browser` is already imported. Just ensure it's there.

- [ ] **Step 2: Add applyMode helper method**

Add this method to the class, before the `driver()` method:

```php
protected function applyMode(Browser $browser): Browser
{
    if ($this->screenshotMode === 'dark') {
        $browser->script("document.documentElement.classList.add('dark')");
        $browser->pause(300);
    }

    return $browser;
}
```

- [ ] **Step 3: Update each test method to call applyMode before screenshot**

Update `testLoginPage`:

```php
/** @test */
public function testLoginPage(): void
{
    $this->browse(function (Browser $browser) {
        $browser->visit('/login')
            ->waitForText('LOG IN');
        $this->applyMode($browser);
        $browser->screenshot('login-page');
    });
}
```

Update `testDashboard`:

```php
/** @test */
public function testDashboard(): void
{
    $this->browse(function (Browser $browser) {
        $browser->loginAs($this->user)
            ->visit('/dashboard')
            ->pause(2000);
        $this->applyMode($browser);
        $browser->screenshot('dashboard');
    });
}
```

Update `testNotificationBell`:

```php
/** @test */
public function testNotificationBell(): void
{
    $this->browse(function (Browser $browser) {
        $browser->loginAs($this->user)
            ->visit('/dashboard')
            ->pause(1000)
            ->click('[dusk="notification-bell-button"]')
            ->pause(1000);
        $this->applyMode($browser);
        $browser->screenshot('notification-bell');
    });
}
```

Update `testNotificationsPage`:

```php
/** @test */
public function testNotificationsPage(): void
{
    $this->browse(function (Browser $browser) {
        $browser->loginAs($this->user)
            ->visit('/notifications')
            ->waitForText('Notifications')
            ->pause(500);
        $this->applyMode($browser);
        $browser->screenshot('notifications-page');
    });
}
```

Update `testStaffDirectory`:

```php
/** @test */
public function testStaffDirectory(): void
{
    $this->browse(function (Browser $browser) {
        $browser->loginAs($this->user)
            ->visit('/staff')
            ->waitForText('Staff')
            ->pause(1000);
        $this->applyMode($browser);
        $browser->screenshot('staff-directory');
    });
}
```

Update `testAdvancedSearch`:

```php
/** @test */
public function testAdvancedSearch(): void
{
    $this->browse(function (Browser $browser) {
        $browser->loginAs($this->user)
            ->visit('/staff')
            ->waitForText('Staff')
            ->pause(500)
            ->click('[dusk="advanced-search-toggle"]')
            ->pause(500);
        $this->applyMode($browser);
        $browser->screenshot('advanced-search');
    });
}
```

Update `testMyProfile`:

```php
/** @test */
public function testMyProfile(): void
{
    $this->browse(function (Browser $browser) {
        $browser->loginAs($this->user)
            ->visit('/my-profile')
            ->waitForText('My Profile')
            ->pause(1000);
        $this->applyMode($browser);
        $browser->screenshot('my-profile');
    });
}
```

Update `testProfilePhotoCard`:

```php
/** @test */
public function testProfilePhotoCard(): void
{
    $this->browse(function (Browser $browser) {
        $browser->loginAs($this->user)
            ->visit('/my-profile')
            ->waitForText('My Profile')
            ->pause(500)
            ->script('window.scrollTo(0, 200)');
        $browser->pause(300);
        $this->applyMode($browser);
        $browser->screenshot('profile-photo-card');
    });
}
```

Update `testQualificationsCard`:

```php
/** @test */
public function testQualificationsCard(): void
{
    $this->browse(function (Browser $browser) {
        $browser->loginAs($this->user)
            ->visit('/my-profile')
            ->waitForText('My Profile')
            ->pause(500)
            ->script('window.scrollTo(0, document.body.scrollHeight * 0.6)');
        $browser->pause(300);
        $this->applyMode($browser);
        $browser->screenshot('qualifications-card');
    });
}
```

Update `testCreateStaffForm`:

```php
/** @test */
public function testCreateStaffForm(): void
{
    $this->browse(function (Browser $browser) {
        $browser->loginAs($this->user)
            ->visit('/staff/create')
            ->pause(2000);
        $this->applyMode($browser);
        $browser->screenshot('create-staff');
    });
}
```

Update `testPhotoApprovals`:

```php
/** @test */
public function testPhotoApprovals(): void
{
    $this->browse(function (Browser $browser) {
        $browser->loginAs($this->user)
            ->visit('/staff-photo-approvals')
            ->waitForText('Photo Approvals')
            ->pause(500);
        $this->applyMode($browser);
        $browser->screenshot('photo-approvals');
    });
}
```

Update `testUnitDetails`:

```php
/** @test */
public function testUnitDetails(): void
{
    $unit = Unit::has('staff')->first() ?? Unit::first();

    if (! $unit) {
        $this->markTestSkipped('No units in database');
    }

    $this->browse(function (Browser $browser) use ($unit) {
        $browser->loginAs($this->user)
            ->visit('/units/' . $unit->id)
            ->pause(2000);
        $this->applyMode($browser);
        $browser->screenshot('unit-details');
    });
}
```

Update `testQualificationsKpiDashboard`:

```php
/** @test */
public function testQualificationsKpiDashboard(): void
{
    $this->browse(function (Browser $browser) {
        $browser->loginAs($this->user)
            ->visit('/qualifications/reports')
            ->waitForText('Qualifications')
            ->pause(2000);
        $this->applyMode($browser);
        $browser->screenshot('qualifications-kpi-dashboard');
    });
}
```

Update `testQualificationsCharts`:

```php
/** @test */
public function testQualificationsCharts(): void
{
    $this->browse(function (Browser $browser) {
        $browser->loginAs($this->user)
            ->visit('/qualifications/reports')
            ->waitForText('Qualifications')
            ->pause(2000)
            ->script('window.scrollTo(0, document.body.scrollHeight * 0.5)');
        $browser->pause(1000);
        $this->applyMode($browser);
        $browser->screenshot('qualifications-charts');
    });
}
```

Update `testQualificationsExport`:

```php
/** @test */
public function testQualificationsExport(): void
{
    $this->browse(function (Browser $browser) {
        $browser->loginAs($this->user)
            ->visit('/qualifications/reports')
            ->waitForText('Qualifications')
            ->pause(1000);
        $this->applyMode($browser);
        $browser->screenshot('qualifications-export');
    });
}
```

- [ ] **Step 4: Keep the custom `driver()` method as-is**

The test overrides `driver()` with `1440x900` window size and specific Chrome options. Leave this method unchanged — it defines the screenshot capture dimensions intentionally.

- [ ] **Step 5: Run Pint**

```bash
./vendor/bin/pint tests/Browser/HelpScreenshotTest.php
```

- [ ] **Step 6: Commit**

```bash
git add tests/Browser/HelpScreenshotTest.php
git commit -m "feat: support SCREENSHOT_MODE env var in Dusk screenshot tests

Tests now read SCREENSHOT_MODE (default: light) to determine output
directory and whether to inject dark class on HTML element. Enables
capturing both light and dark mode screenshot sets from same test class."
```

---

### Task 5: Update CopyHelpScreenshots Artisan Command

**Files:**
- Modify: `app/Console/Commands/CopyHelpScreenshots.php`

- [ ] **Step 1: Write the updated command**

Replace the full contents of `app/Console/Commands/CopyHelpScreenshots.php`:

```php
<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class CopyHelpScreenshots extends Command
{
    protected $signature = 'help:copy-screenshots';

    protected $description = 'Copy Dusk screenshots to docs/screenshots for help documentation';

    public function handle(): int
    {
        $baseSource = base_path('tests/Browser/screenshots');
        $baseDestination = base_path('docs/screenshots');
        $totalCopied = 0;

        foreach (['light', 'dark'] as $mode) {
            $source = "{$baseSource}/{$mode}";
            $destination = "{$baseDestination}/{$mode}";

            if (! File::isDirectory($source)) {
                $this->warn("Skipping {$mode} mode: source directory not found ({$source})");

                continue;
            }

            $files = File::glob("{$source}/*.png");

            if (empty($files)) {
                $this->warn("Skipping {$mode} mode: no screenshots found");

                continue;
            }

            File::ensureDirectoryExists($destination);

            $this->info("Copying {$mode} mode screenshots:");
            foreach ($files as $file) {
                $filename = basename($file);
                File::copy($file, "{$destination}/{$filename}");
                $this->line("  {$filename}");
                $totalCopied++;
            }
        }

        if ($totalCopied === 0) {
            $this->error('No screenshots were copied. Run Dusk tests first:');
            $this->line('  php artisan dusk --filter=HelpScreenshotTest');
            $this->line('  SCREENSHOT_MODE=dark php artisan dusk --filter=HelpScreenshotTest');

            return self::FAILURE;
        }

        $this->newLine();
        $this->info("Copied {$totalCopied} screenshots total to docs/screenshots/");

        return self::SUCCESS;
    }
}
```

- [ ] **Step 2: Run Pint**

```bash
./vendor/bin/pint app/Console/Commands/CopyHelpScreenshots.php
```

- [ ] **Step 3: Commit**

```bash
git add app/Console/Commands/CopyHelpScreenshots.php
git commit -m "feat: update help:copy-screenshots for light/dark subdirectories

Command now iterates over light and dark subdirectories, skipping any
mode where the source directory is missing or empty. Reports counts
per mode and provides usage guidance when no screenshots are found."
```

---

### Task 6: Manual Verification and Final Commit

- [ ] **Step 1: Build frontend assets**

```bash
npm run build
```

Expected: Build succeeds.

- [ ] **Step 2: Verify help page loads correctly**

Start the dev server and navigate to `/help`. Verify:
- Help page loads without errors
- Images display correctly (they will show light variants by default, which will be the `.gitkeep` placeholder until light screenshots are generated — that's expected)
- Toggle dark mode — images should swap to dark variants (the existing screenshots)
- Switch tabs — images in new tab should have correct mode
- Search — highlighted results should not break image attributes

```bash
php artisan serve
npm run dev
```

Visit: `http://localhost:8000/help`

- [ ] **Step 3: Run the full test suite**

```bash
php artisan test
```

Expected: All tests pass.

- [ ] **Step 4: Run Pint on all changed files**

```bash
./vendor/bin/pint --dirty
```

- [ ] **Step 5: Final commit if any formatting fixes**

```bash
git add -u
git commit -m "style: apply Pint formatting to changed files"
```
