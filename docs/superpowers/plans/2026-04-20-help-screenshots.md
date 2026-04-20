# Help Screenshots Capture Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build a reproducible Dusk-based screenshot capture system that generates all 15 help documentation images.

**Architecture:** Install Laravel Dusk, create a seeder for transient screenshot state (notifications, pending photos, qualifications), a Dusk test class with 15 capture methods, and an artisan command to copy screenshots to `docs/screenshots/`. The development database already has realistic demo data.

**Tech Stack:** Laravel Dusk, ChromeDriver, PHPUnit, existing model factories

**Spec:** `docs/superpowers/specs/2026-04-20-help-screenshots-design.md`

---

## File Structure

| File | Responsibility |
|------|---------------|
| `database/seeders/HelpScreenshotSeeder.php` | Create transient state (screenshot user, notifications, pending photos, qualifications) |
| `tests/Browser/HelpScreenshotTest.php` | 15 Dusk test methods, one per screenshot |
| `app/Console/Commands/CopyHelpScreenshots.php` | Copy screenshots from Dusk output to `docs/screenshots/` |
| `resources/js/Components/NotificationBell.vue` | Add `dusk="notification-bell-button"` attribute (minor) |
| `resources/js/Components/Staff/AdvancedSearchPanel.vue` | Add `dusk="advanced-search-toggle"` attribute (minor) |

---

### Task 1: Install and Configure Laravel Dusk

**Files:**
- Modify: `composer.json` (via composer require)
- Create: `tests/Browser/` directory structure (via dusk:install)
- Create: `tests/DuskTestCase.php` (via dusk:install)
- Modify: `.gitignore`

- [ ] **Step 1: Install Dusk**

```bash
composer require laravel/dusk --dev
```

- [ ] **Step 2: Run Dusk install**

```bash
php artisan dusk:install
```

This creates:
- `tests/DuskTestCase.php`
- `tests/Browser/` directory
- `tests/Browser/screenshots/` directory
- `tests/Browser/console/` directory
- `tests/Browser/ExampleTest.php`

- [ ] **Step 3: Add Dusk screenshots to .gitignore**

Open `.gitignore` and add at the end:

```
# Dusk screenshots (build artifacts)
tests/Browser/screenshots/
tests/Browser/console/
```

- [ ] **Step 4: Delete the example test**

Delete the auto-generated `tests/Browser/ExampleTest.php` — we'll create our own.

```bash
rm tests/Browser/ExampleTest.php
```

- [ ] **Step 5: Create docs/screenshots directory**

```bash
mkdir -p docs/screenshots
```

- [ ] **Step 6: Verify Dusk works**

```bash
php artisan dusk --browse
```

Expected: Chrome opens briefly and closes. If ChromeDriver version mismatch, run:

```bash
php artisan dusk:chrome-driver --detect
```

- [ ] **Step 7: Commit**

```bash
git add composer.json composer.lock tests/DuskTestCase.php .gitignore docs/screenshots/
git commit -m "chore: install Laravel Dusk for screenshot automation"
```

---

### Task 2: Add Dusk Selectors to Vue Components

**Files:**
- Modify: `resources/js/Components/NotificationBell.vue`
- Modify: `resources/js/Components/Staff/AdvancedSearchPanel.vue`

Dusk uses `dusk="name"` attributes to reliably target elements. We need two.

- [ ] **Step 1: Add dusk attribute to NotificationBell**

Read `resources/js/Components/NotificationBell.vue` and find the `<PopoverButton>` element. It has classes like `relative -m-2.5 flex items-center p-2.5`. Add a `dusk` attribute to it:

Find:
```vue
<PopoverButton
```

Add `dusk="notification-bell-button"` as an attribute on that element. The result should look like:

```vue
<PopoverButton
    dusk="notification-bell-button"
```

(Keep all existing classes and content unchanged.)

- [ ] **Step 2: Add dusk attribute to AdvancedSearchPanel**

