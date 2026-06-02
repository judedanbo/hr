<?php

namespace Tests\Feature;

use App\Settings\GeneralSettings;
use App\Settings\SecuritySettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class AppSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_settings_have_seeded_defaults(): void
    {
        $general = app(GeneralSettings::class);
        $security = app(SecuritySettings::class);

        $this->assertSame('HRMIS', $general->org_name);
        $this->assertNull($general->support_email);
        $this->assertSame('d M Y', $general->date_format);
        $this->assertSame(10, $general->pagination_size);
        $this->assertSame(90, $security->password_change_interval_days);
    }

    public function test_update_app_settings_permission_is_seeded(): void
    {
        $this->assertTrue(
            Permission::where('name', 'update app settings')->exists(),
            "Permission 'update app settings' should be seeded"
        );
    }
}
