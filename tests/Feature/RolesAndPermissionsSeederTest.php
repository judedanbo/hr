<?php

namespace Tests\Feature;

use Database\Seeders\AllPermissionsSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RolesAndPermissionsSeederTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(AllPermissionsSeeder::class);
    }

    public function test_seeder_creates_all_roles(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        foreach ([
            'super-administrator',
            'admin-user',
            'aag-admin',
            'hr-user',
            'personel-user',
            'general-admin-user',
            'internal-audit-user',
            'staff',
        ] as $roleName) {
            $this->assertTrue(
                Role::where('name', $roleName)->exists(),
                "{$roleName} role should exist"
            );
        }
    }

    public function test_super_administrator_has_all_permissions(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $role = Role::findByName('super-administrator');
        $this->assertEquals(Permission::count(), $role->permissions->count());
    }

    public function test_staff_has_self_service_permissions(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $role = Role::findByName('staff');
        $permissions = $role->permissions->pluck('name')->toArray();

        $this->assertContains('view staff', $permissions);
        $this->assertContains('upload avatar', $permissions);
        $this->assertContains('edit avatar', $permissions);
        $this->assertContains('create staff qualification', $permissions);
        $this->assertContains('view staff qualification', $permissions);

        // Staff should not see the broader list or perform admin actions
        $this->assertNotContains('view all staff', $permissions);
        $this->assertNotContains('create staff', $permissions);
        $this->assertNotContains('update staff', $permissions);
    }

    public function test_admin_user_has_dashboard_and_staff_management(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $role = Role::findByName('admin-user');
        $permissions = $role->permissions->pluck('name')->toArray();

        $this->assertContains('view dashboard', $permissions);
        $this->assertContains('view all staff', $permissions);
        $this->assertContains('view staff', $permissions);
        $this->assertContains('create staff', $permissions);
        $this->assertContains('update staff', $permissions);
        $this->assertContains('download active staff data', $permissions);
        $this->assertContains('download separated staff data', $permissions);
    }

    public function test_aag_admin_has_dashboard_and_staff_view(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $role = Role::findByName('aag-admin');
        $permissions = $role->permissions->pluck('name')->toArray();

        $this->assertContains('view dashboard', $permissions);
        $this->assertContains('view all staff', $permissions);
        $this->assertContains('view staff', $permissions);
        $this->assertCount(3, $permissions);
    }

    public function test_personel_user_has_only_view_permissions(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $role = Role::findByName('personel-user');
        $permissions = $role->permissions->pluck('name')->toArray();

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

        $this->assertNotContains('update staff', $permissions);
        $this->assertNotContains('create staff', $permissions);
        $this->assertNotContains('delete staff', $permissions);
    }

    public function test_hr_user_has_qualification_management_permissions(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $role = Role::findByName('hr-user');
        $permissions = $role->permissions->pluck('name')->toArray();

        $this->assertContains('view all staff', $permissions);
        $this->assertContains('view staff', $permissions);
        $this->assertContains('view all staff qualifications', $permissions);
        $this->assertContains('view staff qualification', $permissions);
        $this->assertContains('create staff qualification', $permissions);
        $this->assertContains('edit staff qualification', $permissions);
        $this->assertContains('view all reports', $permissions);
        $this->assertContains('view report', $permissions);
        $this->assertContains('download staff qualification data', $permissions);
        $this->assertContains('view staff notes', $permissions);
        $this->assertContains('create staff notes', $permissions);
        $this->assertContains('edit staff notes', $permissions);

        $this->assertNotContains('create staff transfers', $permissions);
        $this->assertNotContains('view separated staff', $permissions);
    }

    public function test_general_admin_user_has_active_staff_view_only(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $role = Role::findByName('general-admin-user');
        $permissions = $role->permissions->pluck('name')->toArray();

        $this->assertContains('view all staff', $permissions);
        $this->assertContains('view staff', $permissions);
        $this->assertContains('view contacts', $permissions);
        $this->assertContains('view staff status', $permissions);
        $this->assertContains('view documents', $permissions);
        $this->assertContains('view staff qualification', $permissions);
        $this->assertContains('view staff notes', $permissions);

        $this->assertNotContains('view separated staff', $permissions);
        $this->assertNotContains('create staff', $permissions);
        $this->assertNotContains('update staff', $permissions);
        $this->assertNotContains('delete staff', $permissions);
    }

    public function test_internal_audit_user_has_staff_and_separations_view(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $role = Role::findByName('internal-audit-user');
        $permissions = $role->permissions->pluck('name')->toArray();

        $this->assertContains('view all staff', $permissions);
        $this->assertContains('view staff', $permissions);
        $this->assertContains('view separated staff', $permissions);
        $this->assertContains('view all separations', $permissions);

        $this->assertNotContains('create staff', $permissions);
        $this->assertNotContains('update staff', $permissions);
        $this->assertNotContains('delete staff', $permissions);
    }

    public function test_seeder_is_idempotent(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $firstRun = [];
        foreach (['staff', 'personel-user', 'hr-user', 'general-admin-user', 'internal-audit-user', 'aag-admin', 'admin-user'] as $roleName) {
            $role = Role::findByName($roleName);
            $firstRun[$roleName] = $role->permissions->pluck('name')->sort()->values()->toArray();
        }

        $this->seed(RolesAndPermissionsSeeder::class);

        foreach ($firstRun as $roleName => $expected) {
            $role = Role::findByName($roleName);
            $secondRun = $role->permissions->pluck('name')->sort()->values()->toArray();
            $this->assertEquals($expected, $secondRun, "{$roleName} permissions should be identical after re-running seeder");
        }
    }
}
