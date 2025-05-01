<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class SeparationPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Permission::create(['name' => 'view all separations']);
        Permission::create(['name' => 'view separation']);
        Permission::create(['name' => 'create separation']);
        Permission::create(['name' => 'update separation']);
        Permission::create(['name' => 'delete separation']);
        Permission::create(['name' => 'restore separation']);
        Permission::create(['name' => 'destroy separation']);

        Role::findByName('super-administrator')
            ->givePermissionTo(Permission::all());
    }
}