Read `resources/js/Components/Staff/AdvancedSearchPanel.vue` and find the `<DisclosureButton>` element that contains "Advanced Search" text. Add a `dusk` attribute:

Find:
```vue
<DisclosureButton
```

Add `dusk="advanced-search-toggle"` as an attribute. The result should look like:

```vue
<DisclosureButton
    dusk="advanced-search-toggle"
```

(Keep all existing classes and content unchanged.)

- [ ] **Step 3: Commit**

```bash
git add resources/js/Components/NotificationBell.vue resources/js/Components/Staff/AdvancedSearchPanel.vue
git commit -m "chore: add dusk selectors for screenshot automation"
```

---

### Task 3: Create HelpScreenshotSeeder

**Files:**
- Create: `database/seeders/HelpScreenshotSeeder.php`

- [ ] **Step 1: Generate the seeder**

```bash
php artisan make:seeder HelpScreenshotSeeder --no-interaction
```

- [ ] **Step 2: Write the seeder**

Replace the contents of `database/seeders/HelpScreenshotSeeder.php` with:

```php
<?php

namespace Database\Seeders;

use App\Models\Address;
use App\Models\Contact;
use App\Models\InstitutionPerson;
use App\Models\Person;
use App\Models\Qualification;
use App\Models\User;
use App\Notifications\PhotoApprovedNotification;
use App\Notifications\PhotoPendingApprovalNotification;
use App\Notifications\PhotoRejectedNotification;
use App\Notifications\QualificationPendingApprovalNotification;
use Illuminate\Database\Seeder;

class HelpScreenshotSeeder extends Seeder
{
    public function run(): void
    {
        $user = $this->createScreenshotUser();
        $person = $user->person;

        $this->seedNotifications($user, $person);
        $this->seedPendingPhoto($person);
        $this->seedPhotoApprovalQueue();
        $this->seedQualifications($person);
        $this->seedContacts($person);
        $this->seedAddress($person);
    }

    private function createScreenshotUser(): User
    {
        // Find an existing Person with a staff record, or create one
        $person = Person::whereHas('institutionPerson')->first();

        if (! $person) {
            $person = Person::factory()->create();
            InstitutionPerson::factory()->create(['person_id' => $person->id]);
        }

        $user = User::firstOrCreate(
            ['email' => 'screenshots@help.test'],
            [
                'name' => $person->full_name ?? 'Screenshot User',
                'person_id' => $person->id,
                'password' => bcrypt('screenshot-password'),
                'email_verified_at' => now(),
                'password_change_at' => now(),
            ]
        );

        // Ensure user is linked to the person
        if (! $user->person_id) {
            $user->update(['person_id' => $person->id]);
        }

        // Assign super-administrator role
        if (! $user->hasRole('super-administrator')) {
            $user->assignRole('super-administrator');
        }

        return $user->fresh('person');
    }

    private function seedNotifications(User $user, Person $person): void
    {
        // Clear existing notifications for this user
        $user->notifications()->delete();

        // Unread notifications
        $user->notify(new PhotoApprovedNotification($person));
        $user->notify(new QualificationPendingApprovalNotification($person));

        // Mark older ones as read for a mix
        $user->notify(new PhotoRejectedNotification($person));
        $user->notify(new PhotoPendingApprovalNotification($person));

        // Mark the last two as read
        $user->notifications()
            ->latest()
            ->take(2)
            ->get()
            ->each(fn ($n) => $n->markAsRead());
    }

    private function seedPendingPhoto(Person $person): void
    {
        // Set a pending photo on the screenshot user's person
        $person->update([
            'pending_image' => 'photos/sample-pending.jpg',
            'pending_image_at' => now()->subHours(3),
        ]);
    }

    private function seedPhotoApprovalQueue(): void
    {
        // Set pending photos on 2 other staff for the approvals table
        $staffPersons = Person::whereHas('institutionPerson')
            ->where('id', '!=', User::where('email', 'screenshots@help.test')->value('person_id'))
            ->whereNull('pending_image')
            ->limit(2)
            ->get();

        foreach ($staffPersons as $person) {
            $person->update([
                'pending_image' => 'photos/sample-pending-' . $person->id . '.jpg',
                'pending_image_at' => now()->subHours(rand(1, 48)),
            ]);
        }
    }

    private function seedQualifications(Person $person): void
    {
        // Only add if person has fewer than 2 qualifications
        if ($person->qualifications()->count() >= 2) {
            return;
        }

        Qualification::factory()
            ->approved()
            ->create([
                'person_id' => $person->id,
                'qualification' => 'Bachelor of Science',
                'institution' => 'University of Ghana',
                'course' => 'Computer Science',
                'year' => '2015',
            ]);

        Qualification::factory()
            ->pending()
            ->create([
                'person_id' => $person->id,
                'qualification' => 'Master of Business Administration',
                'institution' => 'Ghana Institute of Management',
                'course' => 'Finance',
                'year' => '2020',
            ]);

        Qualification::factory()
            ->approved()
            ->create([
                'person_id' => $person->id,
                'qualification' => 'Professional Certificate',
                'institution' => 'ICAG',
                'course' => 'Accounting',
                'year' => '2022',
            ]);
    }

    private function seedContacts(Person $person): void
    {
        if ($person->contacts()->count() >= 2) {
            return;
        }

        Contact::firstOrCreate(
            ['person_id' => $person->id, 'contact_type' => 'PHONE'],
            ['contact' => '0244123456', 'valid_end' => null]
        );

        Contact::firstOrCreate(
            ['person_id' => $person->id, 'contact_type' => 'EMAIL'],
            ['contact' => 'staff.member@audit.gov.gh', 'valid_end' => null]
        );
    }

    private function seedAddress(Person $person): void
    {
        if ($person->address()->count() >= 1) {
            return;
        }

        $person->address()->create([
            'address_line_1' => '12 Independence Avenue',
            'city' => 'Accra',
            'region' => 'Greater Accra',
            'country' => 'Ghana',
            'post_code' => 'GA-100',
        ]);
    }
}
```

