<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;

    protected User $adminUser;

    protected User $staffUser;

    protected User $guestUser;

    protected function setUp(): void
    {
        parent::setUp();

        // Create users with different roles
        // Set password_change_at to bypass PasswordChanged middleware
        $this->superAdmin = User::factory()->create(['password_change_at' => now()]);
        $this->superAdmin->assignRole('super-administrator');

        $this->adminUser = User::factory()->create(['password_change_at' => now()]);
        $this->adminUser->assignRole('admin-user');

        $this->staffUser = User::factory()->create(['password_change_at' => now()]);
        $this->staffUser->assignRole('staff');

        $this->guestUser = User::factory()->create(['password_change_at' => now()]);
        // No role assigned
    }

    // ===================
    // STAFF ACCESS TESTS
    // ===================

    public function test_super_admin_can_view_all_staff(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->get(route('staff.index'));

        $response->assertStatus(200);
    }

    public function test_admin_user_can_view_all_staff(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('staff.index'));

        $response->assertStatus(200);
    }

    public function test_user_without_permission_cannot_view_all_staff(): void
    {
        $response = $this->actingAs($this->guestUser)
            ->get(route('staff.index'));

        // Should redirect with error or return 403
        $response->assertStatus(302);
    }

    public function test_super_admin_can_create_staff(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->get(route('staff.create'));

        $response->assertStatus(200);
    }

    public function test_admin_user_can_create_staff(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('staff.create'));

        $response->assertStatus(200);
    }

    public function test_staff_user_cannot_create_staff(): void
    {
        $response = $this->actingAs($this->staffUser)
            ->get(route('staff.create'));

        $response->assertRedirect();
    }

    // ===================
    // USER MANAGEMENT TESTS
    // ===================

    public function test_super_admin_can_view_all_users(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->get(route('user.index'));

        $response->assertStatus(200);
    }

    public function test_admin_user_cannot_view_all_users_without_permission(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('user.index'));

        // Admin user doesn't have 'view all users' by default
        $response->assertRedirect();
    }

    public function test_user_with_view_all_users_permission_can_access(): void
    {
        $this->guestUser->givePermissionTo('view all users');

        $response = $this->actingAs($this->guestUser)
            ->get(route('user.index'));

        $response->assertStatus(200);
    }

    // ===================
    // ROLE MANAGEMENT TESTS
    // ===================

    public function test_super_admin_can_view_roles(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->get(route('role.index'));

        $response->assertStatus(200);
    }

    public function test_user_without_role_permission_cannot_view_roles(): void
    {
        $response = $this->actingAs($this->guestUser)
            ->get(route('role.index'));

        $response->assertRedirect();
    }

    public function test_super_admin_can_create_role(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->post(route('role.store'), [
                'name' => 'test-role',
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('roles', ['name' => 'test-role']);
    }

    // ===================
    // PERMISSION MANAGEMENT TESTS
    // ===================

    public function test_super_admin_can_view_permissions(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->get(route('permission.index'));

        $response->assertStatus(200);
    }

    public function test_user_without_permission_cannot_view_permissions(): void
    {
        $response = $this->actingAs($this->guestUser)
            ->get(route('permission.index'));

        $response->assertRedirect();
    }

    // ===================
    // UNIT MANAGEMENT TESTS
    // ===================

    public function test_super_admin_can_view_all_units(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->get(route('unit.index'));

        $response->assertStatus(200);
    }

    public function test_user_with_unit_permission_can_view_units(): void
    {
        $this->guestUser->givePermissionTo('view all units');

        $response = $this->actingAs($this->guestUser)
            ->get(route('unit.index'));

        $response->assertStatus(200);
    }

    // ===================
    // JOB/RANK MANAGEMENT TESTS
    // ===================

    public function test_super_admin_can_view_all_jobs(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->get(route('job.index'));

        $response->assertStatus(200);
    }

    public function test_user_with_job_permission_can_view_jobs(): void
    {
        $this->guestUser->givePermissionTo('view all jobs');

        $response = $this->actingAs($this->guestUser)
            ->get(route('job.index'));

        $response->assertStatus(200);
    }

    // ===================
    // SEPARATION ACCESS TESTS
    // ===================

    public function test_super_admin_can_view_separated_staff(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->get(route('separation.index'));

        $response->assertStatus(200);
    }

    public function test_user_with_separation_permission_can_view(): void
    {
        $this->guestUser->givePermissionTo('view separated staff');
        $this->guestUser->givePermissionTo('view all separations');

        $response = $this->actingAs($this->guestUser)
            ->get(route('separation.index'));

        $response->assertStatus(200);
    }

    // ===================
    // REPORT ACCESS TESTS
    // ===================

    public function test_super_admin_can_access_promotion_reports(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->get(route('report.promotion'));

        $response->assertStatus(200);
    }

    // ===================
    // DIRECT PERMISSION ASSIGNMENT TESTS
    // ===================

    public function test_user_can_have_direct_permissions(): void
    {
        $this->guestUser->givePermissionTo('view all staff');

        $this->assertTrue($this->guestUser->can('view all staff'));
        $this->assertFalse($this->guestUser->can('create staff'));
    }

    public function test_role_permissions_are_inherited(): void
    {
        // Super admin should have all permissions
        $this->assertTrue($this->superAdmin->can('view all staff'));
        $this->assertTrue($this->superAdmin->can('create staff'));
        $this->assertTrue($this->superAdmin->can('view all users'));
        $this->assertTrue($this->superAdmin->can('view all roles'));
    }

    public function test_permission_can_be_revoked(): void
    {
        $this->guestUser->givePermissionTo('view all staff');
        $this->assertTrue($this->guestUser->can('view all staff'));

        $this->guestUser->revokePermissionTo('view all staff');
        // Need to refresh permissions cache
        $this->guestUser = $this->guestUser->fresh();

        $this->assertFalse($this->guestUser->can('view all staff'));
    }

    // ===================
    // ROLE ASSIGNMENT TESTS
    // ===================

    public function test_user_can_be_assigned_multiple_roles(): void
    {
        $this->guestUser->assignRole('staff');
        $this->guestUser->assignRole('hr-user');

        $this->assertTrue($this->guestUser->hasRole('staff'));
        $this->assertTrue($this->guestUser->hasRole('hr-user'));
    }

    public function test_role_can_be_removed_from_user(): void
    {
        $this->guestUser->assignRole('staff');
        $this->assertTrue($this->guestUser->hasRole('staff'));

        $this->guestUser->removeRole('staff');

        $this->assertFalse($this->guestUser->hasRole('staff'));
    }

    // ===================
    // SELF-ACCESS TESTS
    // ===================

    public function test_user_can_view_own_profile(): void
    {
        // Users should be able to view their own user record
        $response = $this->actingAs($this->staffUser)
            ->get(route('user.show', $this->staffUser));

        // This depends on UserPolicy implementation
        $response->assertStatus(200);
    }

    // ===================
    // DATA INTEGRITY PERMISSION TESTS
    // ===================

    public function test_super_admin_can_access_data_integrity(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->get(route('data-integrity.index'));

        $response->assertStatus(200);
    }

    public function test_non_super_admin_cannot_access_data_integrity(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('data-integrity.index'));

        $response->assertRedirect();
    }
}
