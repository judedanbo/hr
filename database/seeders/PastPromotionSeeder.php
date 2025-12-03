<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class PastPromotionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Permission::firstOrCreate(['name' => 'view all past promotions']);
        Permission::firstOrCreate(['name' => 'view past promotion']);

        // Assign to super-administrator role
        $superAdmin = \Spatie\Permission\Models\Role::where('name', 'super-administrator')->first();
        if ($superAdmin) {
            $superAdmin->givePermissionTo(['view all past promotions', 'view past promotion']);
        }
    }
}
