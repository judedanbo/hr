<?php

namespace App\Exports;

use App\Models\InstitutionPerson;
use Illuminate\Contracts\Queue\ShouldQueue;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class StaffPositionExport implements FromQuery, WithMapping, WithHeadings, ShouldQueue, ShouldAutoSize
{
    use Exportable;

    public function headings(): array
    {
        return [
            'File Number',
            'Staff Number',
            'Full Name',
            'Current Rank',
            'Current Unit',
        ];
    }
    public function map($staff): array
    {
        return [
            $staff->file_number,
            $staff->staff_number,
            $staff->person->full_name,
            $staff->currentRank?->job?->name,
            $staff->currentUnit?->unit?->name,
        ];
    }
    function query()
    {
        return InstitutionPerson::query()
            ->with(['person'])
            ->currentRank()
            ->currentUnit()
            ->active();
    }
}
