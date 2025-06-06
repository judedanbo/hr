<?php

namespace App\Exports;

use App\Models\Job;
use Illuminate\Contracts\Queue\ShouldQueue;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Alignment as StyleAlignment;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class GradeSummaryExport implements FromQuery, ShouldAutoSize, ShouldQueue, WithHeadings, WithMapping, WithStyles, WithTitle
{
    use Exportable;

    public function title(): string
    {
        return 'Ranks';
    }

    public function headings(): array
    {
        return [
            'Rank',
            'Harmonized Grade',
            'Level',
            'Male staff',
            'Female staff',
            'No. of staff',
        ];
    }

    public function map($job): array
    {
        return [
            $job->name,
            $job->category ? $job->category->name : '',
            $job->category ? $job->category->level : '',
            $job->male_staff_count ?? 0,
            $job->female_staff_count ?? 0,
            $job->staff_count,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            // Style the first row as bold text.
            1 => ['font' => ['bold' => true]],
            'B' => ['alignment' => ['horizontal' => StyleAlignment::HORIZONTAL_LEFT]],
            // Styling a specific cell by coordinate.
            'G' => ['alignment' => ['horizontal' => StyleAlignment::HORIZONTAL_LEFT]],
        ];
    }

    public function query()
    {
        return Job::query()
            ->searchRank(request()->search)
            ->with(['category', 'institution'])
            ->whereHas('staff', function ($query) {
                $query->active();
                $query->where('job_staff.end_date', null);
            })
            ->withCount([
                'staff' => function ($query) {
                    $query->active();
                    $query->whereNull('job_staff.end_date');
                },
                'staff as male_staff_count' => function ($query) {
                    $query->active();
                    $query->where('job_staff.end_date', null);
                    $query->maleStaff();
                },
                'staff as female_staff_count' => function ($query) {
                    $query->active();
                    $query->where('job_staff.end_date', null);
                    $query->femaleStaff();
                },
            ])
            ->orderByRaw('job_category_id is null asc, job_category_id asc');
    }
}
