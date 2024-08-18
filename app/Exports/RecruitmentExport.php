<?php

namespace App\Exports;

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

class RecruitmentExport implements FromQuery,
    // FromCollection,
    ShouldAutoSize, ShouldQueue, WithHeadings, WithMapping, WithStyles, WithTitle
{
    use Exportable;

    public function title(): string
    {
        return 'Recruitment';
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
            'F' => ['alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT]],
        ];
    }

    public function headings(): array
    {
        return [
            'Full Name',
            'Gender',
            'Date of Birth',
            'Date Hired',
            'Years Employed',
            'Staff Number',
            'Old Staff Number',
            'Employment Status',
            'Current Rank',
            'Current Rank Start',
            'Current Unit',
            'Current Unit Start',
        ];
    }

    public function map($staff): array
    {
        return [
            $staff->person->full_name,
            $staff->person->gender?->name,
            $staff->person->date_of_birth?->format('d F, Y'),
            $staff->hire_date?->format('d F, Y'),
            $staff->years_employed,
            $staff->staff_number,
            $staff->old_staff_number,
            $staff->status,
            $staff->ranks->count() > 0 ? $staff->ranks->first()->name : null,
            $staff->ranks->count() > 0 ? $staff->ranks->first()->pivot->start_date?->format('d F, Y') : null,
            $staff->units->count() > 0 ? $staff->units->first()->name : null,
            $staff->units->count() > 0 ? $staff->units->first()->pivot->start_date?->format('d F, Y') : null,
        ];
    }

    public function query()
    {
        return InstitutionPerson::query()
            ->with(['person', 'ranks', 'units'])
            ->when(request()->active, function ($query) {
                $query->active();
            })
            ->when(request()->retired, function ($query) {
                $query->retired();
            })
            ->when(request()->ranks, function ($query) {
                $ranks = explode('|', request()->ranks);
                foreach ($ranks as $rank) {
                    $query->orWhere('jobs.name', $rank);
                }
            });
    }
}
