<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class LeavePermissionSeeder extends Seeder
{
    /**
     * Permissions for the Leave Management configuration module (Phase 1).
     *
     * @return array<int, string>
     */
    public static function permissions(): array
    {
        return [
            // Leave Years
            'view all leave years',
            'view leave year',
            'create leave year',
            'update leave year',
            'delete leave year',
            'clone leave year',

            // Leave Types
            'view all leave types',
            'view leave type',
            'create leave type',
            'update leave type',
            'delete leave type',

            // Leave Entitlements
            'view all leave entitlements',
            'view leave entitlement',
            'create leave entitlement',
            'update leave entitlement',
            'delete leave entitlement',

            // Holidays
            'view all holidays',
            'view holiday',
            'create holiday',
            'update holiday',
            'delete holiday',
        ];
    }

    public function run(): void
    {
        foreach (self::permissions() as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }
    }
}
