<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class UserPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Permission::firstOrCreate(['name' => 'view all users']);
        Permission::firstOrCreate(['name' => 'create user']);
        Permission::firstOrCreate(['name' => 'view user']);
        Permission::firstOrCreate(['name' => 'update user']);
        Permission::firstOrCreate(['name' => 'delete user']);
        Permission::firstOrCreate(['name' => 'restore user']);
        Permission::firstOrCreate(['name' => 'destroy user']);
        Permission::firstOrCreate(['name' => 'view user activity']);
        Permission::firstOrCreate(['name' => 'view roles']);
        Permission::firstOrCreate(['name' => 'create role']);
        Permission::firstOrCreate(['name' => 'update role']);
        Permission::firstOrCreate(['name' => 'delete role']);
        Permission::firstOrCreate(['name' => 'view permissions']);
        Permission::firstOrCreate(['name' => 'create permission']);
        Permission::firstOrCreate(['name' => 'update permission']);
        Permission::firstOrCreate(['name' => 'delete permission']);
        Permission::firstOrCreate(['name' => 'view user permissions']);
        Permission::firstOrCreate(['name' => 'update user permissions']);
        Permission::firstOrCreate(['name' => 'view user roles']);
        Permission::firstOrCreate(['name' => 'update user roles']);
        Permission::firstOrCreate(['name' => 'view user profile']);
        Permission::firstOrCreate(['name' => 'update user profile']);
        Role::findByName('staff')
            ->givePermissionTo([
                'view user',
                'create user',
                'update user',
            ]);
        Role::findByName('super-administrator')
            ->givePermissionTo([
                'view all users',
                'view user',
                'create user',
                'update user',
                'delete user',
                'restore user',
                'destroy user',
            ]);
    }
}
