<?php

namespace App\Exports;

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
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class StaffPositionExport implements FromQuery, ShouldAutoSize, ShouldQueue, WithHeadings, WithMapping, WithStyles, WithTitle
{
    use Exportable;

    public function title(): string
    {
        return 'Staff Position';
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
            'B' => ['alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT]],

        ];
    }

    public function headings(): array
    {
        return [
            'File Number',
            'Staff Number',
            'Full Name',
            'Years Served',
            'Current Rank',
            'Current Unit',
            'level',
        ];
    }

    public function map($staff): array
    {
        return [
            $staff->file_number,
            $staff->staff_number,
            $staff->person->full_name,
            $staff->hire_date === null ? '' : Carbon::now()->diffInYears($staff->hire_date) . ' years',
            $staff->currentRank?->job?->name,
            $staff->currentUnit?->unit?->name,
            $staff->currentRank?->job?->category->level ?? null,
            // $staff
        ];
    }

    public function query()
    {
        return InstitutionPerson::query()
            ->active()
            ->with('person')
            ->currentRank()
            ->currentUnit();
        // ->where('staff_number', '2743')
        // ->orderBy(
        //     JobCategory::query()
        //         ->join('jobs', 'job_categories.id', '=', 'jobs.job_category_id')
        //         ->join('job_staff', 'jobs.id', '=', 'job_staff.job_id')
        //         ->select('job_categories.level')
        //         ->whereColumn('job_staff.staff_id', 'institution_person.id')
        //         ->orderBy('job_categories.level')
        //         ->limit(1)
        // );
    }
}
