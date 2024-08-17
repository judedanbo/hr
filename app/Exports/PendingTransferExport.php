<?php

namespace App\Exports;

use App\Enums\ContactTypeEnum;
use App\Enums\TransferStatusEnum;
use App\Models\InstitutionPerson;
use App\Models\StaffUnit;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Alignment as StyleAlignment;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PendingTransferExport implements
    FromQuery,
    WithMapping,
    WithHeadings,
    ShouldQueue,
    ShouldAutoSize,
    WithStyles,
    WithTitle
{
    use Exportable;

    public function title(): string
    {
        return 'Pending Transfers';
    }
    public function headings(): array
    {
        return [
            'File Number',
            'Staff Number',
            'Full Name',
            'Contact',
            'Current Unit',
            'New Unit',
        ];
    }
    public function map($staff): array
    {
        return [
            $staff->file_number,
            $staff->staff_number,
            $staff->person->full_name,
            $staff->person->contacts->count() > 0 ?  $staff->person->contacts->where('contact_type', ContactTypeEnum::PHONE)->map(function ($item) {
                return $item->contact;
            })->implode(', ') : '',
            $staff->currentUnit?->unit?->name,
            $staff->units->first()->name,
        ];
    }
    public function styles(Worksheet $sheet)
    {
        return [
            // Style the first row as bold text.
            1    => ['font' => ['bold' => true]],

            // Styling a specific cell by coordinate.
            'D' => ['alignment' => ['horizontal' => StyleAlignment::HORIZONTAL_LEFT]],
        ];
    }
    function query()
    {
        return InstitutionPerson::query()
            ->with([
                'person.contacts',
                'units' => function ($query) {
                    $query->oldest('start_date');
                }
            ])
            ->whereHas('units', function ($query) {
                $query->where('status', TransferStatusEnum::Pending);
            })
            ->currentUnit()
            ->active();
        // ->addSelect([
        //     'current_unit_id' => StaffUnit::select('id')
        //         ->whereColumn('institution_person.id', 'staff_unit.staff_id')
        //         ->take(1)
        //         ->latest()
        // ])->with(['currentUnit' => function ($query) {
        //     $query->with('unit:id,name');
        // }]);
    }
}
