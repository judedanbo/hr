<?php

namespace Tests\Browser;

use App\Models\User;
use App\Settings\GeneralSettings;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class AppSettingsTest extends DuskTestCase
{
    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::where('email', 'richard.brobbey@audit.gov.gh')->firstOrFail();
    }

    public function test_app_settings_frontend_flow(): void
    {
        $original = app(GeneralSettings::class)->org_name;

        try {
            $this->browse(function (Browser $browser) {
                // 1. Settings hub shows the Application card
                $browser->loginAs($this->admin)
                    ->visit('/settings')
                    ->waitForText('Settings')
                    ->assertSee('Application')
                    ->screenshot('app-settings-1-hub');

                // 2. App settings form renders with sections + the current org name
                $browser->visit('/settings/app')
                    ->waitForText('Application settings')
                    ->assertSee('Branding')
                    ->assertSee('Display')
                    ->assertSee('Security')
                    ->assertInputValue('@org_name', 'HRMIS')
                    ->screenshot('app-settings-2-form');

                // 3. Edit + save persists, and the sidebar branding updates
                $browser->clear('@org_name')
                    ->type('@org_name', 'HRMIS QA')
                    ->clear('@pagination_size')
                    ->type('@pagination_size', '25')
                    ->click('@save')
                    ->waitForText('HRMIS QA')
                    ->assertInputValue('@org_name', 'HRMIS QA')
                    ->screenshot('app-settings-3-saved');

                // 4. Validation error surfaces on invalid input.
                // Use keyboard select-all + delete so Vue's v-model picks up
                // the change (Dusk clear() does not fire the input event).
                $browser->click('@org_name')
                    ->keys('@org_name', ['{control}', 'a'], '{delete}')
                    ->click('@save')
                    ->waitForText('required')
                    ->screenshot('app-settings-4-validation');
            });
        } finally {
            // Restore the original org name so the dev DB is left unchanged.
            $settings = app(GeneralSettings::class);
            $settings->org_name = $original;
            $settings->save();
        }
    }
}
