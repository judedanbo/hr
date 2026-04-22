<?php

namespace Tests\Browser;

use App\Models\Unit;
use App\Models\User;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class HelpScreenshotTest extends DuskTestCase
{
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

    public function test_login_page(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/login')
                ->waitForText('LOG IN');
            $this->applyMode($browser);
            $browser->screenshot('login-page');
        });
    }

    public function test_dashboard(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/dashboard')
                ->pause(2000);
            $this->applyMode($browser);
            $browser->screenshot('dashboard');
        });
    }

    public function test_notification_bell(): void
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

    public function test_notifications_page(): void
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

    public function test_staff_directory(): void
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

    public function test_advanced_search(): void
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

    public function test_my_profile(): void
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

    public function test_profile_photo_card(): void
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

    public function test_qualifications_card(): void
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

    public function test_create_staff_form(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/staff/create')
                ->pause(2000);
            $this->applyMode($browser);
            $browser->screenshot('create-staff');
        });
    }

    public function test_photo_approvals(): void
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

    public function test_unit_details(): void
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

    public function test_qualifications_kpi_dashboard(): void
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

    public function test_qualifications_charts(): void
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

    public function test_qualifications_export(): void
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

    protected function applyMode(Browser $browser): Browser
    {
        if ($this->screenshotMode === 'dark') {
            $browser->script("document.documentElement.classList.add('dark')");
            $browser->pause(300);
        }

        return $browser;
    }

    protected function driver(): \Facebook\WebDriver\Remote\RemoteWebDriver
    {
        $options = (new \Facebook\WebDriver\Chrome\ChromeOptions)->addArguments([
            '--window-size=1440,900',
            '--force-device-scale-factor=1',
            '--disable-gpu',
            '--headless=new',
        ]);

        return \Facebook\WebDriver\Remote\RemoteWebDriver::create(
            $_ENV['DUSK_DRIVER_URL'] ?? env('DUSK_DRIVER_URL') ?? 'http://localhost:9515',
            \Facebook\WebDriver\Remote\DesiredCapabilities::chrome()
                ->setCapability(\Facebook\WebDriver\Chrome\ChromeOptions::CAPABILITY, $options)
        );
    }
}