- [ ] **Step 3: Verify the seeder runs**

```bash
php artisan db:seed --class=HelpScreenshotSeeder
```

Expected: No errors. Verify with tinker:

```bash
php artisan tinker --execute="echo App\Models\User::where('email', 'screenshots@help.test')->first()?->notifications()->count();"
```

Expected output: `4`

- [ ] **Step 4: Commit**

```bash
git add database/seeders/HelpScreenshotSeeder.php
git commit -m "feat: add HelpScreenshotSeeder for screenshot test data"
```

---

### Task 4: Create HelpScreenshotTest

**Files:**
- Create: `tests/Browser/HelpScreenshotTest.php`

- [ ] **Step 1: Create the test file**

Create `tests/Browser/HelpScreenshotTest.php` with the following content:

```php
<?php

namespace Tests\Browser;

use App\Models\Unit;
use App\Models\User;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class HelpScreenshotTest extends DuskTestCase
{
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::where('email', 'screenshots@help.test')->firstOrFail();
    }

    /** @test */
    public function testLoginPage(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/login')
                ->waitForText('Log in')
                ->screenshot('login-page');
        });
    }

    /** @test */
    public function testDashboard(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/dashboard')
                ->pause(2000) // Wait for charts to render
                ->screenshot('dashboard');
        });
    }

    /** @test */
    public function testNotificationBell(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/dashboard')
                ->pause(1000)
                ->click('[dusk="notification-bell-button"]')
                ->pause(1000) // Wait for dropdown and API response
                ->screenshot('notification-bell');
        });
    }

    /** @test */
    public function testNotificationsPage(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/notifications')
                ->waitForText('Notifications')
                ->pause(500)
                ->screenshot('notifications-page');
        });
    }

    /** @test */
    public function testStaffDirectory(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/staff')
                ->waitForText('Staff')
                ->pause(1000) // Wait for table to populate
                ->screenshot('staff-directory');
        });
    }

    /** @test */
    public function testAdvancedSearch(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/staff')
                ->waitForText('Staff')
                ->pause(500)
                ->click('[dusk="advanced-search-toggle"]')
                ->pause(500) // Wait for panel animation
                ->screenshot('advanced-search');
        });
    }

    /** @test */
    public function testMyProfile(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/my-profile')
                ->waitForText('My Profile')
                ->pause(1000)
                ->screenshot('my-profile');
        });
    }

    /** @test */
    public function testProfilePhotoCard(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/my-profile')
                ->waitForText('My Profile')
                ->pause(500)
                ->scrollTo('.photo-card, [class*="photo"], h3:contains("Photo")', 0, -100)
                ->pause(300)
                ->screenshot('profile-photo-card');
        });
    }

    /** @test */
    public function testQualificationsCard(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/my-profile')
                ->waitForText('My Profile')
                ->pause(500)
                ->script('window.scrollTo(0, document.body.scrollHeight * 0.6)');
            $browser->pause(300)
                ->screenshot('qualifications-card');
        });
    }

    /** @test */
    public function testCreateStaffForm(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/staff/create')
                ->waitForText('Create')
                ->pause(500)
                ->screenshot('create-staff');
        });
    }

    /** @test */
    public function testPhotoApprovals(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/staff-photo-approvals')
                ->waitForText('Photo Approvals')
                ->pause(500)
                ->screenshot('photo-approvals');
        });
    }

    /** @test */
    public function testUnitDetails(): void
    {
        $this->browse(function (Browser $browser) {
            // Find a unit that has staff assigned
            $unit = Unit::has('staff')->first()
                ?? Unit::first();

            $browser->loginAs($this->user)
                ->visit('/units/' . $unit->id)
                ->pause(2000) // Wait for charts to render
                ->screenshot('unit-details');
        });
    }

    /** @test */
    public function testQualificationsKpiDashboard(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/qualifications/reports')
                ->waitForText('Qualifications')
                ->pause(2000) // Wait for KPI cards to load
                ->screenshot('qualifications-kpi-dashboard');
        });
    }

    /** @test */
    public function testQualificationsCharts(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/qualifications/reports')
                ->waitForText('Qualifications')
                ->pause(2000)
                ->script('window.scrollTo(0, document.body.scrollHeight * 0.5)');
            $browser->pause(1000) // Wait for charts to render after scroll
                ->screenshot('qualifications-charts');
        });
    }

    /** @test */
    public function testQualificationsExport(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/qualifications/reports')
                ->waitForText('Qualifications')
                ->pause(1000)
                ->click('button[id*="menu-button"], button:contains("Export"), [class*="export"]')
                ->pause(500)
                ->screenshot('qualifications-export');
        });
    }

    protected function driver(): \Facebook\WebDriver\Remote\RemoteWebDriver
    {
        $options = (new \Facebook\WebDriver\Chrome\ChromeOptions)->addArguments([
            '--window-size=1440,900',
            '--force-device-scale-factor=1',
            '--disable-gpu',
        ]);

        return \Facebook\WebDriver\Remote\RemoteWebDriver::create(
            'http://localhost:9515',
            \Facebook\WebDriver\Remote\DesiredCapabilities::chrome()
                ->setCapability(\Facebook\WebDriver\Chrome\ChromeOptions::CAPABILITY, $options)
        );
    }
}
```

