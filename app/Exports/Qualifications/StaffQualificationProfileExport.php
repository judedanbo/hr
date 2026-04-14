<?php

namespace App\Exports\Qualifications;

use App\Enums\QualificationLevelEnum;
use App\Models\Person;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class StaffQualificationProfileExport implements FromArray, ShouldAutoSize, WithHeadings, WithTitle
{
    use Exportable;

    public function __construct(private readonly Person $person) {}

    public function title(): string
    {
        return "Qualifications - {$this->person->first_name} {$this->person->surname}";
    }

    public function headings(): array
    {
        return ['Qualification', 'Level', 'Institution', 'Year', 'Status', 'Approved At'];
    }

    public function array(): array
    {
        return $this->person->qualifications()
            ->orderByDesc('year')
            ->get()
            ->map(fn ($q) => [
                $q->qualification,
                $q->level ? (QualificationLevelEnum::tryFrom($q->level)?->label() ?? $q->level) : null,
                $q->institution,
                $q->year,
                $q->status?->label(),
                $q->approved_at?->format('Y-m-d'),
            ])
            ->all();
    }
}
