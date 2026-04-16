<?php

namespace Tests\Unit;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserMultiRoleTest extends TestCase
{
    use RefreshDatabase;

    public function test_is_multi_role_staff_returns_true_for_staff_plus_another_role(): void
    {
        $user = User::factory()->create(['password_change_at' => now()]);
        $user->assignRole(['staff', 'super-administrator']);

        $this->assertTrue($user->fresh()->isMultiRoleStaff());
    }

    public function test_is_multi_role_staff_returns_false_for_staff_only(): void
    {
        $user = User::factory()->create(['password_change_at' => now()]);
        $user->assignRole('staff');

        $this->assertFalse($user->fresh()->isMultiRoleStaff());
    }

    public function test_is_multi_role_staff_returns_false_for_admin_only(): void
    {
        $user = User::factory()->create(['password_change_at' => now()]);
        $user->assignRole('super-administrator');

        $this->assertFalse($user->fresh()->isMultiRoleStaff());
    }

    public function test_is_multi_role_staff_returns_false_for_no_roles(): void
    {
        $user = User::factory()->create(['password_change_at' => now()]);

        $this->assertFalse($user->fresh()->isMultiRoleStaff());
    }

    public function test_can_access_admin_dashboard_true_for_super_administrator(): void
    {
        $user = User::factory()->create(['password_change_at' => now()]);
        $user->assignRole('super-administrator');

        $this->assertTrue($user->fresh()->canAccessAdminDashboard());
    }

    public function test_can_access_admin_dashboard_true_for_user_with_view_dashboard_permission(): void
    {
        $user = User::factory()->create(['password_change_at' => now()]);
        $user->givePermissionTo('view dashboard');

        $this->assertTrue($user->fresh()->canAccessAdminDashboard());
    }

    public function test_can_access_admin_dashboard_false_for_staff_only(): void
    {
        $user = User::factory()->create(['password_change_at' => now()]);
        $user->assignRole('staff');

        $this->assertFalse($user->fresh()->canAccessAdminDashboard());
    }
}
