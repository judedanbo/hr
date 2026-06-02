<?php

namespace Tests\Feature;

use App\Models\User;
use App\Settings\GeneralSettings;
use App\Settings\SecuritySettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
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

    public function test_admin_can_view_app_settings_page(): void
    {
        $admin = User::factory()->create();
        $admin->givePermissionTo('update app settings');

        $response = $this->actingAs($admin)->get(route('app-settings.edit'));

        $response->assertOk();
        $response->assertInertia(
            fn (Assert $page) => $page
                ->component('Settings/App')
                ->where('general.org_name', 'HRMIS')
                ->where('general.pagination_size', 10)
                ->where('security.password_change_interval_days', 90)
        );
    }

    public function test_admin_can_update_app_settings(): void
    {
        $admin = User::factory()->create();
        $admin->givePermissionTo('update app settings');

        $response = $this->actingAs($admin)->put(route('app-settings.update'), [
            'org_name' => 'New Org',
            'support_email' => 'help@example.com',
            'date_format' => 'Y-m-d',
            'pagination_size' => 25,
            'password_change_interval_days' => 30,
        ]);

        $response->assertRedirect(route('app-settings.edit'));
        $response->assertSessionHas('success');

        $this->assertSame('New Org', app(\App\Settings\GeneralSettings::class)->org_name);
        $this->assertSame('help@example.com', app(\App\Settings\GeneralSettings::class)->support_email);
        $this->assertSame('Y-m-d', app(\App\Settings\GeneralSettings::class)->date_format);
        $this->assertSame(25, app(\App\Settings\GeneralSettings::class)->pagination_size);
        $this->assertSame(30, app(\App\Settings\SecuritySettings::class)->password_change_interval_days);
    }

    public function test_update_accepts_null_support_email(): void
    {
        $admin = User::factory()->create();
        $admin->givePermissionTo('update app settings');

        $response = $this->actingAs($admin)->put(route('app-settings.update'), [
            'org_name' => 'New Org',
            'support_email' => null,
            'date_format' => 'Y-m-d',
            'pagination_size' => 25,
            'password_change_interval_days' => 30,
        ]);

        $response->assertRedirect(route('app-settings.edit'));
        $this->assertNull(app(\App\Settings\GeneralSettings::class)->support_email);
    }

    public function test_update_rejects_invalid_input(): void
    {
        $admin = User::factory()->create();
        $admin->givePermissionTo('update app settings');

        $response = $this->actingAs($admin)
            ->from(route('app-settings.edit'))
            ->put(route('app-settings.update'), [
                'org_name' => '',
                'support_email' => 'not-an-email',
                'date_format' => 'd M Y',
                'pagination_size' => 9999,
                'password_change_interval_days' => 30,
            ]);

        $response->assertSessionHasErrors(['org_name', 'support_email', 'pagination_size']);
    }

    public function test_app_settings_require_permission(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('app-settings.edit'))->assertForbidden();
        $this->actingAs($user)->put(route('app-settings.update'), [])->assertForbidden();
    }

    public function test_app_settings_are_shared_to_frontend(): void
    {
        $admin = User::factory()->create();
        $admin->givePermissionTo('update app settings');

        $response = $this->actingAs($admin)->get(route('app-settings.edit'));

        $response->assertInertia(
            fn (Assert $page) => $page
                ->where('app.org_name', 'HRMIS')
                ->where('app.pagination_size', 10)
        );
    }
}
