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
        Permission::firstOrCreate(['name' => 'view all permissions']);
        Permission::firstOrCreate(['name' => 'view permission']);
        Permission::firstOrCreate(['name' => 'create permission']);
        Permission::firstOrCreate(['name' => 'update permission']);
        Permission::firstOrCreate(['name' => 'delete permission']);

        // Role permissions
        Permission::firstOrCreate(['name' => 'view all roles']);
        Permission::firstOrCreate(['name' => 'view role']);
        Permission::firstOrCreate(['name' => 'create role']);
        Permission::firstOrCreate(['name' => 'update role']);
        Permission::firstOrCreate(['name' => 'delete role']);
        Permission::firstOrCreate(['name' => 'restore role']);
        Permission::firstOrCreate(['name' => 'destroy role']);

        // User permission/role management
        Permission::firstOrCreate(['name' => 'view user permissions']);
        Permission::firstOrCreate(['name' => 'view user roles']);
        Permission::firstOrCreate(['name' => 'assign roles to user']);
        Permission::firstOrCreate(['name' => 'assign permissions to role']);
        Permission::firstOrCreate(['name' => 'assign permissions to user']);
    }
}
