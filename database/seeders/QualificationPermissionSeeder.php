<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
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
        Permission::create(['name' => 'veiw all staff qualifications']);
        Permission::create(['name' => 'delete staff qualification']);
        Permission::create(['name' => 'restore staff qualification']);
        Permission::create(['name' => 'destroy staff qualification']);
    }
}
