<?php

namespace App\Exports;

use App\Models\Institution;
use App\Models\InstitutionPerson;
use App\Models\Unit;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class UnitStaffExport implements
    FromQuery,
    ShouldAutoSize,
    ShouldQueue,
    WithHeadings,
    WithMapping,
    WithStyles,
    WithTitle
{
    use Exportable;

    public $unit;

    public function __construct(Unit $unit)
    {
        $this->unit = $unit;
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
            'B' => ['alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT]],
        ];
    }

    public function headings(): array
    {
        return [
            'File Number',
            'Staff Number',
            'Full Name',
            'Promotion Date',
            'Current Posting',
            'Posting Date',
        ];
    }

    public function title(): string
    {
        return Str::of($this->unit->name)
            ->title()
            ->replaceMatches('/[^A-Za-z0-9]++/', '-')
            ->__toString();
    }

    public function map($staff): array
    {
        // dd($staff);
        return [
            $staff->file_number,
            $staff->staff_number,
            $staff->person->full_name,
            // $staff->current_unit_id,
            // $staff->units->first(),
            $staff->units->first()?->name,
            $staff->units->first()?->pivot->start_date?->format('d M, Y'),
            $staff->ranks->first()?->name,
            $staff->ranks->first()?->pivot->start_date?->format('d M, Y'),
            // $staff->units->first()?->pivot->end_date?->format('d M, Y'),
        ];
    }

    public function query()
    {
        return InstitutionPerson::query()
            ->active()
            ->currentUnit()
            ->where(function ($query) {
                $query->whereHas('units', function ($query) {
                    // $query->whereHas('subs', function ($query) {
                    $query->where(function ($query) {
                        $query->where('units.id', $this->unit->id);
                        $query->orWhere('units.unit_id', $this->unit->id);
                        $query->orWhere(
                            'units.unit_id',
                            'in',
                            Unit::where('units.unit_id', $this->unit->id)->select('id')
                        );
                    });
                    $query->where(function ($query) {
                        $query->whereNull('staff_unit.end_date');
                        $query->orWhere('staff_unit.end_date', '>=', now());
                    });
                });
            });
    }
}
