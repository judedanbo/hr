<?php

namespace App\Exports;

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

class PendingTransferExport implements FromQuery, WithMapping, WithHeadings, ShouldQueue, ShouldAutoSize
{
    use Exportable;

    public function headings(): array
    {
        return [
            'File Number',
            'Staff Number',
            'Full Name',
            'New Unit',
            'Current Unit',
        ];
    }
    public function map($staff): array
    {
        return [
            $staff->file_number,
            $staff->staff_number,
            $staff->person->full_name,
            $staff->currentUnit?->unit?->name,
            $staff->units->first()->name,
        ];
    }
    function query()
    {
        return InstitutionPerson::query()
            ->with(['person', 'units' => function ($query) {
                $query->oldest('start_date');
            }])
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
