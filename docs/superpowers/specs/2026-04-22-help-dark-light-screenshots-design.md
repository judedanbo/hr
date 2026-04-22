# Help Screenshots: Dark/Light Mode Support

**Date:** 2026-04-22
**Status:** Approved

## Problem

All help screenshots are captured in a single mode (currently appears dark). Users in light mode see dark-themed screenshots, and vice versa. The screenshots should match the user's active display mode for visual consistency.

## Solution

Maintain two sets of screenshots (light and dark), stored in subdirectories. The HelpController post-processes rendered HTML to embed both paths as data attributes. The Vue frontend reactively swaps image sources based on the user's current dark mode setting.

## Screenshot Storage

```
docs/screenshots/
├── dark/
│   ├── login-page.png
│   ├── dashboard.png
│   ├── notification-bell.png
│   ├── notifications-page.png
│   ├── staff-directory.png
│   ├── advanced-search.png
│   ├── my-profile.png
│   ├── profile-photo-card.png
│   ├── qualifications-card.png
│   ├── create-staff.png
│   ├── photo-approvals.png
│   ├── unit-details.png
│   ├── qualifications-kpi-dashboard.png
│   ├── qualifications-charts.png
│   └── qualifications-export.png
└── light/
    └── (same 15 filenames)
```

Existing images in `docs/screenshots/` are moved to `docs/screenshots/dark/`.

The symlink `public/help-screenshots → ../docs/screenshots` remains unchanged. Both subdirectories are web-accessible at `/help-screenshots/dark/` and `/help-screenshots/light/`.

## Dusk Test Changes (`HelpScreenshotTest.php`)

### Mode Detection

Read `SCREENSHOT_MODE` environment variable, defaulting to `light`:

```php
protected string $screenshotMode;

protected function setUp(): void
{
    parent::setUp();
    $this->screenshotMode = env('SCREENSHOT_MODE', 'light');
    $this->user = User::where('email', 'screenshots@help.test')->firstOrFail();
}
```

### Dark Mode Injection

When `$screenshotMode` is `dark`, inject the `dark` class on the `<html>` element before capturing. Implement as a helper method called before each screenshot:

```php
protected function applyMode(Browser $browser): Browser
{
    if ($this->screenshotMode === 'dark') {
        $browser->script("document.documentElement.classList.add('dark')");
        $browser->pause(300); // allow CSS transition
    }

    return $browser;
}
```

Each test method calls `$this->applyMode($browser)` after navigation and waiting, before taking the screenshot.

### Screenshot Output Directory

Override the screenshot storage path so files land in a mode-specific subdirectory:

```php
protected function storeScreenshotsIn(): string
{
    return base_path('tests/Browser/screenshots/' . $this->screenshotMode);
}
```

This produces:
- `tests/Browser/screenshots/light/*.png`
- `tests/Browser/screenshots/dark/*.png`

### Usage

```bash
# Light mode (default)
php artisan dusk --filter=HelpScreenshotTest

# Dark mode
SCREENSHOT_MODE=dark php artisan dusk --filter=HelpScreenshotTest
```

## Artisan Command Changes (`CopyHelpScreenshots.php`)

Update `help:copy-screenshots` to handle both mode subdirectories:

- Source: `tests/Browser/screenshots/{mode}/` for each mode that has files
- Destination: `docs/screenshots/{mode}/`
- Iterate over `['light', 'dark']`, skip modes where the source directory is empty or missing
- Ensure destination subdirectories exist before copying
- Report counts per mode

## HelpController Changes

After converting markdown to HTML via `Str::markdown()`, post-process the HTML string to transform help screenshot image tags.

### Transformation

Find: `<img src="/help-screenshots/filename.png" ...>`

Replace with: `<img src="/help-screenshots/light/filename.png" data-light-src="/help-screenshots/light/filename.png" data-dark-src="/help-screenshots/dark/filename.png" ...>`

Implementation: Use `preg_replace` to match `<img` tags with `src="/help-screenshots/..."` and rewrite the `src` while adding the two data attributes.

Only images under `/help-screenshots/` are transformed. Other images (if any) are left untouched.

The default `src` is set to the light variant, serving as a no-JS fallback.

## Vue Frontend Changes (`Help/Index.vue`)

### Dark Mode Detection

```javascript
import { useDark } from "@vueuse/core";
const isDark = useDark();
```

### Image Source Swapping

Create a function that queries all `img[data-light-src]` elements within the content area and sets their `src` to the appropriate data attribute:

```javascript
function swapScreenshots() {
    const container = document.querySelector('[data-help-content]');
    if (!container) return;

    const attr = isDark.value ? 'data-dark-src' : 'data-light-src';
    container.querySelectorAll('img[data-light-src]').forEach(img => {
        img.src = img.getAttribute(attr);
    });
}
```

### Reactive Triggers

- **On `isDark` change:** Watch `isDark` and call `swapScreenshots()` with `nextTick`
- **On content change:** Watch `displayHtml` and call `swapScreenshots()` with `nextTick` (since `v-html` replaces DOM nodes, newly rendered images need their `src` set correctly)
- **On mount:** Call `swapScreenshots()` via `nextTick` in `onMounted`

Add `data-help-content` attribute to the `<article>` element for targeted querying.

## Markdown Files

No changes needed. Markdown continues to reference `![Alt](/help-screenshots/filename.png)`. The HelpController handles path rewriting at render time.

## Migration of Existing Screenshots

One-time manual step:

1. Create `docs/screenshots/dark/` directory
2. Move all existing `.png` files from `docs/screenshots/` into `docs/screenshots/dark/`
3. Generate light mode screenshots and place in `docs/screenshots/light/`

## Full Workflow

```bash
# 1. Generate light mode screenshots (default)
php artisan dusk --filter=HelpScreenshotTest

# 2. Generate dark mode screenshots
SCREENSHOT_MODE=dark php artisan dusk --filter=HelpScreenshotTest

# 3. Copy both sets to docs/
php artisan help:copy-screenshots

# 4. Verify
ls docs/screenshots/light/
ls docs/screenshots/dark/
```

## Files Modified

| File | Change |
|------|--------|
| `tests/Browser/HelpScreenshotTest.php` | Add mode env var, `applyMode()` helper, output directory override |
| `app/Console/Commands/CopyHelpScreenshots.php` | Handle light/dark subdirectories |
| `app/Http/Controllers/HelpController.php` | Post-process HTML to add data attributes |
| `resources/js/Pages/Help/Index.vue` | Add `useDark` watcher, image source swapping logic |
| `docs/screenshots/dark/` | Existing images moved here |
| `docs/screenshots/light/` | New light mode images (generated via Dusk) |

## Out of Scope

- Automated CI pipeline for screenshot generation
- Screenshot diffing or visual regression testing
- Lazy loading or progressive image loading
- Image compression or format optimization (WebP)
