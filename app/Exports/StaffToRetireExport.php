<?php

namespace App\Exports;

use App\Enums\Identity;
use App\Models\InstitutionPerson;
use App\Models\JobCategory;
use App\Models\Person;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class StaffToRetireExport implements FromQuery, WithMapping, WithHeadings, ShouldQueue, ShouldAutoSize
{
    use Exportable;

    public function headings(): array
    {
        return [
            'File Number',
            'Staff Number',
            'Full Name',
            'Date of Birth',
            'Age',
            'Ghana Card Number',
            'Appointment Date',
            'Years Served',
            'Current Rank',
            'Current Unit',
            'Retirement Date',
            'current rank Start Date',
            'current Unit Start Date',
            'level'
        ];
    }
    public function map($staff): array
    {
        return [
            $staff->file_number,
            $staff->staff_number,
            $staff->person->full_name,
            $staff->person->date_of_birth?->format('d F, Y'),
            $staff->person->date_of_birth?->diffInYears() . " years",
            $staff->person->identities->where('id_type', Identity::GhanaCard)->first()?->id_number,            $staff->hire_date?->format('d F, Y'),
            $staff->hire_date === null ? '' : Carbon::now()->diffInYears($staff->hire_date) . ' years',
            $staff->currentRank?->job?->name,
            $staff->currentUnit?->unit?->name,
            $staff->person->date_of_birth?->addYears(60)->format('d F Y'),
            $staff->currentRank?->start_date?->format('d F, Y'),
            $staff->currentUnit?->start_date?->format('d F, Y'),
            $staff->currentRank?->job?->category->level,
        ];
    }
    function query()
    {
        return InstitutionPerson::query()
            ->active()
            ->with(['person.identities'])
            ->active()
            ->currentRank()
            ->currentUnit()
            ->toRetire();
        // ->orderBy(
        //     Person::select('date_of_birth')
        //         ->whereColumn('people.id', 'institution_person.person_id')
        // )
        // ->orderBy(
        //     JobCategory::select('level')
        //         ->join('job_staff', 'job_categories.id', '=', 'job_staff.job_id')
        //         ->whereColumn('job_staff.staff_id', 'institution_person.id')
        //         ->whereNull('job_staff.end_date')
        //         ->take(1)
        // );
    }
}
