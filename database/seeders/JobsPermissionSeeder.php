<?php

namespace Database\Seeders;

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
        Permission::firstOrCreate(['name' => 'view all jobs']);
        Permission::firstOrCreate(['name' => 'view job']);
        Permission::firstOrCreate(['name' => 'create job']);
        Permission::firstOrCreate(['name' => 'edit job']);
        Permission::firstOrCreate(['name' => 'delete job']);
        Permission::firstOrCreate(['name' => 'restore job']);
        Permission::firstOrCreate(['name' => 'destroy job']);
    }
}
