<?php

namespace App\Exports\RankDistribution;

use App\Models\Unit;
use App\Services\RankDistributionService;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class UnitRankDistributionExport implements FromArray, ShouldAutoSize, WithHeadings, WithStyles, WithTitle
{
    use Exportable;

    private ?array $matrix = null;

    public function __construct(private readonly Unit $unit) {}

    public function title(): string
    {
        return 'Rank Distribution';
    }

    public function headings(): array
    {
        $unitNames = array_map(
            fn (array $column) => str_repeat('  ', $column['depth']) . $column['name'],
            $this->matrix()['columns']
        );

        return array_merge(['Rank'], $unitNames, ['Total']);
    }

    public function array(): array
    {
        $matrix = $this->matrix();

        $rows = [];
        foreach ($matrix['rows'] as $rank) {
            $rows[] = array_merge([$rank['name']], array_values($rank['counts']), [$rank['total']]);
        }

        $rows[] = array_merge(['Total'], array_values($matrix['column_totals']), [$matrix['grand_total']]);

        return $rows;
    }

    public function styles(Worksheet $sheet): array
    {
        return [1 => ['font' => ['bold' => true]]];
    }

    private function matrix(): array
    {
        return $this->matrix ??= app(RankDistributionService::class)->matrixForUnit($this->unit);
    }
}
