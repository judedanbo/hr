<?php

namespace App\Exports;

use App\Models\InstitutionPerson;
use App\Models\JobStaff;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class PromotionSummaryExport implements
    FromQuery,
    WithHeadings,
    ShouldAutoSize,
    WithTitle
{
    function title(): string
    {
        return date('Y') . ' promotions';
    }
    public function headings(): array
    {
        return [
            'Rank',
            'April',
            'October',
            'Total Staff',
        ];
    }
    public function query()
    {
        return InstitutionPerson::query()
            ->active()
            ->join('job_staff', 'institution_person.id', '=', 'job_staff.staff_id')
            ->join('jobs', 'job_staff.job_id', '=', 'jobs.id')
            ->join('job_categories', 'jobs.job_category_id', '=', 'job_categories.id')
            ->selectRaw('jobs.name job_name, count(case when month(job_staff.start_date) <= 4 then 1 end) as april, count(case when month(job_staff.start_date) > 4 then 1 end) as october, count(*) as staff')
            ->groupByRaw('job_name, job_id')
            ->orderByRaw('job_categories.level')
            ->whereNull('jobs.deleted_at')
            ->whereRaw("year(job_staff.start_date) < " . date('Y') - 3);
    }
}
