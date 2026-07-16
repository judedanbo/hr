<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class LeaveApprovalPermissionSeeder extends Seeder
{
    /**
     * Permissions for the Leave Approvals & workflow module (Phase 4).
     *
     * @return array<int, string>
     */
    public static function permissions(): array
    {
        return [
            'approve staff leave',
            'reassign leave approver',
            'manage leave delegations',
            'manage leave approvers',
        ];
    }

    public function run(): void
    {
        foreach (self::permissions() as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }
    }
}
