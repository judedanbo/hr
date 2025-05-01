<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class JobsCategoryPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Permission::create(['name' => 'view all job categories']);
        Permission::create(['name' => 'view job category']);
        Permission::create(['name' => 'create job category']);
        Permission::create(['name' => 'edit job category']);
        Permission::create(['name' => 'delete job category']);
        Permission::create(['name' => 'restore job category']);
        Permission::create(['name' => 'destroy job category']);
    }
}
