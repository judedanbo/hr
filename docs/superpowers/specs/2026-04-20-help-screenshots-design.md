# Help Screenshots Capture Design Spec

**Date:** 2026-04-20
**Approach:** Laravel Dusk automated browser screenshots
**Scope:** Capture all 15 screenshots referenced in docs/HELP.md

---

## Context

The help documentation (`docs/HELP.md`) references 15 screenshots — 6 from the original doc and 9 added for new features. The actual image files don't exist yet. We need a reproducible way to capture them so they can be regenerated when the UI changes.

**Prerequisites:**
- Dusk is NOT currently installed (needs `composer require laravel/dusk --dev`)
- The development database has realistic demo data (seeded)
- 16 model factories exist, including Qualification with `approved()`/`pending()` states
- Notifications use Laravel's built-in database notification system
- Photo storage uses `Person.image`, `Person.pending_image` columns

## Screenshots to Capture

| # | Filename | Page / Action | Required State |
|---|----------|--------------|----------------|
| 1 | `login-page.png` | `/login` (unauthenticated) | None |
| 2 | `dashboard.png` | `/dashboard` | Authenticated |
| 3 | `notification-bell.png` | Click bell, wait for dropdown | Unread notifications exist |
| 4 | `notifications-page.png` | `/notifications` | Mix of read/unread notifications |
| 5 | `staff-directory.png` | `/staff` | Staff data exists |
| 6 | `advanced-search.png` | `/staff`, open advanced panel | Staff data exists |
| 7 | `my-profile.png` | `/my-profile` | User linked to populated Person |
| 8 | `profile-photo-card.png` | `/my-profile`, scroll to photo | Person has pending_image set |
| 9 | `qualifications-card.png` | `/my-profile`, scroll to qualifications | Person has 2-3 qualifications |
| 10 | `create-staff.png` | `/staff/create` | Permission to create staff |
| 11 | `photo-approvals.png` | `/staff-photo-approvals` | 2-3 staff with pending photos |
| 12 | `unit-details.png` | Unit show page | Unit with staff assigned |
| 13 | `qualifications-kpi-dashboard.png` | `/qualifications/reports` | Qualification data exists |
| 14 | `qualifications-charts.png` | `/qualifications/reports`, scroll to charts | Same |
| 15 | `qualifications-export.png` | `/qualifications/reports`, click export dropdown | Same |

## Components

### 1. HelpScreenshotSeeder

**Purpose:** Create transient state needed for screenshots against existing demo data.

**Location:** `database/seeders/HelpScreenshotSeeder.php`

**What it creates:**

1. **Screenshot user** — finds or creates a user with email `screenshots@help.test`, linked to an existing Person with a populated staff record. Assigns `super-administrator` role. Sets `password_changed_at` so password-change middleware doesn't redirect.

2. **Pending photo state** — sets `pending_image` on the screenshot user's Person (for My Profile photo card), plus 2 additional existing staff Persons (for Photo Approvals queue). Uses a placeholder image path.

3. **Notifications** — creates 5-6 database notifications for the screenshot user:
   - 2 unread: PhotoApprovedNotification, QualificationPendingApprovalNotification
   - 2 read: PhotoRejectedNotification, PhotoPendingApprovalNotification
   - This ensures bell badge shows a count and the notifications page has filterable mixed data

4. **Qualifications** — ensures the screenshot user's Person has 2-3 qualifications using existing factory states: `approved()`, `pending()`, `approved()`.

5. **Contacts & address** — ensures Person has at least one phone and one email contact, plus an address record, so My Profile cards display populated data.

**What it does NOT create:** Staff, units, ranks, institutions, job categories — those exist in the demo data.

**Idempotency:** The seeder should be safe to re-run. Check for existing `screenshots@help.test` user before creating. Use `firstOrCreate` patterns.

### 2. HelpScreenshotTest

**Purpose:** Dusk test class that captures all 15 screenshots.

**Location:** `tests/Browser/HelpScreenshotTest.php`

**Structure:** One public test method per screenshot. All methods use the `screenshots@help.test` super-administrator user, except `testLoginPage` which is unauthenticated.

**Viewport:** 1440x900 for all screenshots.

**Methods:**

```
testLoginPage()           → visit /login, screenshot
testDashboard()           → loginAs, visit /dashboard, pause for charts, screenshot
testNotificationBell()    → loginAs, click bell icon, waitFor dropdown, screenshot
testNotificationsPage()   → loginAs, visit /notifications, screenshot
testStaffDirectory()      → loginAs, visit /staff, waitFor table, screenshot
testAdvancedSearch()      → loginAs, visit /staff, click advanced search button, waitFor panel, screenshot
testMyProfile()           → loginAs, visit /my-profile, waitFor cards, screenshot
testProfilePhotoCard()    → loginAs, visit /my-profile, scroll to photo card, screenshot
testQualificationsCard()  → loginAs, visit /my-profile, scroll to qualifications, screenshot
testCreateStaffForm()     → loginAs, visit /staff/create, screenshot
testPhotoApprovals()      → loginAs, visit /staff-photo-approvals, waitFor table, screenshot
testUnitDetails()         → loginAs, visit a unit show page (pick first unit with staff), screenshot
testQualificationsKpi()   → loginAs, visit /qualifications/reports, waitFor stat cards, screenshot
testQualificationsCharts()→ loginAs, visit /qualifications/reports, scroll to charts, pause, screenshot
testQualificationsExport()→ loginAs, visit /qualifications/reports, click export dropdown, screenshot
```

**Screenshot output:** `tests/Browser/screenshots/{name}.png` (Dusk default location).

### 3. Copy Command

**Purpose:** Copy captured screenshots from Dusk output to docs directory.

**Location:** `app/Console/Commands/CopyHelpScreenshots.php`

**Signature:** `php artisan help:copy-screenshots`

**Behavior:**
1. Read all `.png` files from `tests/Browser/screenshots/`
2. Create `docs/screenshots/` if it doesn't exist
3. Copy each file, overwriting existing
4. Output summary: "Copied N screenshots to docs/screenshots/"

### 4. Dusk Installation & Configuration

**Steps:**
1. `composer require laravel/dusk --dev`
2. `php artisan dusk:install`
3. Add `tests/Browser/screenshots/` to `.gitignore`
4. Track `docs/screenshots/` in git (committed documentation images)
5. Configure DuskTestCase if needed (viewport, Chrome options)

## Workflow

```bash
# One-time setup
composer require laravel/dusk --dev
php artisan dusk:install

# Capture screenshots (repeatable)
php artisan db:seed --class=HelpScreenshotSeeder
php artisan dusk --filter=HelpScreenshotTest
php artisan help:copy-screenshots

# Commit the screenshots
git add docs/screenshots/
git commit -m "docs: add help documentation screenshots"
```

## Out of Scope

- Image optimization/compression (can be done later)
- Dark mode variants
- Mobile/responsive screenshots
- Automated screenshot updates in CI/CD
