<?php

namespace App\Exports;

use App\Enums\ContactTypeEnum;
use App\Enums\Identity;
use App\Models\InstitutionPerson;
use App\Models\JobCategory;
use App\Models\Person;
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

class StaffToRetireExport implements FromQuery, ShouldAutoSize, ShouldQueue, WithHeadings, WithMapping, WithStyles, WithTitle
{
    use Exportable;

    public function title(): string
    {
        return 'Staff To Retire';
    }

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
            'Contact',
            'Years Served',
            'Current Rank',
            'Current Unit',
            'Retirement Date',
            'current rank Start Date',
            'current Unit Start Date',
            'level',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            // Style the first row as bold text.
            1 => ['font' => ['bold' => true]],
            'B' => ['alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT]],
            // Styling a specific cell by coordinate.
            'H' => ['alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT]],
        ];
    }

    public function map($staff): array
    {
        return [
            $staff->file_number,
            $staff->staff_number,
            $staff->person->full_name,
            $staff->person->date_of_birth?->format('d F, Y'),
            $staff->person->date_of_birth?->diffInYears() . ' years',
            $staff->person->identities->where('id_type', Identity::GhanaCard)->first()?->id_number,
            $staff->hire_date?->format('d F, Y'),
            $staff->person->contacts->count() > 0 ? $staff->person->contacts->where('contact_type', ContactTypeEnum::PHONE)->map(function ($item) {
                return $item->contact;
            })->implode(', ') : '',
            $staff->hire_date === null ? '' : Carbon::now()->diffInYears($staff->hire_date) . ' years',
            $staff->currentRank?->job?->name,
            $staff->currentUnit?->unit?->name,
            $staff->person->date_of_birth?->addYears(60)->format('d F Y'),
            $staff->currentRank?->start_date?->format('d F, Y'),
            $staff->currentUnit?->start_date?->format('d F, Y'),
            $staff->currentRank?->job?->category->level,
        ];
    }

    public function query()
    {
        return InstitutionPerson::query()
            ->active()
            ->with(['person' => function ($query) {
                $query->with('identities', 'contacts');
            }])
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
