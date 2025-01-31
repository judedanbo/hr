<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Illuminate\Support\Str;
use App\Models\InstitutionPerson;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\StringValueBinder;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class StaffListRawExport
extends StringValueBinder
implements
    FromQuery,
    ShouldAutoSize,
    WithHeadings,
    WithMapping,
    WithStyles,
    WithTitle,
    WithColumnFormatting,
    WithCustomValueBinder
{
    use Exportable;

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
            'B' => ['alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT]],
        ];
    }


    public function columnFormats(): array
    {
        return [
            'E' => DataType::TYPE_STRING,
        ];
    }
    public function title(): string
    {
        return 'Staff List';
    }
    public function headings(): array
    {
        return [
            'Staff Number',
            'First Name',
            'Last Name',
            'Gender',
            'Contact',
            'Region',
            'Current Rank',
            'Current Unit',
            'Email'
        ];
    }
    public function map($staff): array
    {
        return [
            $staff->staff_number,
            $staff->person->first_name,
            $staff->person->surname,
            $staff->person->gender?->label(),
            $staff->person->contacts?->first()?->contact,
            '',
            $staff->currentRank?->job?->name,
            $staff->currentUnit?->unit?->name,
            Str::of($staff->person->first_name)
                ->append('.')
                ->append($staff->person->surname)
                ->lower()
                ->trim()
                ->append('@audit.gov.gh')
        ];
    }
    public function query()
    {
        return InstitutionPerson::query()
            ->active()
            ->with('person')
            ->currentUnit()
            ->currentRank();
    }
}
