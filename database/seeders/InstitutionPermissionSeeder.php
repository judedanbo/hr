<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class InstitutionPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        \Spatie\Permission\Models\Permission::create(['name' => 'view all institutions']);
        \Spatie\Permission\Models\Permission::create(['name' => 'create institution']);
        \Spatie\Permission\Models\Permission::create(['name' => 'view institution']);
        \Spatie\Permission\Models\Permission::create(['name' => 'edit institution']);
        \Spatie\Permission\Models\Permission::create(['name' => 'delete institution']);
        \Spatie\Permission\Models\Permission::create(['name' => 'restore institution']);
        \Spatie\Permission\Models\Permission::create(['name' => 'destroy institution']);

        $role = \Spatie\Permission\Models\Role::findByName('super-administrator');
        $role->givePermissionTo('view all institutions');
        $role->givePermissionTo('create institution');
        $role->givePermissionTo('view institution');
        $role->givePermissionTo('edit institution');
        $role->givePermissionTo('delete institution');
        $role->givePermissionTo('restore institution');
        $role->givePermissionTo('destroy institution');
    }
}
