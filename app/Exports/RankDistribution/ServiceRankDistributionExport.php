<?php

namespace App\Exports\RankDistribution;

use App\Services\RankDistributionService;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ServiceRankDistributionExport implements FromArray, ShouldAutoSize, WithHeadings, WithStyles, WithTitle
{
    use Exportable;

    public function title(): string
    {
        return 'Service Rank Distribution';
    }

    public function headings(): array
    {
        return ['Rank', 'Staff Count'];
    }

    public function array(): array
    {
        $distribution = app(RankDistributionService::class)->forUnits(null);

        $rows = array_map(fn (array $rank) => [$rank['name'], $rank['count']], $distribution);
        $rows[] = ['Total', array_sum(array_column($distribution, 'count'))];

        return $rows;
    }

    public function styles(Worksheet $sheet): array
    {
        return [1 => ['font' => ['bold' => true]]];
    }
}
