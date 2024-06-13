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
            'Appointment Date',
            'Years Served',
            'Current Rank',
            'Current Unit',
            'Retirement Date',
            'current rank Start Date',
            'current Unit Start Date'
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
            $staff->hire_date?->format('d F, Y'),
            $staff->hire_date === null ? '' : Carbon::now()->diffInYears($staff->hire_date) . ' years',
            $staff->currentRank?->job?->name,
            $staff->currentUnit?->unit?->name,
            $staff->person->date_of_birth?->addYears(60)->format('d F Y'),
            $staff->currentRank?->start_date?->format('d F, Y'),
            $staff->currentUnit?->start_date?->format('d F, Y')
        ];
    }
    function query()
    {
        return InstitutionPerson::query()
            ->join('people', 'institution_person.person_id', '=', 'people.id')
            ->active()
            ->with(['person'])
            ->currentRank()
            ->currentUnit()
            ->toRetire()
            ->orderBy('people.date_of_birth', 'asc');
    }
}
