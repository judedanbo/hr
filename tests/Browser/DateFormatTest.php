<?php

namespace Tests\Browser;

use App\Models\Person;
use App\Models\User;
use App\Settings\GeneralSettings;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class DateFormatTest extends DuskTestCase
{
    public function test_frontend_renders_dates_in_configured_format(): void
    {
        $admin = User::where('email', 'richard.brobbey@audit.gov.gh')->firstOrFail();

        $settings = app(GeneralSettings::class);
        $original = $settings->date_format;

        $surname = 'Zdatefmt' . now()->timestamp;

        $person = Person::factory()->create([
            'surname' => $surname,
            'date_of_birth' => '2024-06-28',
        ]);

        $search = '?search=' . $surname;

        try {
            $settings->date_format = 'd F Y';
            $settings->save();

            $this->browse(fn (Browser $b) => $b->loginAs($admin)
                ->visit('/person' . $search)
                ->waitForText($surname)
                ->waitForText('28 June 2024')
                ->assertSee('28 June 2024'));

            $settings->date_format = 'Y-m-d';
            $settings->save();

            $this->browse(fn (Browser $b) => $b->loginAs($admin)
                ->visit('/person' . $search)
                ->waitForText($surname)
                ->waitForText('2024-06-28')
                ->assertSee('2024-06-28'));
        } finally {
            $settings->date_format = $original;
            $settings->save();

            $person->forceDelete();
        }
    }
}
