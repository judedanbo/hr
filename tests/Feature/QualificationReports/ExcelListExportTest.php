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
}
