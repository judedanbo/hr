<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class StaffUnitLookupExport implements FromArray, ShouldAutoSize, WithHeadings, WithStyles, WithTitle
{
    use Exportable;

    /**
     * @param  array<int, array<string, mixed>>  $results  Rows produced by StaffUnitLookupService.
     */
    public function __construct(private array $results) {}

    public function headings(): array
    {
        return [
            'Row',
            'Name (from file)',
            'Email (from file)',
            'Match',
            'Staff Number',
            'File Number',
            'Staff Name',
            'Current Rank',
            'Department',
            'Unit',
            'Unit Type',
            'Unit Hierarchy',
            'Assignment Start',
            'Assignment Status',
        ];
    }

    public function title(): string
    {
        return 'Departments and Units';
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }

    public function array(): array
    {
        return array_map(fn (array $result): array => [
            $result['row'],
            $result['source_name'],
            $result['source_email'],
            $result['match_type'],
            $result['staff_number'],
            $result['file_number'],
            $result['staff_name'],
            $result['rank'],
            $result['department'],
            $result['unit'],
            $result['unit_type'],
            $result['hierarchy'],
            $result['assignment_start'],
            $result['assignment_status'],
        ], $this->results);
    }
}
