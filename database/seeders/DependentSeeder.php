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
        Permission::create(['name' => 'view all dependents']);
        // Permission::create(['name' => 'view dependent']);
        // Permission::create(['name' => 'create dependent']);
        // Permission::create(['name' => 'update dependent']);
        // Permission::create(['name' => 'delete dependent']);
        Permission::create(['name' => 'restore dependent']);
        Permission::create(['name' => 'destroy dependent']);

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
