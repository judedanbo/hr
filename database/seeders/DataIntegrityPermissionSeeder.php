<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class DataIntegrityPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create permissions
        Permission::firstOrCreate(['name' => 'data-integrity.view'], [
            'name' => 'data-integrity.view',
            'guard_name' => 'web',
        ]);

        Permission::firstOrCreate(['name' => 'data-integrity.fix'], [
            'name' => 'data-integrity.fix',
            'guard_name' => 'web',
        ]);

        // Assign permissions to super-administrator role only
        $superAdmin = Role::where('name', 'super-administrator')->first();

        if ($superAdmin) {
            $superAdmin->givePermissionTo(['data-integrity.view', 'data-integrity.fix']);
        }

        $this->command->info('Data Integrity permissions created and assigned to super-administrator role.');
    }
}
