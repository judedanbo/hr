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


class PositionsExport implements
    FromQuery,
    WithMapping,
    WithHeadings,
    ShouldQueue,
    ShouldAutoSize
{
    use Exportable;
    public function headings(): array
    {
        return [
            'File Number',
            'Staff Number',
            'Full Name',
            'Years Served',
            'Current Rank',
            'Current Unit',
            'Position',
            'Position Date',
            'Level'
        ];
    }
    public function map($staff): array
    {
        return [
            $staff->file_number,
            $staff->staff_number,
            $staff->person->full_name,
            $staff->hire_date === null ? '' : Carbon::now()->diffInYears($staff->hire_date) . ' years',
            $staff->currentRank?->job?->name,
            $staff->currentUnit?->unit?->name,
            $staff->positions->first()?->name,
            Carbon::parse($staff->positions->first()?->pivot->start_date)?->format('d M, Y'),
            $staff->currentRank?->job?->category->level ?? null,

        ];
    }
    function query()
    {
        return InstitutionPerson::query()
            ->whereHas('positions')
            ->with(['person', 'ranks.category', 'positions'])
            ->currentRank()
            ->currentUnit()
            ->active();
    }
}
