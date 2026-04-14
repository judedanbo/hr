<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class QualificationPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Permission::firstOrCreate(['name' => 'view all staff qualifications']);
        Permission::firstOrCreate(['name' => 'delete staff qualification']);
        Permission::firstOrCreate(['name' => 'restore staff qualification']);
        Permission::firstOrCreate(['name' => 'destroy staff qualification']);
    }
}
