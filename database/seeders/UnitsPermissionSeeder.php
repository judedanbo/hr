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
        Permission::firstOrCreate(['name' => 'view all units']);
        Permission::firstOrCreate(['name' => 'view unit']);
        Permission::firstOrCreate(['name' => 'create unit']);
        Permission::firstOrCreate(['name' => 'edit unit']);
        Permission::firstOrCreate(['name' => 'delete unit']);
        Permission::firstOrCreate(['name' => 'restore unit']);
        Permission::firstOrCreate(['name' => 'destroy unit']);
        Permission::firstOrCreate(['name' => 'transfer staff']);

        // Assign to super-administrator role
        $superAdmin = \Spatie\Permission\Models\Role::where('name', 'super-administrator')->first();
        if ($superAdmin) {
            $superAdmin->givePermissionTo([
                'view all units', 'view unit', 'create unit', 'edit unit',
                'delete unit', 'restore unit', 'destroy unit', 'transfer staff',
            ]);
        }
    }
}
