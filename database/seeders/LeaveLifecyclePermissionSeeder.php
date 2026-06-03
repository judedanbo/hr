<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class LeaveLifecyclePermissionSeeder extends Seeder
{
    /**
     * Permissions for the post-approval lifecycle module (Phase 7).
     *
     * @return array<int, string>
     */
    public static function permissions(): array
    {
        return [
            'resume leave request',
            'amend leave request',
            'adjust leave balance',
        ];
    }

    public function run(): void
    {
        foreach (self::permissions() as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }
    }
}
