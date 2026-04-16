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
}
