<?php

namespace App\Exports\Qualifications;

use App\DataTransferObjects\QualificationReportFilter;
use App\Enums\QualificationLevelEnum;
use App\Models\InstitutionPerson;
use App\Models\Qualification;
use App\Services\QualificationReportService;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class QualificationByLevelExport implements FromArray, ShouldAutoSize, WithHeadings, WithStyles, WithTitle
{
    use Exportable;

    public function __construct(private readonly QualificationReportFilter $filter) {}

    public function title(): string
    {
        return 'Qualifications by Level';
    }

    public function headings(): array
    {
        return ['Level', 'Staff Count', '% of Workforce', 'Pending'];
    }

    public function array(): array
    {
        $service = app(QualificationReportService::class);
        $dist = $service->levelDistribution($this->filter);

        $totalStaff = InstitutionPerson::query()->whereNull('end_date')->count() ?: 1;

        $pendingPerLevel = Qualification::query()
            ->pending()
            ->selectRaw('level, COUNT(*) AS n')
            ->groupBy('level')
            ->pluck('n', 'level');

        $rows = [];
        foreach (QualificationLevelEnum::orderedByRank() as $case) {
            $count = $dist[$case->value] ?? 0;
            $rows[] = [
                $case->label(),
                $count,
                round(($count / $totalStaff) * 100, 1) . '%',
                (int) ($pendingPerLevel[$case->value] ?? 0),
            ];
        }

        return $rows;
    }

    public function styles(Worksheet $sheet): array
    {
        return [1 => ['font' => ['bold' => true]]];
    }
}
