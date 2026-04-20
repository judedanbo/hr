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
                ->waitForText('LOG IN')
                ->screenshot('login-page');
        });
    }

    /** @test */
    public function testDashboard(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/dashboard')
                ->pause(2000)
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
                ->pause(1000)
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
                ->pause(1000)
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
                ->pause(500)
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
                ->script('window.scrollTo(0, 200)');
            $browser->pause(300)
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
                ->pause(2000)
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
        $unit = Unit::has('staff')->first() ?? Unit::first();

        if (! $unit) {
            $this->markTestSkipped('No units in database');
        }

        $this->browse(function (Browser $browser) use ($unit) {
            $browser->loginAs($this->user)
                ->visit('/units/' . $unit->id)
                ->pause(2000)
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
                ->pause(2000)
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
            $browser->pause(1000)
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
                ->screenshot('qualifications-export');
        });
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
            'http://localhost:9515',
            \Facebook\WebDriver\Remote\DesiredCapabilities::chrome()
                ->setCapability(\Facebook\WebDriver\Chrome\ChromeOptions::CAPABILITY, $options)
        );
    }
}