- [ ] **Step 2: Verify the test runs for one screenshot**

Make sure the dev server is running (`php artisan serve`) and the seeder has been run, then:

```bash
php artisan dusk --filter=testLoginPage
```

Expected: Chrome opens, captures the login page, test passes. File appears at `tests/Browser/screenshots/login-page.png`.

- [ ] **Step 3: Run all screenshot tests**

```bash
php artisan dusk --filter=HelpScreenshotTest
```

Expected: 15 tests pass, 15 PNG files in `tests/Browser/screenshots/`.

If any test fails due to selectors not matching (the interactive screenshots like bell dropdown, export button), adjust the selectors. Common fixes:
- For the notification bell: ensure the `dusk` attribute from Task 2 is in place
- For the export dropdown: inspect the page in browser DevTools to find the correct button selector
- For scroll-based screenshots: adjust the `scrollTo` offsets or `script` percentages

- [ ] **Step 4: Commit**

```bash
git add tests/Browser/HelpScreenshotTest.php
git commit -m "feat: add Dusk screenshot test for help documentation"
```

---

### Task 5: Create CopyHelpScreenshots Artisan Command

**Files:**
- Create: `app/Console/Commands/CopyHelpScreenshots.php`

- [ ] **Step 1: Generate the command**

```bash
php artisan make:command CopyHelpScreenshots --no-interaction
```

