<?php

namespace Tests\Feature\QualificationReports;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

class ExcelRouteTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\QualificationReportPermissionSeeder::class);
    }

    public function test_excel_route_dispatches_list_export(): void
    {
        Excel::fake();
        $user = User::factory()->create();
        $user->givePermissionTo([
            'qualifications.reports.view',
            'qualifications.reports.view.all',
            'qualifications.reports.export',
        ]);

        $this->actingAs($user->fresh())
            ->get('/qualifications/reports/export/excel?type=list')
            ->assertOk();

        Excel::assertDownloaded('qualifications-list.xlsx');
    }

    public function test_excel_route_dispatches_by_unit_export(): void
    {
        Excel::fake();
        $user = User::factory()->create();
        $user->givePermissionTo([
            'qualifications.reports.view',
            'qualifications.reports.view.all',
            'qualifications.reports.export',
        ]);
        $this->actingAs($user->fresh())
            ->get('/qualifications/reports/export/excel?type=by_unit')
            ->assertOk();
        Excel::assertDownloaded('qualifications-by-unit.xlsx');
    }

    public function test_excel_route_requires_export_permission(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(['qualifications.reports.view', 'qualifications.reports.view.all']);

        $this->actingAs($user->fresh())
            ->get('/qualifications/reports/export/excel?type=list')
            ->assertForbidden();
    }

    public function test_excel_route_rejects_invalid_type(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo([
            'qualifications.reports.view',
            'qualifications.reports.view.all',
            'qualifications.reports.export',
        ]);

        $this->actingAs($user->fresh())
            ->get('/qualifications/reports/export/excel?type=unknown')
            ->assertStatus(302);  // Redirect back due to validation failure
    }
}
