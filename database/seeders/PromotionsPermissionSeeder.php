<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class PromotionsPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Permission::firstOrCreate(['name' => 'promote staff']);
        Permission::firstOrCreate(['name' => 'view past all promotions']);
        Permission::firstOrCreate(['name' => 'view past promotion']);

        // Assign to super-administrator role
        $superAdmin = \Spatie\Permission\Models\Role::where('name', 'super-administrator')->first();
        if ($superAdmin) {
            $superAdmin->givePermissionTo(['promote staff', 'view past all promotions', 'view past promotion']);
        }
    }
}
