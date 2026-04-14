<?php

namespace App\Exports\Qualifications;

use App\DataTransferObjects\QualificationReportFilter;
use App\Enums\QualificationLevelEnum;
use App\Models\InstitutionPerson;
use App\Models\Qualification;
use App\Services\QualificationReportService;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class QualificationListExport implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping, WithStyles, WithTitle
{
    use Exportable;

    public function __construct(private readonly QualificationReportFilter $filter) {}

    public function title(): string
    {
        return 'Qualifications';
    }

    public function headings(): array
    {
        return [
            'Staff Number',
            'First Name',
            'Surname',
            'Qualification',
            'Level',
            'Institution',
            'Year',
            'Status',
            'Approved At',
        ];
    }

    public function query()
    {
        return app(QualificationReportService::class)
            ->applyFilter(Qualification::query(), $this->filter)
            ->with('person')
            ->orderByDesc('year');
    }

    public function map($q): array
    {
        $inst = InstitutionPerson::where('person_id', $q->person_id)->first();

        $level = null;
        if ($q->level) {
            $level = QualificationLevelEnum::tryFrom($q->level)?->label() ?? $q->level;
        }

        $status = $q->status?->label() ?? (string) $q->status;

        return [
            $inst?->staff_number,
            $q->person?->first_name,
            $q->person?->surname,
            $q->qualification,
            $level,
            $q->institution,
            $q->year,
            $status,
            $q->approved_at?->format('Y-m-d'),
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
