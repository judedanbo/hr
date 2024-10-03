<?php

namespace App\Exports;

use App\Enums\ContactTypeEnum;
use App\Enums\Identity;
use App\Models\InstitutionPerson;
use App\Models\JobCategory;
use Carbon\Carbon;
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

class HarmonizedGradeSummaryExport implements FromQuery, ShouldAutoSize, ShouldQueue, WithHeadings, WithMapping, WithStyles, WithTitle
{
    use Exportable;

    public function title(): string
    {
        return 'Harmonized grades';
    }

    public function headings(): array
    {
        return [
            'Harmonized Grade',
            'Level',
            'No. of grades',
            'Active Staff',
            'Due for Promotion',
            'All time Staff',
        ];
    }

    public function map($jobCategory): array
    {
        return [
            $jobCategory->name,
            $jobCategory->level,
            $jobCategory->jobs_count,
            $jobCategory->staff->sum('active_count'),
            $jobCategory->staff->sum('promotion_count'),
            $jobCategory->staff->sum('all_count'),
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
        return JobCategory::query()
            ->withCount(
                [
                    'jobs',
                ]
            )
            ->with([
                'staff' => function ($query) {
                    $query->withCount([
                        'staff as active_count' => function ($query) {
                            $query->active();
                            $query->where('job_staff.end_date', null);
                        },
                        'staff as promotion_count' => function ($query) {
                            $query->active();
                            $query->where('job_staff.end_date', null);
                            $query->whereYear('job_staff.start_date', '<=', Carbon::now()->subYears(3));
                        },
                        'staff as all_count',
                    ]);
                },
            ])
            ->when(request()->search, function ($query, $search) {
                $query->where('name', 'like', '%' . $search . '%');
                $query->orWhere('short_name', 'like', "%$search%");
            })
            ->with(['parent', 'institution']);
    }
}
