<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class QualificationReportPermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'qualifications.reports.view',
            'qualifications.reports.export',
            'qualifications.reports.view.all',
            'qualifications.reports.view.own_unit',
        ];

        foreach ($permissions as $name) {
            Permission::firstOrCreate(['name' => $name]);
        }
    }
}
