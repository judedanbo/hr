<?php

namespace App\Exports;

use App\Models\PersonUnit;
use Illuminate\Contracts\Queue\ShouldQueue;
// use Illuminate\Contracts\Support\Responsable;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class RecruitmentExport implements
    // FromCollection,
    ShouldAutoSize,
    WithHeadings,
    FromQuery,
    WithMapping,
    ShouldQueue
{
    use Exportable;

    function headings(): array
    {
        return [
            'Title',
            'Surname',
            'Other Names',
            'First Name',
            'gender',
            'Date of Birth',
            'Date Hired',
            'Years Employed',
            'Staff Number',
            'Old Staff Number',
            'Status',
            'Current Rank',
            // 'Current Start Date',
        ];
    }
    public function map($staff): array
    {
        return [
            $staff->title,
            $staff->surname,
            $staff->other_names,
            $staff->first_name,
            $staff->gender,
            $staff->date_of_birth->format('d F, Y'),
            $staff->hire_date->format('d F, Y'),
            $staff->years_employed,
            $staff->staff_number,
            $staff->old_staff_number,
            $staff->status,
            $staff->jobs ? $staff->jobs->first()->name : null,
            $staff->jobs ? $staff->jobs->first()->pivot->start_date : null,
        ];
    }
    public function query()
    {
        return PersonUnit::query()
            ->with(['jobs', 'person'])
            ->join('people', function ($join) {
                $join->on('people.id', '=', 'person_unit.person_id');
            })
            ->when(request()->active, function ($query) {
                $query->active();
            })
            ->when(request()->retired, function ($query) {
                $query->retired();
            });
    }
}