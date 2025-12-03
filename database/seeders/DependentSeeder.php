<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class DependentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Permission::firstOrCreate(['name' => 'view all dependents']);
        Permission::firstOrCreate(['name' => 'view dependent']);
        Permission::firstOrCreate(['name' => 'create dependent']);
        Permission::firstOrCreate(['name' => 'update dependent']);
        Permission::firstOrCreate(['name' => 'delete dependent']);
        Permission::firstOrCreate(['name' => 'restore dependent']);
        Permission::firstOrCreate(['name' => 'destroy dependent']);

        $role = \Spatie\Permission\Models\Role::findByName('staff');
        $role->givePermissionTo([
            'view all dependents',
            'view dependent',
            'create dependent',
            'update dependent',
            'delete dependent',
        ]);
    }
}
