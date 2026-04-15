<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Single source of truth for roles and their permissions.
 *
 * Safe to run repeatedly: creates any missing permissions, syncs role
 * permission sets, and assigns all permissions to super-administrator.
 */
class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Role definitions: name => display description.
     *
     * @var array<string, string>
     */
    protected array $roles = [
        'super-administrator' => 'Full system access with all permissions',
        'admin-user' => 'Administrative access for system management',
        'aag-admin' => 'AAG Admin with dashboard access',
        'hr-user' => 'Human resources management access',
        'personel-user' => 'Personnel/staff data management (read-only, active & separated)',
        'general-admin-user' => 'General administrative functions (read-only, active only)',
        'internal-audit-user' => 'Internal audit with view access to staff and separations',
        'staff' => 'Basic staff access — own staff page, avatar, and qualifications',
    ];

    /**
     * Permission assignments per role.
     *
     * super-administrator is intentionally omitted here — it receives ALL
     * permissions at the end of the run.
     *
     * @var array<string, array<string>>
     */
    protected array $rolePermissions = [
        'admin-user' => [
            'view dashboard',
            'view all staff',
            'view staff',
            'create staff',
            'update staff',
            'download active staff data',
            'download separated staff data',
            'create staff transfers',
            'create staff promotion',
            'create staff notes',
            'qualifications.reports.view',
            'qualifications.reports.export',
            'qualifications.reports.view.all',
        ],

        'aag-admin' => [
            'view dashboard',
            'view all staff',
            'view staff',
            'qualifications.reports.view',
            'qualifications.reports.view.all',
        ],

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
            'qualifications.reports.view',
            'qualifications.reports.export',
            'qualifications.reports.view.all',
        ],

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

        'general-admin-user' => [
            'view all staff',
            'view staff',
            'view contacts',
            'view staff status',
            'view documents',
            'view staff qualification',
            'view staff notes',
        ],

        'internal-audit-user' => [
            'view dashboard',
            'view all staff',
            'view staff',
            'view separated staff',
            'view all separations',
        ],

        'staff' => [
            'view staff',
            'upload avatar',
            'edit avatar',
            'create staff qualification',
            'view staff qualification',
        ],
    ];

    public function run(): void
    {
        foreach ($this->roles as $name => $_description) {
            Role::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        $requiredPermissions = collect($this->rolePermissions)->flatten()->unique();
        foreach ($requiredPermissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        foreach ($this->rolePermissions as $roleName => $permissions) {
            $role = Role::findByName($roleName);
            $role->syncPermissions($permissions);

            $this->command->info("Synced {$roleName}: " . count($permissions) . ' permissions');
        }

        Role::findByName('super-administrator')->syncPermissions(Permission::all());

        $this->command->info('Super-administrator granted all ' . Permission::count() . ' permissions');
    }
}
