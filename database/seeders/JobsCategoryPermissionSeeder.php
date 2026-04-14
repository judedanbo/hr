<?php

namespace Database\Seeders;

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
        Permission::firstOrCreate(['name' => 'view all job categories']);
        Permission::firstOrCreate(['name' => 'view job category']);
        Permission::firstOrCreate(['name' => 'create job category']);
        Permission::firstOrCreate(['name' => 'edit job category']);
        Permission::firstOrCreate(['name' => 'delete job category']);
        Permission::firstOrCreate(['name' => 'restore job category']);
        Permission::firstOrCreate(['name' => 'destroy job category']);
        Permission::firstOrCreate(['name' => 'download job summary']);
    }
}
