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

class StaffPositionExport implements FromQuery, ShouldAutoSize, ShouldQueue, WithHeadings, WithMapping, WithStyles, WithTitle
{
    use Exportable;

    /**
     * @param  array<string, mixed>  $filters
     */
    public function __construct(
        protected array $filters = []
    ) {}

    public function title(): string
    {
        return 'Staff Position';
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
            'Years Served',
            'Current Rank',
            'Current Unit',
            'level',
        ];
    }

    public function map($staff): array
    {
        return [
            $staff->file_number,
            $staff->staff_number,
            $staff->person->full_name,
            $staff->years_served . ' years',
            $staff->currentRank?->job?->name,
            $staff->currentUnit?->unit?->name,
            $staff->currentRank?->job?->category->level ?? null,
            // $staff
        ];
    }

    public function query()
    {
        return InstitutionPerson::query()
            ->active()
            ->with('person')
            ->currentRank()
            ->currentUnit()
            ->when($this->filters['rank_id'] ?? null, fn ($q, $rankId) => $q->filterByRank($rankId))
            ->when($this->filters['job_category_id'] ?? null, fn ($q, $categoryId) => $q->filterByJobCategory($categoryId))
            ->when($this->filters['unit_id'] ?? null, fn ($q, $unitId) => $q->filterByUnit($unitId))
            ->when($this->filters['department_id'] ?? null, fn ($q, $deptId) => $q->filterByDepartment($deptId))
            ->when($this->filters['gender'] ?? null, fn ($q, $gender) => $q->filterByGender($gender))
            ->when($this->filters['status'] ?? null, fn ($q, $status) => $q->filterByStatus($status))
            ->when(($this->filters['hire_date_from'] ?? null) && ($this->filters['hire_date_to'] ?? null),
                fn ($q) => $q->filterByHireDateRange($this->filters['hire_date_from'], $this->filters['hire_date_to']))
            ->when(($this->filters['hire_date_from'] ?? null) && ! ($this->filters['hire_date_to'] ?? null),
                fn ($q) => $q->filterByHireDateFrom($this->filters['hire_date_from']))
            ->when(($this->filters['hire_date_to'] ?? null) && ! ($this->filters['hire_date_from'] ?? null),
                fn ($q) => $q->filterByHireDateTo($this->filters['hire_date_to']))
            ->when(($this->filters['age_from'] ?? null) && ($this->filters['age_to'] ?? null),
                fn ($q) => $q->filterByAgeRange($this->filters['age_from'], $this->filters['age_to']))
            ->when(($this->filters['age_from'] ?? null) && ! ($this->filters['age_to'] ?? null),
                fn ($q) => $q->filterByAgeFrom($this->filters['age_from']))
            ->when(($this->filters['age_to'] ?? null) && ! ($this->filters['age_from'] ?? null),
                fn ($q) => $q->filterByAgeTo($this->filters['age_to']))
            ->search($this->filters['search'] ?? null);
    }
}
