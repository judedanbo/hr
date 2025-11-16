<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class RolePermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Permission permissions
        Permission::create(['name' => 'view all permissions']);
        Permission::create(['name' => 'view permission']);
        Permission::create(['name' => 'create permission']);
        Permission::create(['name' => 'update permission']);
        Permission::create(['name' => 'delete permission']);

        // Role permissions
        Permission::create(['name' => 'view all roles']);
        Permission::create(['name' => 'view role']);
        Permission::create(['name' => 'create role']);
        Permission::create(['name' => 'update role']);
        Permission::create(['name' => 'delete role']);
        Permission::create(['name' => 'restore role']);
        Permission::create(['name' => 'destroy role']);

        // User permission/role management
        Permission::create(['name' => 'view user permissions']);
        Permission::create(['name' => 'view user roles']);
        Permission::create(['name' => 'assign roles to user']);
        Permission::create(['name' => 'assign permissions to role']);
        Permission::create(['name' => 'assign permissions to user']);
    }
}
