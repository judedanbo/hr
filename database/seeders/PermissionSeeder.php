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

        Permission::create(['name' => 'view all people']);
        Permission::create(['name' => 'view person']);
        Permission::create(['name' => 'view create person']);
        Permission::create(['name' => 'view update person']);
        Permission::create(['name' => 'view delete person']);
        Permission::create(['name' => 'view restore person']);
        Permission::create(['name' => 'view destroy person']);

        // staff
        Permission::create(['name' => 'view all staff']);
        Permission::create(['name' => 'view staff']);
        Permission::create(['name' => 'create staff']);
        Permission::create(['name' => 'update staff']);
        Permission::create(['name' => 'delete staff']);
        Permission::create(['name' => 'restore staff']);
        Permission::create(['name' => 'destroy staff']);

        Permission::create(['name' => 'view staff qualification']);
        Permission::create(['name' => 'create staff qualification']);
        Permission::create(['name' => 'edit staff qualification']);
        Permission::create(['name' => 'create staff notes']);
        Permission::create(['name' => 'view staff notes']);
        Permission::create(['name' => 'edit staff notes']);

        Permission::create(['name' => 'view separated staff']);

        Permission::create(['name' => 'download active staff data']);
        Permission::create(['name' => 'download separated staff data']);

        // Transfer staff permissionas
        Permission::create(['name' => 'view all staff transfers']);
        Permission::create(['name' => 'view staff transfers']);
        Permission::create(['name' => 'create staff transfers']);
        Permission::create(['name' => 'update staff transfers']);
        Permission::create(['name' => 'delete staff transfers']);
        Permission::create(['name' => 'restore staff transfers']);
        Permission::create(['name' => 'destroy staff transfers']);
        //staff promotions permissionas
        Permission::create(['name' => 'view all staff promotions']);
        Permission::create(['name' => 'view staff promotion']);
        Permission::create(['name' => 'create staff promotion']);
        Permission::create(['name' => 'update staff promotion']);
        Permission::create(['name' => 'delete staff promotion']);
        Permission::create(['name' => 'restore staff promotion']);
        Permission::create(['name' => 'destroy staff promotion']);
        // personel-user@gmail.com
        // hr-user@gmail.com

        // posrion Permission
        Permission::create(['name' => 'view all positions']);
        Permission::create(['name' => 'view position']);
        Permission::create(['name' => 'create position']);
        Permission::create(['name' => 'update position']);
        Permission::create(['name' => 'delete position']);
        Permission::create(['name' => 'restore position']);
        Permission::create(['name' => 'destroy position']);

        // Staff Position Permission
        Permission::create(['name' => 'view all staff positions']);
        Permission::create(['name' => 'view staff position']);
        Permission::create(['name' => 'create staff position']);
        Permission::create(['name' => 'update staff position']);
        Permission::create(['name' => 'delete staff position']);
        Permission::create(['name' => 'restore staff position']);
        Permission::create(['name' => 'destroy staff position']);

        Role::create(['name' => 'super-administrator'])
            ->givePermissionTo(Permission::all());
        Role::create(['name' => 'personel-user'])
            ->givePermissionTo([
                'view all staff',
                'view staff',
                'update staff',
                'view separated staff',
            ]);
        Role::create(['name' => 'hr-user'])
            ->givePermissionTo([
                'view all staff',
                'view staff qualification',
                'create staff qualification',
                'edit staff qualification',
                'create staff notes',
                'view staff notes',
                'edit staff notes',
                'create staff transfers',
            ]);
        Role::create(['name' => 'general-admin-user'])
            ->givePermissionTo([
                'view all staff',
            ]);
        Role::create(['name' => 'admin-user'])
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
