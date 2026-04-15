<?php

namespace App\Exports\Qualifications;

use App\DataTransferObjects\QualificationReportFilter;
use App\Enums\QualificationLevelEnum;
use App\Services\QualificationReportService;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class QualificationByUnitExport implements FromArray, ShouldAutoSize, WithHeadings, WithStyles, WithTitle
{
    use Exportable;

    public function __construct(private readonly QualificationReportFilter $filter) {}

    public function title(): string
    {
        return 'Qualifications by Unit';
    }

    public function headings(): array
    {
        $levels = collect(QualificationLevelEnum::orderedByRank())
            ->map(fn ($c) => $c->label())
            ->all();

        return array_merge(['Unit'], $levels, ['Total']);
    }

    public function array(): array
    {
        $data = app(QualificationReportService::class)->byUnit($this->filter);
        $orderedLevels = collect(QualificationLevelEnum::orderedByRank())
            ->map(fn ($c) => $c->value)
            ->all();

        $rows = [];
        foreach ($data as $unitName => $counts) {
            $row = [$unitName];
            $total = 0;
            foreach ($orderedLevels as $lv) {
                $v = $counts[$lv] ?? 0;
                $row[] = $v;
                $total += $v;
            }
            $row[] = $total;
            $rows[] = $row;
        }

        return $rows;
    }

    public function styles(Worksheet $sheet): array
    {
        return [1 => ['font' => ['bold' => true]]];
    }
}
