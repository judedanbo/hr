<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
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

        // Permission::create(['name' => 'view all roles']);
        // Permission::create(['name' => 'view role']);
        // Permission::create(['name' => 'create role']);
        // Permission::create(['name' => 'update role']);
        // Permission::create(['name' => 'delete role']);
        Permission::create(['name' => 'restore role']);
        Permission::create(['name' => 'destroy role']);
        Permission::create(['name' => 'view user permissions']);
        Permission::create(['name' => 'view user roles']);
        Permission::create(['name' => 'assign roles to user']);
        Permission::create(['name' => 'assign permissions to role']);
        Permission::create(['name' => 'assign permissions to user']);
    }
}
