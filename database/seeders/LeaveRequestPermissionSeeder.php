<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class LeaveRequestPermissionSeeder extends Seeder
{
    /**
     * Permissions for the Leave Requests module (Phase 3).
     *
     * @return array<int, string>
     */
    public static function permissions(): array
    {
        return [
            'view leave requests',
            'create leave request',
            'update leave request',
            'cancel leave request',
            'view all leave requests',
        ];
    }

    public function run(): void
    {
        foreach (self::permissions() as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }
    }
}
