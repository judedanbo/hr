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
}
