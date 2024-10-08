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

class AllSeparatedExport implements FromQuery, ShouldAutoSize, ShouldQueue, WithHeadings, WithMapping, WithStyles, WithTitle
{
    use Exportable;

    public function title(): string
    {
        return 'All Separated Staff';
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            // Style the first row as bold text.
            1 => ['font' => ['bold' => true]],
            'B' => ['alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT]],

            'H' => ['alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT]],
        ];
    }

    public function headings(): array
    {
        return [
            'File Number',
            'Staff Number',
            'Full Name',
            'Rank',
            'Separation Date',
            'Years served',
            'Ghana Card Number',
            'Contact',
            'Type',

        ];
    }

    public function map($staff): array
    {
        return [
            $staff->file_number,
            $staff->staff_number,
            $staff->person->full_name,
            $staff->currentRank?->job?->name,
            $staff->statuses->first()->start_date?->format('d F, Y'),
            $staff->hire_date->diff($staff->statuses->first()->start_date)->format('%y years'),
            $staff->person->identities->where('id_type', Identity::GhanaCard)->first()?->id_number,
            $staff->person->contacts->count() > 0 ? $staff->person->contacts->where('contact_type', ContactTypeEnum::PHONE)->map(function ($item) {
                return $item->contact;
            })->implode(', ') : '',
            $staff->statuses->first()->status->label(),
            $staff->person->contacts->filter(function ($contact) {
                return $contact->contact_type == ContactTypeEnum::EMERGENCY;
            })->first()?->contact ?? '',
        ];
    }

    public function query()
    {
        return InstitutionPerson::query()
            ->with(['person' => function ($query) {
                $query->with(['contacts', 'identities']);
            }, 'statuses' => function ($query) {
                $query->latest('start_date');
            }])
            ->currentRank()
            ->retired();
    }
}
