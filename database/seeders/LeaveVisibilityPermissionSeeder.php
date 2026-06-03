<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class LeaveVisibilityPermissionSeeder extends Seeder
{
    /**
     * Permissions for the Leave visibility module (Phase 5).
     *
     * @return array<int, string>
     */
    public static function permissions(): array
    {
        return [
            'view leave calendar',
        ];
    }

    public function run(): void
    {
        foreach (self::permissions() as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }
    }
}
