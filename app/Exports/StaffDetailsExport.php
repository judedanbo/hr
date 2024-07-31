<?php

namespace App\Exports;

use App\Enums\Identity;
use App\Models\InstitutionPerson;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Maatwebsite\Excel\Concerns\Exportable;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Style\Alignment as StyleAlignment;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class StaffDetailsExport implements FromQuery, WithMapping, WithHeadings, ShouldQueue, ShouldAutoSize, WithStyles
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
            'Social Security Number',
            'Appointment Date',
            'Years served',
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
            $staff->person->identities->where('id_type', Identity::GhanaCard)->first()?->id_number,
            // $staff->person->identities->where('id_type', Identity::Social_Security_Number)->first()?->id_number,
            $staff->hire_date?->format('d F, Y'),
            $staff->hire_date === null ? '' : Carbon::now()->diffInYears($staff->hire_date) . ' years',
            $staff->currentRank?->job?->name,
            $staff->currentUnit?->unit?->name,
            $staff->person->date_of_birth?->addYears(60)->format('d F Y'),
            $staff->currentRank?->start_date?->format('d F, Y'),
            $staff->currentUnit?->start_date?->format('d F, Y'),
            $staff->currentRank?->job?->category->level ?? null,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            // Style the first row as bold text.
            1    => ['font' => ['bold' => true]],

            // Styling a specific cell by coordinate.
            'F' => ['alignment' => ['horizontal' => StyleAlignment::HORIZONTAL_LEFT]],

            // Styling an entire column.
            // 'G'  => ['font' => ['size' => 16]],
        ];
    }

    function query()
    {
        return InstitutionPerson::query()
            ->with(['person.identities'])
            ->currentRank()
            ->currentUnit()
            ->active();
    }
}
