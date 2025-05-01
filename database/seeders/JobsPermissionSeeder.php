<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class JobsPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Permission::create(['name' => 'view all jobs']);
        Permission::create(['name' => 'view job']);
        Permission::create(['name' => 'create job']);
        Permission::create(['name' => 'edit job']);
        Permission::create(['name' => 'delete job']);
        Permission::create(['name' => 'restore job']);
        Permission::create(['name' => 'destroy job']);
    }
}
