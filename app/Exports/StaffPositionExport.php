<?php

namespace App\Exports;

use App\Models\InstitutionPerson;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class StaffPositionExport implements FromQuery, WithMapping, WithHeadings, ShouldQueue, ShouldAutoSize
{
    use Exportable;

    public function headings(): array
    {
        return [
            'File Number',
            'Staff Number',
            'Full Name',
            'Years Served',
            'Current Rank',
            'Current Unit',
            // 'level'
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
            // $staff->currentUnit?->level->label(),

        ];
    }
    function query()
    {
        return InstitutionPerson::query()
            ->join('job_staff', 'institution_person.id', '=', 'job_staff.staff_id')
            ->join('jobs', 'job_staff.job_id', '=', 'jobs.id')
            ->join('job_categories', 'jobs.job_category_id', '=', 'job_categories.id')
            ->whereNull('job_staff.end_date')
            ->whereNull('jobs.deleted_at')
            ->with(['person', 'ranks.category'])
            ->currentRank()
            ->currentUnit()
            ->orderBy('job_categories.level', 'asc')
            ->orderBy('job_staff.start_date', 'asc')
            ->active();
    }
}
