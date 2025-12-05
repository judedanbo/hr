<?php

namespace App\Exports;

use App\Enums\ContactTypeEnum;
use App\Enums\Identity;
use App\Models\InstitutionPerson;
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
            $staff->person->age . ' years',
            $staff->person->identities->first()?->id_number, // Pre-filtered to Ghana Card only
            $staff->hire_date?->format('d F, Y'),
            $staff->person->contacts->pluck('contact')->implode(', '), // Pre-filtered to phone only
            $staff->hire_date === null ? '' : $staff->years_served . ' years',
            $staff->currentRank?->job?->name,
            $staff->currentUnit?->unit?->name,
            $staff->retirement_date?->format('d F, Y'),
            $staff->currentRank?->start_date?->format('d F, Y'),
            $staff->currentUnit?->start_date?->format('d F, Y'),
            $staff->currentRank?->job?->category->level,
        ];
    }

    public function query()
    {
        return InstitutionPerson::query()
            ->active()
            ->with([
                'person' => function ($query) {
                    $query->select(['id', 'first_name', 'surname', 'other_names', 'date_of_birth', 'gender']);
                },
                'person.identities' => function ($query) {
                    $query->where('id_type', Identity::GhanaCard)
                        ->select(['id', 'person_id', 'id_type', 'id_number']);
                },
                'person.contacts' => function ($query) {
                    $query->where('contact_type', ContactTypeEnum::PHONE)
                        ->select(['id', 'person_id', 'contact', 'contact_type']);
                },
            ])
            ->currentRank()
            ->currentUnit()
            ->toRetire();
    }
}
