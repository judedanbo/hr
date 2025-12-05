<?php

namespace App\Exports;

use App\Enums\ContactTypeEnum;
use App\Enums\Identity;
use App\Models\InstitutionPerson;
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

class StaffDetailsExport implements FromQuery, ShouldAutoSize, ShouldQueue, WithHeadings, WithMapping, WithStyles, WithTitle
{
    use Exportable;

    public function title(): string
    {
        return 'Staff Details';
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
            'Contact',
            'Appointment Date',
            'Years served',
            'Current Rank',
            'Current Unit',
            'Retirement Date',
            'current rank Start Date',
            'current Unit Start Date',
            'Rank Level',
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
            $staff->person->contacts->pluck('contact')->implode(', '), // Pre-filtered to phone only
            $staff->hire_date?->format('d F, Y'),
            $staff->hire_date === null ? '' : (int) Carbon::now()->diffInYears($staff->hire_date) . ' years',
            $staff->currentRank?->job?->name,
            $staff->currentUnit?->unit?->name,
            $staff->retirement_date->format('d F Y'),
            $staff->currentRank?->start_date?->format('d F, Y'),
            $staff->currentUnit?->start_date?->format('d F, Y'),
            $staff->currentRank?->job?->category->level ?? null,
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
        return InstitutionPerson::query()
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
            ->active();
    }
}
