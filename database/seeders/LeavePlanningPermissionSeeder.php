<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class LeavePlanningPermissionSeeder extends Seeder
{
    /**
     * Permissions for the Annual Leave Planning module (Phase 2).
     *
     * @return array<int, string>
     */
    public static function permissions(): array
    {
        return [
            'manage leave planning windows',
            'view leave plans',
            'submit leave plan',
            'view all leave plans',
        ];
    }

    public function run(): void
    {
        foreach (self::permissions() as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }
    }
}
