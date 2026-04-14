<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class ReportPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Permission::firstOrCreate(['name' => 'view all reports']);
        Permission::firstOrCreate(['name' => 'view report']);
    }
}