- [ ] **Step 2: Write the command**

Replace the contents of `app/Console/Commands/CopyHelpScreenshots.php` with:

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
        $source = base_path('tests/Browser/screenshots');
        $destination = base_path('docs/screenshots');

        if (! File::isDirectory($source)) {
            $this->error("Source directory not found: {$source}");
            $this->info('Run `php artisan dusk --filter=HelpScreenshotTest` first.');

            return self::FAILURE;
        }

        $files = File::glob("{$source}/*.png");

        if (empty($files)) {
            $this->warn('No screenshots found in ' . $source);

            return self::FAILURE;
        }

        File::ensureDirectoryExists($destination);

        $copied = 0;
        foreach ($files as $file) {
            $filename = basename($file);
            File::copy($file, "{$destination}/{$filename}");
            $this->line("  Copied: {$filename}");
            $copied++;
        }

        $this->info("Copied {$copied} screenshots to docs/screenshots/");

        return self::SUCCESS;
    }
}
```

- [ ] **Step 3: Verify the command works**

```bash
php artisan help:copy-screenshots
```

Expected output:
```
  Copied: login-page.png
  Copied: dashboard.png
  ... (15 files)
Copied 15 screenshots to docs/screenshots/
```

- [ ] **Step 4: Commit**

```bash
git add app/Console/Commands/CopyHelpScreenshots.php
git commit -m "feat: add artisan command to copy help screenshots"
```

---

### Task 6: Run Full Pipeline and Commit Screenshots

**Files:**
- Track: `docs/screenshots/*.png`

- [ ] **Step 1: Run the full pipeline**

```bash
php artisan db:seed --class=HelpScreenshotSeeder
php artisan dusk --filter=HelpScreenshotTest
php artisan help:copy-screenshots
```

- [ ] **Step 2: Verify all 15 screenshots exist**

```bash
ls -la docs/screenshots/
```

Expected: 15 PNG files:
```
login-page.png
dashboard.png
notification-bell.png
notifications-page.png
staff-directory.png
advanced-search.png
my-profile.png
profile-photo-card.png
qualifications-card.png
create-staff.png
photo-approvals.png
unit-details.png
qualifications-kpi-dashboard.png
qualifications-charts.png
qualifications-export.png
```

- [ ] **Step 3: Visually inspect key screenshots**

Open each screenshot and verify:
- `notification-bell.png` — shows dropdown with notifications and badge
- `photo-approvals.png` — shows table with pending photos
- `my-profile.png` — shows card layout with populated data
- `qualifications-kpi-dashboard.png` — shows 4 KPI stat cards with data

If any screenshot looks wrong (empty page, wrong scroll position, missing data), fix the test method and re-run:

```bash
php artisan dusk --filter=testMethodName
php artisan help:copy-screenshots
```

- [ ] **Step 4: Commit screenshots**

```bash
git add docs/screenshots/
git commit -m "docs: add help documentation screenshots"
```

- [ ] **Step 5: Run Laravel Pint**

```bash
./vendor/bin/pint --dirty
```

If any files were reformatted:

```bash
git add -u
git commit -m "style: format new files with Pint"
```
