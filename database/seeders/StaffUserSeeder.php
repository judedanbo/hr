<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class StaffUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Permission::firstOrCreate(['name' => 'upload avatar']);
        Permission::firstOrCreate(['name' => 'edit avatar']);
        Permission::firstOrCreate(['name' => 'view dependent']);
        Permission::firstOrCreate(['name' => 'create dependent']);
        Permission::firstOrCreate(['name' => 'edit dependent']);
        Permission::firstOrCreate(['name' => 'delete dependent']);

        Role::firstOrCreate(['name' => 'staff', 'guard_name' => 'web'])
            ->syncPermissions([
                'view staff',
                'upload avatar',
                'edit avatar',
                'view dependent',
                'create dependent',
                'edit dependent',
                'delete dependent',
            ]);
        Role::findByName('super-administrator')
            ->givePermissionTo([
                'view staff',
                'upload avatar',
                'edit avatar',
                'view dependent',
                'create dependent',
                'edit dependent',
                'delete dependent',
            ]);
    }
}
