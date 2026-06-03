<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class LeaveReportPermissionSeeder extends Seeder
{
    /**
     * Permissions for the Leave reporting module (Phase 6).
     *
     * @return array<int, string>
     */
    public static function permissions(): array
    {
        return [
            'view leave reports',
            'view all leave reports',
            'export leave reports',
        ];
    }

    public function run(): void
    {
        foreach (self::permissions() as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }
    }
}
