<?php

namespace App\Exports;

use App\Enums\ContactTypeEnum;
use App\Enums\EmployeeStatusEnum;
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

class SeparatedVacatedPostExport implements FromQuery, ShouldAutoSize, ShouldQueue, WithHeadings, WithMapping, WithStyles, WithTitle
{
    use Exportable;

    public function title(): string
    {
        return 'Separated (Vacated Post)';
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => 1]],
            'B' => ['alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT]],
            'F' => ['alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT]],
        ];
    }

    public function headings(): array
    {
        return [
            'File Number',
            'Staff Number',
            'Full Name',
            'Rank',
            // 'Status',
            'Date',
            'contact',
        ];
    }

    public function map($staff): array
    {
        return [
            $staff->file_number,
            $staff->staff_number,
            $staff->person->full_name,
            $staff->currentRank?->job?->name,
            // $staff->statuses?->first()->status->label(),
            $staff->statuses->first()->start_date?->format('d F, Y'),
            $staff->person->contacts->filter(function ($contact) {
                return $contact->contact_type == ContactTypeEnum::PHONE;
            })->first()?->contact ?? '',
            $staff->person->contacts->filter(function ($contact) {
                return $contact->contact_type == ContactTypeEnum::EMERGENCY;
            })->first()?->contact ?? '',
        ];
    }

    public function query()
    {
        return InstitutionPerson::query()
            ->with(['person.contacts', 'statuses' => function ($query) {
                $query->latest('start_date');
            }])
            ->currentRank()
            ->whereHas('statuses', function ($query) {
                $query->where('status', EmployeeStatusEnum::Vacation);
            })
            ->retired();
    }
}
