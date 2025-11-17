<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class AssignRolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Permission::create(['name' => 'assign roles to user']);
        Permission::create(['name' => 'assign permissions to role']);
        Permission::create(['name' => 'assign permissions to user']);
    }
}
