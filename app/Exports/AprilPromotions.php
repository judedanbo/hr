<?php

namespace App\Exports;

use App\Models\InstitutionPerson;
use Illuminate\Contracts\Queue\ShouldQueue;
// use Illuminate\Contracts\Support\Responsable;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class AprilPromotions implements
    // FromCollection,
    ShouldAutoSize, WithHeadings, FromQuery, WithMapping, ShouldQueue
{
    use Exportable;

    // function headings(): array
    // {
    //     return [
    //         'Full Name',
    //         'Gender',
    //         'Date of Birth',
    //         'Date Hired',
    //         'Years Employed',
    //         'Staff Number',
    //         'Old Staff Number',
    //         'Employment Status',
    //         'Current Rank',
    //         'Current Rank Start',
    //         'Current Unit',
    //         'Current Unit Start',
    //     ];
    // }
    // public function map($staff): array
    // {
    //     return [
    //         $staff->person->full_name,
    //         $staff->person->gender->name,
    //         $staff->person->date_of_birth->format('d F, Y'),
    //         $staff->hire_date?->format('d F, Y'),
    //         $staff->years_employed,
    //         $staff->staff_number,
    //         $staff->old_staff_number,
    //         $staff->status,
    //         $staff->ranks->count() > 0 ? $staff->ranks->first()->name : null,
    //         $staff->ranks->count() > 0 ? $staff->ranks->first()->pivot->start_date->format('d F, Y') : null,
    //         $staff->units->count() > 0 ? $staff->units->first()->name : null,
    //         $staff->units->count() > 0 ? $staff->units->first()->pivot->start_date->format('d F, Y') : null,
    //     ];
    // }
    public function query()
    {
        return InstitutionPerson::query()
            ->active()
            ->whereHas('ranks', function ($query) {
                $query->whereNull('end_date');
                $query->whereYear('start_date', '<=', date('Y') - 3);
                $query->whereNotIn('job_id', [16, 35, 49, 65, 71]);
                $query->whereMonth('start_date', '<=', '07');
            })
            ->with(['person', 'institution', 'units', 'ranks' => function ($query) {
                $query->whereNull('end_date');
                $query->whereYear('start_date', '<=', date('Y') - 3);
            }])
            ->orderBy(
                JobStaff::select('start_date')
                    ->whereColumn('staff_id', 'institution_person.id')
                    ->orderBy('start_date', 'desc')
                    ->limit(1)
            );
    }
}
