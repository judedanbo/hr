<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SettingsControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_settings_dashboard(): void
    {
        $admin = User::factory()->create();
        $admin->givePermissionTo(['view admin settings', 'view user activity']);

        User::factory()->count(3)->create();
        Role::firstOrCreate(['name' => 'reviewer']);

        $response = $this->actingAs($admin)->get(route('settings.index'));

        $response->assertOk();
        $response->assertInertia(
            fn (Assert $page) => $page
                ->component('Settings/Index')
                ->where('stats.users', User::count())
                ->where('stats.roles', Role::count())
                ->where('stats.permissions', Permission::count())
                ->has('stats.staff')
                ->has('stats.hrUser')
                ->has('stats.auditLogs')
                ->has('stats.institutions')
                ->has('recentActivity')
        );
    }

    public function test_recent_activity_is_empty_without_activity_permission(): void
    {
        $admin = User::factory()->create();
        $admin->givePermissionTo('view admin settings'); // no 'view user activity'

        $response = $this->actingAs($admin)->get(route('settings.index'));

        $response->assertOk();
        $response->assertInertia(
            fn (Assert $page) => $page
                ->component('Settings/Index')
                ->where('recentActivity', [])
        );
    }

    public function test_settings_denied_without_permission(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->from('/dashboard')
            ->get(route('settings.index'));

        $response->assertRedirect('/dashboard');
        $response->assertSessionHas('error');
    }
}
