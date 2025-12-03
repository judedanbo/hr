<?php

namespace Database\Seeders;

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
        Permission::firstOrCreate(['name' => 'view all separations']);
        Permission::firstOrCreate(['name' => 'view separation']);
        Permission::firstOrCreate(['name' => 'create separation']);
        Permission::firstOrCreate(['name' => 'update separation']);
        Permission::firstOrCreate(['name' => 'delete separation']);
        Permission::firstOrCreate(['name' => 'restore separation']);
        Permission::firstOrCreate(['name' => 'destroy separation']);

        Role::findByName('super-administrator')
            ->givePermissionTo(Permission::all());
    }
}
