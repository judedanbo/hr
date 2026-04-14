<?php

namespace Tests\Feature\QualificationReports;

use App\DataTransferObjects\QualificationReportFilter;
use App\Exports\Qualifications\QualificationListExport;
use App\Models\Qualification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

class ExcelListExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_list_export_download_ok(): void
    {
        Excel::fake();
        Qualification::factory()->approved()->count(3)->create();

        Excel::download(new QualificationListExport(new QualificationReportFilter), 'quals.xlsx');

        Excel::assertDownloaded('quals.xlsx');
    }

    public function test_list_export_headings_include_expected_columns(): void
    {
        $export = new QualificationListExport(new QualificationReportFilter);
        $headings = $export->headings();
        $this->assertContains('Staff Number', $headings);
        $this->assertContains('Qualification', $headings);
        $this->assertContains('Level', $headings);
        $this->assertContains('Institution', $headings);
        $this->assertContains('Year', $headings);
        $this->assertContains('Status', $headings);
    }

    public function test_by_unit_export_downloads(): void
    {
        Excel::fake();
        Qualification::factory()->approved()->count(2)->create();

        Excel::download(
            new \App\Exports\Qualifications\QualificationByUnitExport(new QualificationReportFilter),
            'by-unit.xlsx'
        );
        Excel::assertDownloaded('by-unit.xlsx');
    }

    public function test_by_level_export_downloads(): void
    {
        Excel::fake();
        Qualification::factory()->approved()->count(2)->create();

        Excel::download(
            new \App\Exports\Qualifications\QualificationByLevelExport(new QualificationReportFilter),
            'by-level.xlsx'
        );
        Excel::assertDownloaded('by-level.xlsx');
    }

    public function test_by_level_export_headings(): void
    {
        $export = new \App\Exports\Qualifications\QualificationByLevelExport(new QualificationReportFilter);
        $h = $export->headings();
        $this->assertContains('Level', $h);
        $this->assertContains('Staff Count', $h);
        $this->assertContains('% of Workforce', $h);
        $this->assertContains('Pending', $h);
    }

    public function test_staff_without_quals_export_downloads(): void
    {
        Excel::fake();
        Excel::download(
            new \App\Exports\Qualifications\StaffWithoutQualificationsExport(new QualificationReportFilter),
            'gaps.xlsx'
        );
        Excel::assertDownloaded('gaps.xlsx');
    }

    public function test_staff_qualification_profile_export_downloads(): void
    {
        Excel::fake();
        $person = \App\Models\Person::factory()->create();
        Qualification::factory()->for($person)->approved()->count(2)->create();

        Excel::download(
            new \App\Exports\Qualifications\StaffQualificationProfileExport($person),
            "profile-{$person->id}.xlsx"
        );
        Excel::assertDownloaded("profile-{$person->id}.xlsx");
    }
}
