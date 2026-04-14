<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class AllPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Consolidates ALL permissions used in the application and assigns them to super-administrator.
     * Uses firstOrCreate() for idempotency - safe to run multiple times.
     */
    public function run(): void
    {
        $permissions = $this->getAllPermissions();

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Assign all permissions to super-administrator
        $superAdmin = Role::findByName('super-administrator');
        if ($superAdmin) {
            $superAdmin->syncPermissions(Permission::all());
        }
    }

    /**
     * Get all permissions organized by category.
     */
    protected function getAllPermissions(): array
    {
        return [
            // ============================================
            // User Management (17)
            // ============================================
            'view all users',
            'create user',
            'view user',
            'update user',
            'delete user',
            'restore user',
            'destroy user',
            'view user activity',
            'view user permissions',
            'update user permissions',
            'view user roles',
            'update user roles',
            'view user profile',
            'update user profile',
            'upload avatar',
            'edit avatar',
            'reset user password',

            // ============================================
            // Roles & Permissions (17)
            // ============================================
            'view all permissions',
            'view permission',
            'create permission',
            'update permission',
            'delete permission',
            'view all roles',
            'view role',
            'create role',
            'update role',
            'delete role',
            'restore role',
            'destroy role',
            'view roles',
            'view permissions',
            'assign roles to user',
            'assign permissions to role',
            'assign permissions to user',

            // ============================================
            // Staff Management (17)
            // ============================================
            'view all staff',
            'view staff',
            'create staff',
            'update staff',
            'delete staff',
            'restore staff',
            'destroy staff',
            'view separated staff',
            'download active staff data',
            'download separated staff data',
            'view all people',
            'view person',
            'view create person',
            'view update person',
            'view delete person',
            'view restore person',
            'view destroy person',

            // ============================================
            // Staff Transfers (8)
            // ============================================
            'view all staff transfers',
            'view staff transfers',
            'create staff transfers',
            'update staff transfers',
            'delete staff transfers',
            'restore staff transfers',
            'destroy staff transfers',
            'transfer staff',

            // ============================================
            // Staff Promotions (10)
            // ============================================
            'view all staff promotions',
            'view staff promotion',
            'create staff promotion',
            'update staff promotion',
            'delete staff promotion',
            'restore staff promotion',
            'destroy staff promotion',
            'promote staff',
            'view all past promotions',
            'view past promotion',
            'view past all promotions',

            // ============================================
            // Staff Positions (7)
            // ============================================
            'view all staff positions',
            'view staff position',
            'create staff position',
            'update staff position',
            'delete staff position',
            'restore staff position',
            'destroy staff position',

            // ============================================
            // Positions (7)
            // ============================================
            'view all positions',
            'view position',
            'create position',
            'update position',
            'delete position',
            'restore position',
            'destroy position',

            // ============================================
            // Staff Status (7)
            // ============================================
            'view staff status history',
            'view staff status',
            'create staff status',
            'edit staff status',
            'delete staff status',
            'restore staff status',
            'destroy staff status',

            // ============================================
            // Staff Qualifications (8)
            // ============================================
            'view all staff qualifications',
            'view staff qualification',
            'create staff qualification',
            'edit staff qualification',
            'delete staff qualification',
            'restore staff qualification',
            'destroy staff qualification',
            'download staff qualification data',

            // ============================================
            // Staff Notes (3)
            // ============================================
            'view staff notes',
            'create staff notes',
            'edit staff notes',

            // ============================================
            // Dependents (8)
            // ============================================
            'view all dependents',
            'view dependent',
            'create dependent',
            'edit dependent',
            'delete dependent',
            'update dependent',
            'restore dependent',
            'destroy dependent',

            // ============================================
            // Units (8)
            // ============================================
            'view all units',
            'view unit',
            'create unit',
            'edit unit',
            'delete unit',
            'restore unit',
            'destroy unit',
            'download unit staff',

            // ============================================
            // Jobs/Ranks (7)
            // ============================================
            'view all jobs',
            'view job',
            'create job',
            'edit job',
            'delete job',
            'restore job',
            'destroy job',

            // ============================================
            // Job Categories (8)
            // ============================================
            'view all job categories',
            'view job category',
            'create job category',
            'edit job category',
            'delete job category',
            'restore job category',
            'destroy job category',
            'download job summary',

            // ============================================
            // Institutions (7)
            // ============================================
            'view all institutions',
            'view institution',
            'create institution',
            'edit institution',
            'delete institution',
            'restore institution',
            'destroy institution',

            // ============================================
            // Separations (7)
            // ============================================
            'view all separations',
            'view separation',
            'create separation',
            'update separation',
            'delete separation',
            'restore separation',
            'destroy separation',

            // ============================================
            // Reports & Downloads (5)
            // ============================================
            'view all reports',
            'view report',
            'download promotion data',
            'download recruitment data',
            'download rank staff data',

            // ============================================
            // Dashboard (1)
            // ============================================
            'view dashboard',

            // ============================================
            // Data Integrity (2)
            // ============================================
            'data-integrity.view',
            'data-integrity.fix',

            // ============================================
            // Contacts (4)
            // ============================================
            'view contacts',
            'create contacts',
            'update contacts',
            'delete contacts',

            // ============================================
            // Documents (4)
            // ============================================
            'view documents',
            'create documents',
            'update documents',
            'delete documents',
        ];
    }
}
