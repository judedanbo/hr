<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // person Permission
        Permission::firstOrCreate(['name' => 'view all people']);
        Permission::firstOrCreate(['name' => 'view person']);
        Permission::firstOrCreate(['name' => 'view create person']);
        Permission::firstOrCreate(['name' => 'view update person']);
        Permission::firstOrCreate(['name' => 'view delete person']);
        Permission::firstOrCreate(['name' => 'view restore person']);
        Permission::firstOrCreate(['name' => 'view destroy person']);

        // staff
        Permission::firstOrCreate(['name' => 'view all staff']);
        Permission::firstOrCreate(['name' => 'view staff']);
        Permission::firstOrCreate(['name' => 'create staff']);
        Permission::firstOrCreate(['name' => 'update staff']);
        Permission::firstOrCreate(['name' => 'delete staff']);
        Permission::firstOrCreate(['name' => 'restore staff']);
        Permission::firstOrCreate(['name' => 'destroy staff']);

        Permission::firstOrCreate(['name' => 'view staff qualification']);
        Permission::firstOrCreate(['name' => 'create staff qualification']);
        Permission::firstOrCreate(['name' => 'edit staff qualification']);
        Permission::firstOrCreate(['name' => 'create staff notes']);
        Permission::firstOrCreate(['name' => 'view staff notes']);
        Permission::firstOrCreate(['name' => 'edit staff notes']);

        Permission::firstOrCreate(['name' => 'view separated staff']);

        Permission::firstOrCreate(['name' => 'download active staff data']);
        Permission::firstOrCreate(['name' => 'download separated staff data']);
        Permission::firstOrCreate(['name' => 'download staff qualification data']);

        // Transfer staff permissions
        Permission::firstOrCreate(['name' => 'view all staff transfers']);
        Permission::firstOrCreate(['name' => 'view staff transfers']);
        Permission::firstOrCreate(['name' => 'create staff transfers']);
        Permission::firstOrCreate(['name' => 'update staff transfers']);
        Permission::firstOrCreate(['name' => 'delete staff transfers']);
        Permission::firstOrCreate(['name' => 'restore staff transfers']);
        Permission::firstOrCreate(['name' => 'destroy staff transfers']);

        // staff promotions permissions
        Permission::firstOrCreate(['name' => 'view all staff promotions']);
        Permission::firstOrCreate(['name' => 'view staff promotion']);
        Permission::firstOrCreate(['name' => 'create staff promotion']);
        Permission::firstOrCreate(['name' => 'update staff promotion']);
        Permission::firstOrCreate(['name' => 'delete staff promotion']);
        Permission::firstOrCreate(['name' => 'restore staff promotion']);
        Permission::firstOrCreate(['name' => 'destroy staff promotion']);

        // position Permission
        Permission::firstOrCreate(['name' => 'view all positions']);
        Permission::firstOrCreate(['name' => 'view position']);
        Permission::firstOrCreate(['name' => 'create position']);
        Permission::firstOrCreate(['name' => 'update position']);
        Permission::firstOrCreate(['name' => 'delete position']);
        Permission::firstOrCreate(['name' => 'restore position']);
        Permission::firstOrCreate(['name' => 'destroy position']);

        // Staff Position Permission
        Permission::firstOrCreate(['name' => 'view all staff positions']);
        Permission::firstOrCreate(['name' => 'view staff position']);
        Permission::firstOrCreate(['name' => 'create staff position']);
        Permission::firstOrCreate(['name' => 'update staff position']);
        Permission::firstOrCreate(['name' => 'delete staff position']);
        Permission::firstOrCreate(['name' => 'restore staff position']);
        Permission::firstOrCreate(['name' => 'destroy staff position']);

        Role::firstOrCreate(['name' => 'super-administrator', 'guard_name' => 'web'])
            ->givePermissionTo(Permission::all());

        // Personnel User: Read-only access to active and separated staff with full details
        Role::firstOrCreate(['name' => 'personel-user', 'guard_name' => 'web'])
            ->givePermissionTo([
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
            ]);

        // HR User: View active staff, manage qualifications, download qualification reports
        Role::firstOrCreate(['name' => 'hr-user', 'guard_name' => 'web'])
            ->givePermissionTo([
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
            ]);

        // General Admin User: Read-only access to active staff with details
        Role::firstOrCreate(['name' => 'general-admin-user', 'guard_name' => 'web'])
            ->givePermissionTo([
                'view all staff',
                'view staff',
                'view contacts',
                'view staff status',
                'view documents',
                'view staff qualification',
                'view staff notes',
            ]);

        // Internal Audit User: View active staff list only, no detail access
        Role::firstOrCreate(['name' => 'internal-audit-user', 'guard_name' => 'web'])
            ->givePermissionTo([
                'view all staff',
            ]);

        // Admin User (unchanged)
        Role::firstOrCreate(['name' => 'admin-user', 'guard_name' => 'web'])
            ->givePermissionTo([
                'view all staff',
                'view staff',
                'create staff',
                'update staff',
                'download active staff data',
                'create staff transfers',
                'create staff promotion',
                'create staff notes',
                'download separated staff data',
            ]);
    }
}
