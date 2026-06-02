<?php

namespace Tests\Feature;

use App\Models\User;
use App\Settings\GeneralSettings;
use Carbon\Carbon as BaseCarbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class DisplayDateMacroTest extends TestCase
{
    use RefreshDatabase;

    public function test_display_date_uses_configured_default_format(): void
    {
        $date = Carbon::parse('2024-06-28');
        $this->assertSame('28 Jun 2024', $date->displayDate());
    }

    public function test_display_date_reflects_a_changed_setting(): void
    {
        $settings = app(GeneralSettings::class);
        $settings->date_format = 'Y-m-d';
        $settings->save();

        $this->assertSame('2024-06-28', Carbon::parse('2024-06-28')->displayDate());
    }

    public function test_display_datetime_appends_time(): void
    {
        $date = Carbon::parse('2024-06-28 14:30:00');
        $this->assertSame('28 Jun 2024 14:30', $date->displayDateTime());
    }

    public function test_macro_resolves_on_a_model_cast_date(): void
    {
        $user = User::factory()->create(['created_at' => '2024-06-28 09:00:00']);
        $this->assertSame('28 Jun 2024', $user->created_at->displayDate());
    }

    public function test_macro_resolves_on_base_carbon_parse(): void
    {
        $this->assertSame('28 Jun 2024', BaseCarbon::parse('2024-06-28')->displayDate());
    }
}
