<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
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
        Permission::create(['name' => 'view all users']);
        Permission::create(['name' => 'create user']);
        Permission::create(['name' => 'view user']);
        Permission::create(['name' => 'update user']);
        Permission::create(['name' => 'delete user']);
        Permission::create(['name' => 'restore user']);
        Permission::create(['name' => 'destroy user']);
        Permission::create(['name' => 'view user activity']);
        Permission::create(['name' => 'view roles']);
        Permission::create(['name' => 'create role']);
        Permission::create(['name' => 'update role']);
        Permission::create(['name' => 'delete role']);
        Permission::create(['name' => 'view permissions']);
        Permission::create(['name' => 'create permission']);
        Permission::create(['name' => 'update permission']);
        Permission::create(['name' => 'delete permission']);
        Permission::create(['name' => 'view user permissions']);
        Permission::create(['name' => 'update user permissions']);
        Permission::create(['name' => 'view user roles']);
        Permission::create(['name' => 'update user roles']);
        Permission::create(['name' => 'view user profile']);
        Permission::create(['name' => 'update user profile']);
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
