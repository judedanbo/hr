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

class DepartmentsRankDistributionExport implements FromArray, ShouldAutoSize, WithHeadings, WithStyles, WithTitle
{
    use Exportable;

    private ?array $matrix = null;

    public function title(): string
    {
        return 'Ranks by Department';
    }

    public function headings(): array
    {
        $departmentNames = array_column($this->matrix()['columns'], 'name');

        return array_merge(['Rank'], $departmentNames, ['Total']);
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
        return $this->matrix ??= app(RankDistributionService::class)->matrixByDepartments();
    }
}
