<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
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
        // Permission::create(['name' => 'upload avatar']);
        Permission::create(['name' => 'edit avatar']);
        Permission::create(['name' => 'view dependent']);
        Permission::create(['name' => 'create dependent']);
        Permission::create(['name' => 'edit dependent']);
        Permission::create(['name' => 'delete dependent']);

        Role::create(['name' => 'staff'])
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
