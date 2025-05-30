<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class StaffStatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Permission::create(['name' => 'view staff status history']);
        Permission::create(['name' => 'view staff status']);
        Permission::create(['name' => 'create staff status']);
        Permission::create(['name' => 'edit staff status']);
        Permission::create(['name' => 'delete staff status']);
        Permission::create(['name' => 'restore staff status']);
        Permission::create(['name' => 'destroy staff status']);

        Role::findByName('super-administrator')
            ->givePermissionTo(Permission::all());
    }
}
