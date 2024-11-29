<?php

namespace App\Exports;

use App\Enums\ContactTypeEnum;
use App\Enums\Identity;
use App\Models\Institution;
use App\Models\InstitutionPerson;
use App\Models\Unit;
use Carbon\Carbon;
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
            $staff->person->date_of_birth?->format('d F, Y'),
            $staff->person->date_of_birth?->diffInYears() . ' years',
            $staff->person->identities->where('id_type', Identity::GhanaCard)->first()?->id_number,
            $staff->person->contacts->count() > 0 ? $staff->person->contacts->where('contact_type', ContactTypeEnum::PHONE)->map(function ($item) {
                return $item->contact;
            })->implode(', ') : '',
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

    public function query()
    {
        return InstitutionPerson::query()
            ->with(['person' => function ($query) {
                $query->with(['identities', 'contacts']);
            }])
            ->active()
            ->currentRank()
            ->currentUnit()
            ->where(function ($query) {
                $query->whereHas('units', function ($query) {
                    // $query->whereHas('subs', function ($query) {
                    $query->where(function ($query) {
                        $query->where('units.id', $this->unit->id);
                        $query->orWhere('units.unit_id', $this->unit->id);
                        $query->orWhereRaw(
                            'units.unit_id 
                            in 
                            (select id from units as tempUnit where tempUnit.unit_id = ?)',
                            [$this->unit->id]
                        );
                        $query->orWhereRaw(
                            'units.unit_id 
                            in 
                            (select id from units as tempUnit where tempUnit.unit_id 
                            in 
                            (select id from units as tempUnit2 where tempUnit2.unit_id = ?))',
                            [$this->unit->id]
                        );
                        $query->orWhereRaw(
                            'units.unit_id 
                            in 
                            (select id from units as tempUnit where tempUnit.unit_id 
                            in 
                            (select id from units as tempUnit2 where tempUnit2.unit_id 
                            in 
                            (select id from units as tempUnit3 where tempUnit3.unit_id = ?)))',
                            [$this->unit->id]
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
