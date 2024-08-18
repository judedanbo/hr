<?php

namespace App\Exports;

use App\Models\InstitutionPerson;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;

class PromotionSummaryExport implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping, WithStyles, WithTitle
{
    public function title(): string
    {
        return date('Y') . ' promotions';
    }

    public function styles($sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
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

    public function map($staff): array
    {
        return [
            $staff->job_name,
            $staff->april,
            $staff->october,
            $staff->staff,
        ];
    }

    public function query()
    {
        return InstitutionPerson::query()
            ->active()
            ->join('job_staff', 'institution_person.id', '=', 'job_staff.staff_id')
            ->join('jobs', 'job_staff.job_id', '=', 'jobs.id')
            ->join('job_categories', 'jobs.job_category_id', '=', 'job_categories.id')
            ->selectRaw('jobs.name as job_name, job_staff.job_id as job_id, count(case when month(job_staff.start_date) in (1,2,3,4,11,12) then 1 end) as april, count(case when month(job_staff.start_date) in (5,6,7,8,9,10) then 1 end) as october, count(*) as staff')
            ->groupByRaw('job_name, job_id')
            ->orderByRaw('job_categories.level')
            ->whereNull('jobs.deleted_at')
            ->whereNull('job_staff.end_date')
            ->whereRaw('year(job_staff.start_date) < ' . date('Y') - 3);
    }
}
