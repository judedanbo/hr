<?php

namespace Tests\Feature;

use Database\Seeders\AllPermissionsSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AllPermissionsSeederTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed roles first (required for super-administrator assignment)
        $this->seed(RoleSeeder::class);
    }

    public function test_seeder_creates_all_permissions(): void
    {
        $this->seed(AllPermissionsSeeder::class);

        // Verify a substantial number of permissions were created
        $this->assertGreaterThanOrEqual(100, Permission::count());
    }

    public function test_seeder_is_idempotent(): void
    {
        // Run seeder twice
        $this->seed(AllPermissionsSeeder::class);
        $countAfterFirst = Permission::count();

        $this->seed(AllPermissionsSeeder::class);
        $countAfterSecond = Permission::count();

        // Count should remain the same
        $this->assertEquals($countAfterFirst, $countAfterSecond);
    }

    public function test_seeder_assigns_all_permissions_to_super_administrator(): void
    {
        $this->seed(AllPermissionsSeeder::class);

        $superAdmin = Role::findByName('super-administrator');
        $totalPermissions = Permission::count();

        $this->assertEquals($totalPermissions, $superAdmin->permissions->count());
    }

    public function test_seeder_creates_user_management_permissions(): void
    {
        $this->seed(AllPermissionsSeeder::class);

        $userPermissions = [
            'view all users',
            'create user',
            'view user',
            'update user',
            'delete user',
            'view user activity',
        ];

        foreach ($userPermissions as $permission) {
            $this->assertTrue(
                Permission::where('name', $permission)->exists(),
                "Permission '{$permission}' should exist"
            );
        }
    }

    public function test_seeder_creates_role_permissions(): void
    {
        $this->seed(AllPermissionsSeeder::class);

        $rolePermissions = [
            'view all roles',
            'create role',
            'update role',
            'delete role',
            'assign roles to user',
            'assign permissions to role',
        ];

        foreach ($rolePermissions as $permission) {
            $this->assertTrue(
                Permission::where('name', $permission)->exists(),
                "Permission '{$permission}' should exist"
            );
        }
    }

    public function test_seeder_creates_staff_management_permissions(): void
    {
        $this->seed(AllPermissionsSeeder::class);

        $staffPermissions = [
            'view all staff',
            'create staff',
            'update staff',
            'delete staff',
            'view separated staff',
        ];

        foreach ($staffPermissions as $permission) {
            $this->assertTrue(
                Permission::where('name', $permission)->exists(),
                "Permission '{$permission}' should exist"
            );
        }
    }

    public function test_seeder_creates_qualification_permissions_with_correct_spelling(): void
    {
        $this->seed(AllPermissionsSeeder::class);

        // Verify the corrected spelling (was "veiw" in old seeder)
        $this->assertTrue(
            Permission::where('name', 'view all staff qualifications')->exists(),
            "Permission 'view all staff qualifications' should exist with correct spelling"
        );
    }

    public function test_seeder_creates_contact_and_document_permissions(): void
    {
        $this->seed(AllPermissionsSeeder::class);

        $permissions = [
            'view contacts',
            'create contacts',
            'update contacts',
            'delete contacts',
            'view documents',
            'create documents',
            'update documents',
            'delete documents',
        ];

        foreach ($permissions as $permission) {
            $this->assertTrue(
                Permission::where('name', $permission)->exists(),
                "Permission '{$permission}' should exist"
            );
        }
    }

    public function test_seeder_creates_data_integrity_permissions(): void
    {
        $this->seed(AllPermissionsSeeder::class);

        $dataIntegrityPermissions = [
            'data-integrity.view',
            'data-integrity.fix',
        ];

        foreach ($dataIntegrityPermissions as $permission) {
            $this->assertTrue(
                Permission::where('name', $permission)->exists(),
                "Permission '{$permission}' should exist"
            );
        }
    }
}
