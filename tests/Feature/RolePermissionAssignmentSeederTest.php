<?php

namespace Tests\Feature;

use Database\Seeders\AllPermissionsSeeder;
use Database\Seeders\RolePermissionAssignmentSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RolePermissionAssignmentSeederTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        $this->seed(AllPermissionsSeeder::class);
    }

    public function test_seeder_creates_internal_audit_user_role(): void
    {
        $this->seed(RolePermissionAssignmentSeeder::class);

        $this->assertTrue(
            Role::where('name', 'internal-audit-user')->exists(),
            'internal-audit-user role should exist'
        );
    }

    public function test_personel_user_has_only_view_permissions(): void
    {
        $this->seed(RolePermissionAssignmentSeeder::class);

        $role = Role::findByName('personel-user');
        $permissions = $role->permissions->pluck('name')->toArray();

        // Should have view access to staff list and details
        $this->assertContains('view all staff', $permissions);
        $this->assertContains('view staff', $permissions);
        $this->assertContains('view separated staff', $permissions);
        $this->assertContains('view contacts', $permissions);
        $this->assertContains('view documents', $permissions);
        $this->assertContains('view all dependents', $permissions);
        $this->assertContains('view dependent', $permissions);
        $this->assertContains('view staff qualification', $permissions);
        $this->assertContains('view all staff qualifications', $permissions);
        $this->assertContains('view staff notes', $permissions);
        $this->assertContains('view all staff positions', $permissions);
        $this->assertContains('view staff position', $permissions);
        $this->assertContains('view all staff promotions', $permissions);
        $this->assertContains('view staff promotion', $permissions);
        $this->assertContains('view all staff transfers', $permissions);
        $this->assertContains('view staff transfers', $permissions);
        $this->assertContains('view all separations', $permissions);
        $this->assertContains('view separation', $permissions);

        // Should NOT have edit/delete/create permissions
        $this->assertNotContains('update staff', $permissions);
        $this->assertNotContains('create staff', $permissions);
        $this->assertNotContains('delete staff', $permissions);
    }

    public function test_hr_user_has_qualification_management_permissions(): void
    {
        $this->seed(RolePermissionAssignmentSeeder::class);

        $role = Role::findByName('hr-user');
        $permissions = $role->permissions->pluck('name')->toArray();

        // Should have staff view access
        $this->assertContains('view all staff', $permissions);
        $this->assertContains('view staff', $permissions);

        // Should have qualification CRUD
        $this->assertContains('view all staff qualifications', $permissions);
        $this->assertContains('view staff qualification', $permissions);
        $this->assertContains('create staff qualification', $permissions);
        $this->assertContains('edit staff qualification', $permissions);

        // Should have report/download access
        $this->assertContains('view all reports', $permissions);
        $this->assertContains('view report', $permissions);
        $this->assertContains('download staff qualification data', $permissions);

        // Should have notes access
        $this->assertContains('view staff notes', $permissions);
        $this->assertContains('create staff notes', $permissions);
        $this->assertContains('edit staff notes', $permissions);

        // Should NOT have staff transfers (removed)
        $this->assertNotContains('create staff transfers', $permissions);

        // Should NOT have separated staff access
        $this->assertNotContains('view separated staff', $permissions);
    }

    public function test_general_admin_user_has_active_staff_view_only(): void
    {
        $this->seed(RolePermissionAssignmentSeeder::class);

        $role = Role::findByName('general-admin-user');
        $permissions = $role->permissions->pluck('name')->toArray();

        // Should have active staff view with details
        $this->assertContains('view all staff', $permissions);
        $this->assertContains('view staff', $permissions);
        $this->assertContains('view contacts', $permissions);
        $this->assertContains('view staff status', $permissions);
        $this->assertContains('view documents', $permissions);
        $this->assertContains('view staff qualification', $permissions);
        $this->assertContains('view staff notes', $permissions);

        // Should NOT have separated staff access
        $this->assertNotContains('view separated staff', $permissions);

        // Should NOT have any create/edit/delete
        $this->assertNotContains('create staff', $permissions);
        $this->assertNotContains('update staff', $permissions);
        $this->assertNotContains('delete staff', $permissions);
    }

    public function test_internal_audit_user_has_staff_list_only(): void
    {
        $this->seed(RolePermissionAssignmentSeeder::class);

        $role = Role::findByName('internal-audit-user');
        $permissions = $role->permissions->pluck('name')->toArray();

        // Should ONLY have view all staff (list access)
        $this->assertContains('view all staff', $permissions);
        $this->assertCount(1, $permissions);

        // Should NOT have detail access
        $this->assertNotContains('view staff', $permissions);
        $this->assertNotContains('view separated staff', $permissions);
        $this->assertNotContains('view contacts', $permissions);
    }

    public function test_seeder_is_idempotent(): void
    {
        $this->seed(RolePermissionAssignmentSeeder::class);

        $firstRun = [];
        foreach (['personel-user', 'hr-user', 'general-admin-user', 'internal-audit-user'] as $roleName) {
            $role = Role::findByName($roleName);
            $firstRun[$roleName] = $role->permissions->pluck('name')->sort()->values()->toArray();
        }

        // Run again
        $this->seed(RolePermissionAssignmentSeeder::class);

        foreach (['personel-user', 'hr-user', 'general-admin-user', 'internal-audit-user'] as $roleName) {
            $role = Role::findByName($roleName);
            $secondRun = $role->permissions->pluck('name')->sort()->values()->toArray();
            $this->assertEquals($firstRun[$roleName], $secondRun, "{$roleName} permissions should be identical after re-running seeder");
        }
    }

    public function test_download_staff_qualification_data_permission_exists(): void
    {
        $this->seed(RolePermissionAssignmentSeeder::class);

        $this->assertTrue(
            \Spatie\Permission\Models\Permission::where('name', 'download staff qualification data')->exists(),
            'download staff qualification data permission should exist'
        );
    }
}
