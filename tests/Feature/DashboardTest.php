<?php

namespace Tests\Feature;

use App\Models\Institution;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_pure_staff_user_without_person_redirects_to_staff_index(): void
    {
        $user = User::factory()->create(['password_change_at' => now()]);
        $user->assignRole('staff');

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertRedirect(route('staff.index'));
    }

    public function test_super_admin_with_institution_redirects_to_institution_show(): void
    {
        Institution::factory()->create();

        $user = User::factory()->create(['password_change_at' => now()]);
        $user->assignRole('super-administrator');

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertRedirect(route('institution.show', [1]));
    }

    public function test_super_admin_without_institution_redirects_to_institution_index(): void
    {
        Institution::query()->delete();

        $user = User::factory()->create(['password_change_at' => now()]);
        $user->assignRole('super-administrator');

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertRedirect(route('institution.index'));
    }

    public function test_user_with_no_roles_redirects_to_staff_index(): void
    {
        $user = User::factory()->create(['password_change_at' => now()]);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertRedirect(route('staff.index'));
    }

    public function test_multi_role_staff_user_sees_chooser_page(): void
    {
        $user = User::factory()->create(['password_change_at' => now()]);
        $user->assignRole(['staff', 'super-administrator']);

        $response = $this->actingAs($user)->get('/dashboard/choose-mode');

        $response->assertInertia(fn ($page) => $page
            ->component('Dashboard/ChooseMode')
            ->has('staffOption')
            ->has('otherOption')
            ->where('staffOption.mode', 'staff')
            ->where('otherOption.mode', 'other')
        );
    }

    public function test_chooser_shows_admin_option_when_user_has_admin_access(): void
    {
        $user = User::factory()->create(['password_change_at' => now()]);
        $user->assignRole(['staff', 'super-administrator']);

        $response = $this->actingAs($user)->get('/dashboard/choose-mode');

        $response->assertInertia(fn ($page) => $page
            ->where('otherOption.label', 'Go to admin dashboard')
        );
    }

    public function test_chooser_shows_staff_list_option_when_user_has_no_admin_access(): void
    {
        $user = User::factory()->create(['password_change_at' => now()]);
        // 'hr-user' is a seeded role without 'view dashboard' permission (confirmed in AuthorizationTest).
        // If hr-user actually has 'view dashboard', pick a different seeded role that lacks it.
        $user->assignRole(['staff', 'hr-user']);

        $response = $this->actingAs($user)->get('/dashboard/choose-mode');

        $response->assertInertia(fn ($page) => $page
            ->where('otherOption.label', 'Go to staff list')
        );
    }

    public function test_chooser_redirects_non_multi_role_user_to_dashboard(): void
    {
        $user = User::factory()->create(['password_change_at' => now()]);
        $user->assignRole('staff');

        $response = $this->actingAs($user)->get('/dashboard/choose-mode');

        $response->assertRedirect(route('dashboard'));
    }

    public function test_switch_mode_to_staff_sets_session_and_redirects_to_dashboard(): void
    {
        $user = User::factory()->create(['password_change_at' => now()]);
        $user->assignRole(['staff', 'super-administrator']);

        $response = $this->actingAs($user)
            ->post('/dashboard/switch-mode', ['mode' => 'staff']);

        $response->assertRedirect(route('dashboard'));
        $this->assertSame('staff', session('view_mode'));
    }

    public function test_switch_mode_to_other_sets_session_and_redirects_to_dashboard(): void
    {
        $user = User::factory()->create(['password_change_at' => now()]);
        $user->assignRole(['staff', 'super-administrator']);

        $response = $this->actingAs($user)
            ->post('/dashboard/switch-mode', ['mode' => 'other']);

        $response->assertRedirect(route('dashboard'));
        $this->assertSame('other', session('view_mode'));
    }

    public function test_switch_mode_rejects_invalid_mode(): void
    {
        $user = User::factory()->create(['password_change_at' => now()]);
        $user->assignRole(['staff', 'super-administrator']);

        $response = $this->actingAs($user)
            ->from('/dashboard/choose-mode')
            ->post('/dashboard/switch-mode', ['mode' => 'bogus']);

        $response->assertSessionHasErrors('mode');
        $this->assertNull(session('view_mode'));
    }

    public function test_switch_mode_rejects_non_multi_role_user(): void
    {
        $user = User::factory()->create(['password_change_at' => now()]);
        $user->assignRole('staff');

        $response = $this->actingAs($user)
            ->post('/dashboard/switch-mode', ['mode' => 'other']);

        $response->assertForbidden();
        $this->assertNull(session('view_mode'));
    }

    public function test_multi_role_staff_without_session_mode_redirects_to_chooser(): void
    {
        $user = User::factory()->create(['password_change_at' => now()]);
        $user->assignRole(['staff', 'super-administrator']);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertRedirect(route('dashboard.choose-mode'));
    }

    public function test_multi_role_staff_with_staff_mode_redirects_to_staff_landing(): void
    {
        $user = User::factory()->create(['password_change_at' => now()]);
        $user->assignRole(['staff', 'super-administrator']);

        $response = $this->actingAs($user)
            ->withSession(['view_mode' => 'staff'])
            ->get('/dashboard');

        // No person attached, so falls through to staff.index.
        $response->assertRedirect(route('staff.index'));
    }

    public function test_multi_role_staff_with_other_mode_as_admin_redirects_to_institution_show(): void
    {
        \App\Models\Institution::factory()->create();

        $user = User::factory()->create(['password_change_at' => now()]);
        $user->assignRole(['staff', 'super-administrator']);

        $response = $this->actingAs($user)
            ->withSession(['view_mode' => 'other'])
            ->get('/dashboard');

        $response->assertRedirect(route('institution.show', [1]));
    }

    public function test_multi_role_staff_with_other_mode_no_admin_redirects_to_staff_index(): void
    {
        $user = User::factory()->create(['password_change_at' => now()]);
        $user->assignRole(['staff', 'hr-user']);

        $response = $this->actingAs($user)
            ->withSession(['view_mode' => 'other'])
            ->get('/dashboard');

        $response->assertRedirect(route('staff.index'));
    }

    public function test_multi_role_staff_with_other_mode_admin_no_institutions_redirects_to_institution_index(): void
    {
        \App\Models\Institution::query()->delete();

        $user = User::factory()->create(['password_change_at' => now()]);
        $user->assignRole(['staff', 'super-administrator']);

        $response = $this->actingAs($user)
            ->withSession(['view_mode' => 'other'])
            ->get('/dashboard');

        $response->assertRedirect(route('institution.index'));
    }

    public function test_inertia_shares_view_mode_props_for_multi_role_staff(): void
    {
        $user = User::factory()->create(['password_change_at' => now()]);
        $user->assignRole(['staff', 'super-administrator']);

        $response = $this->actingAs($user)
            ->withSession(['view_mode' => 'other'])
            ->get('/dashboard/choose-mode');

        $response->assertInertia(fn ($page) => $page
            ->where('auth.viewMode', 'other')
            ->where('auth.isMultiRoleStaff', true)
            ->where('auth.viewModeLabel', 'Admin')
        );
    }

    public function test_inertia_view_mode_label_is_other_when_user_has_no_admin_access(): void
    {
        $user = User::factory()->create(['password_change_at' => now()]);
        $user->assignRole(['staff', 'hr-user']);

        $response = $this->actingAs($user)
            ->get('/dashboard/choose-mode');

        $response->assertInertia(fn ($page) => $page
            ->where('auth.isMultiRoleStaff', true)
            ->where('auth.viewModeLabel', 'Other')
        );
    }
}
