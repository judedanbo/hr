<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class UnitsPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Permission::create(['name' => 'view all units']);
        Permission::create(['name' => 'view unit']);
        Permission::create(['name' => 'create unit']);
        Permission::create(['name' => 'edit unit']);
        Permission::create(['name' => 'delete unit']);
        Permission::create(['name' => 'restore unit']);
        Permission::create(['name' => 'destroy unit']);
    }
}
