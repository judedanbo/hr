<?php

namespace App\Exports\Qualifications;

use App\DataTransferObjects\QualificationReportFilter;
use App\Models\InstitutionPerson;
use App\Services\QualificationReportService;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class StaffWithoutQualificationsExport implements FromArray, ShouldAutoSize, WithHeadings, WithStyles, WithTitle
{
    use Exportable;

    public function __construct(private readonly QualificationReportFilter $filter) {}

    public function title(): string
    {
        return 'Staff Without Qualifications';
    }

    public function headings(): array
    {
        return ['Staff Number', 'First Name', 'Surname', 'Hire Date'];
    }

    public function array(): array
    {
        $rows = [];
        foreach (app(QualificationReportService::class)->staffWithoutQualifications($this->filter) as $person) {
            $inst = InstitutionPerson::where('person_id', $person->id)->whereNull('end_date')->first();
            $hireDate = $inst?->hire_date;
            $rows[] = [
                $inst?->staff_number,
                $person->first_name,
                $person->surname,
                $hireDate instanceof \Carbon\Carbon ? $hireDate->format('Y-m-d') : $hireDate,
            ];
        }

        return $rows;
    }

    public function styles(Worksheet $sheet): array
    {
        return [1 => ['font' => ['bold' => true]]];
    }
}
