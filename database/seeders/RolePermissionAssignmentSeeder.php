<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionAssignmentSeeder extends Seeder
{
    /**
     * Permission assignments for each role.
     *
     * @var array<string, array<string>>
     */
    protected array $rolePermissions = [
        // Personnel User: Read-only access to active and separated staff with full details
        'personel-user' => [
            'view all staff',
            'view staff',
            'view separated staff',
            'view staff status',
            'view staff status history',
            'view contacts',
            'view documents',
            'view all dependents',
            'view dependent',
            'view staff qualification',
            'view all staff qualifications',
            'view staff notes',
            'view all staff positions',
            'view staff position',
            'view all staff promotions',
            'view staff promotion',
            'view all staff transfers',
            'view staff transfers',
            'view all separations',
            'view separation',
        ],

        // HR User: View active staff, manage qualifications, download qualification reports
        'hr-user' => [
            'view all staff',
            'view staff',
            'view contacts',
            'view staff notes',
            'create staff notes',
            'edit staff notes',
            'view all staff qualifications',
            'view staff qualification',
            'create staff qualification',
            'edit staff qualification',
            'view all reports',
            'view report',
            'download staff qualification data',
        ],

        // General Admin User: Read-only access to active staff with details
        'general-admin-user' => [
            'view all staff',
            'view staff',
            'view contacts',
            'view staff status',
            'view documents',
            'view staff qualification',
            'view staff notes',
        ],

        // Internal Audit User: View active staff list only, no detail access
        'internal-audit-user' => [
            'view all staff',
        ],
    ];

    /**
     * Run the database seeds.
     *
     * Syncs permissions for each role, replacing any existing assignments.
     */
    public function run(): void
    {
        // Ensure all required permissions exist
        $allPermissions = collect($this->rolePermissions)->flatten()->unique();
        foreach ($allPermissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        foreach ($this->rolePermissions as $roleName => $permissions) {
            $role = Role::firstOrCreate(
                ['name' => $roleName, 'guard_name' => 'web']
            );

            $role->syncPermissions($permissions);

            $this->command->info("Synced {$roleName}: " . count($permissions) . ' permissions');
        }
    }
}
