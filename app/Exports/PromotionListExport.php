<?php

namespace App\Exports;

use App\Models\InstitutionPerson;
use App\Models\JobStaff;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Illuminate\Contracts\Queue\ShouldQueue;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class PromotionListExport implements
    ShouldAutoSize,
    WithHeadings,
    FromQuery,
    WithMapping,
    ShouldQueue
{
    use Exportable;

    public function headings(): array
    {
        return [
            'Staff Number',
            'File Number',
            'Name',
            'Retirement Date',
            'Current Post',
            'Date Posted',
            'Current Promotion',
            'Date Promoted',
        ];
    }

    public function map($staff): array
    {
        return [
            $staff->staff_number,
            $staff->file_number,
            $staff->person->full_name,
            $staff->person->date_of_birth ? $staff->person->date_of_birth->addYears(60)->format('Y-m-d') : null,
            $staff->units?->first()?->name,
            $staff->units?->first() ? $staff->units?->first()->pivot->start_date->format('Y-m-d') : null,
            $staff->ranks?->first()?->name,
            $staff->ranks?->first() ? $staff->ranks?->first()->pivot->start_date->format('Y-m-d') : null,
        ];
    }

    public function columnFormats(): array
    {
        return [
            'D' => NumberFormat::FORMAT_DATE_YYYYMMDDSLASH,
            'F' => NumberFormat::FORMAT_DATE_DDMMYYYY,
            'H' => NumberFormat::FORMAT_DATE_DDMMYYYY,
        ];
    }

    public function query()
    {
        return InstitutionPerson::query()
            ->active()
            ->whereHas('ranks', function ($query) {
                $query->whereYear('start_date', '<',  Date('Y') - 3);
                $query->whereNotIn('job_id', [16, 35, 49, 65, 71]);
            })
            ->with(['person', 'institution', 'units' => function ($query) {
                $query->whereNull('staff_unit.end_date');
            }, 'ranks' => function ($query) {
                $query->whereNull('end_date');
            }])
            ->orderBy(
                JobStaff::select('start_date')
                    ->whereColumn('staff_id', 'institution_person.id')
                    ->orderBy('start_date', 'desc')
                    ->limit(1)
            );
    }
}