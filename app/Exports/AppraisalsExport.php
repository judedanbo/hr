<?php

namespace App\Exports;

use App\Models\Appraisal;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class AppraisalsExport implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping, WithStyles, WithTitle
{
    use Exportable;

    public function __construct(protected ?int $cycleId = null) {}

    public function title(): string
    {
        return 'Appraisals';
    }

    /**
     * @return array<int, string>
     */
    public function headings(): array
    {
        return [
            'Staff Number',
            'Staff Name',
            'Cycle',
            'Unit',
            'Appraiser',
            'Reviewer',
            'Status',
            'Objectives Score',
            'Competencies Score',
            'Overall Score',
            'Band',
        ];
    }

    /**
     * @param  Appraisal  $appraisal
     * @return array<int, mixed>
     */
    public function map($appraisal): array
    {
        return [
            $appraisal->staff?->staff_number,
            $appraisal->staff?->person?->full_name,
            $appraisal->cycle?->name,
            $appraisal->unit?->name,
            $appraisal->appraiser?->person?->full_name,
            $appraisal->reviewer?->person?->full_name,
            $appraisal->status->label(),
            $appraisal->objectives_score,
            $appraisal->competencies_score,
            $appraisal->overall_score,
            $appraisal->overall_band,
        ];
    }

    public function query()
    {
        return Appraisal::query()
            ->with(['staff.person', 'cycle', 'unit', 'appraiser.person', 'reviewer.person'])
            ->when($this->cycleId, fn ($query, $cycleId) => $query->where('appraisal_cycle_id', $cycleId))
            ->latest();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
