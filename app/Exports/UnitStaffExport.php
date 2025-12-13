<?php

namespace App\Exports;

use App\Enums\ContactTypeEnum;
use App\Enums\Identity;
use App\Models\InstitutionPerson;
use App\Models\Unit;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class UnitStaffExport implements FromQuery, ShouldAutoSize, ShouldQueue, WithHeadings, WithMapping, WithStyles, WithTitle
{
    use Exportable;

    public $unit;

    public $filters;

    public function __construct(Unit $unit, array $filters = [])
    {
        $this->unit = $unit;
        $this->filters = $filters;
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
            'B' => ['alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT]],
        ];
    }

    public function headings(): array
    {
        return [
            'File Number',
            'Staff Number',
            'Full Name',
            'Date of Birth',
            'Age',
            'Ghana Card Number',
            'Contact',
            'Appointment Date',
            'Years served',
            'Current Rank',
            'Current Unit',
            'Retirement Date',
            'current rank Start Date',
            'current Unit Start Date',
        ];
    }

    public function title(): string
    {
        return Str::of($this->unit->name)
            ->title()
            ->replaceMatches('/[^A-Za-z0-9]++/', '-')
            ->__toString();
    }

    public function map($staff): array
    {
        return [
            $staff->file_number,
            $staff->staff_number,
            $staff->person->full_name,
            $staff->person->date_of_birth?->format('d F, Y'),
            $staff->person->age . ' years',
            $staff->person->identities->where('id_type', Identity::GhanaCard)->first()?->id_number,
            $staff->person->contacts->count() > 0 ? $staff->person->contacts->where('contact_type', ContactTypeEnum::PHONE)->map(function ($item) {
                return $item->contact;
            })->implode(', ') : '',
            $staff->hire_date?->format('d F, Y'),
            $staff->years_served . ' years',
            $staff->currentRank?->job?->name,
            $staff->currentUnit?->unit?->name,
            $staff->person->date_of_birth?->addYears(60)->format('d F Y'),
            $staff->currentRank?->start_date?->format('d F, Y'),
            $staff->currentUnit?->start_date?->format('d F, Y'),
            $staff->currentRank?->job?->category->level ?? null,
        ];
    }

    public function query()
    {
        $query = InstitutionPerson::query()
            ->joinSub(
                DB::table('job_staff')
                    ->select('job_staff.*')
                    ->whereRaw('job_staff.start_date = (
                        SELECT MAX(js2.start_date)
                        FROM job_staff js2
                        WHERE js2.staff_id = job_staff.staff_id
                    )'),
                'current_job_staff',
                'current_job_staff.staff_id',
                '=',
                'institution_person.id'
            )
            ->join('jobs', 'jobs.id', '=', 'current_job_staff.job_id')
            ->join('job_categories', 'job_categories.id', '=', 'jobs.job_category_id')
            ->with(['person' => function ($query) {
                $query->with(['identities', 'contacts']);
            }])
            ->active()
            ->currentRank()
            ->currentUnit()
            ->where(function ($query) {
                $query->whereHas('units', function ($query) {
                    // $query->whereHas('subs', function ($query) {
                    $query->where(function ($query) {
                        $query->where('units.id', $this->unit->id);
                        $query->orWhere('units.unit_id', $this->unit->id);
                        $query->orWhereRaw(
                            'units.unit_id
                            in
                            (select id from units as tempUnit where tempUnit.unit_id = ?)',
                            [$this->unit->id]
                        );
                        $query->orWhereRaw(
                            'units.unit_id
                            in
                            (select id from units as tempUnit where tempUnit.unit_id
                            in
                            (select id from units as tempUnit2 where tempUnit2.unit_id = ?))',
                            [$this->unit->id]
                        );
                        $query->orWhereRaw(
                            'units.unit_id
                            in
                            (select id from units as tempUnit where tempUnit.unit_id
                            in
                            (select id from units as tempUnit2 where tempUnit2.unit_id
                            in
                            (select id from units as tempUnit3 where tempUnit3.unit_id = ?)))',
                            [$this->unit->id]
                        );
                    });
                    $query->where(function ($query) {
                        $query->whereNull('staff_unit.end_date');
                        $query->orWhere('staff_unit.end_date', '>=', now());
                    });
                });
            });

        // Apply filters
        $this->applyFilters($query);

        return $query->orderBy('job_categories.level');
    }

    protected function applyFilters($query): void
    {
        // Search filter (name, staff_number, file_number)
        if (! empty($this->filters['search'])) {
            $search = $this->filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('institution_person.staff_number', 'like', "%{$search}%")
                    ->orWhere('institution_person.file_number', 'like', "%{$search}%")
                    ->orWhereHas('person', function ($q) use ($search) {
                        $q->where('first_name', 'like', "%{$search}%")
                            ->orWhere('surname', 'like', "%{$search}%")
                            ->orWhere('other_names', 'like', "%{$search}%");
                    });
            });
        }

        // Job category filter
        if (! empty($this->filters['job_category_id'])) {
            $query->where('job_categories.id', $this->filters['job_category_id']);
        }

        // Rank/Job filter
        if (! empty($this->filters['rank_id'])) {
            $query->where('jobs.id', $this->filters['rank_id']);
        }

        // Sub-unit filter
        if (! empty($this->filters['sub_unit_id'])) {
            $query->whereHas('units', function ($q) {
                $q->where('units.id', $this->filters['sub_unit_id'])
                    ->where(function ($q) {
                        $q->whereNull('staff_unit.end_date')
                            ->orWhere('staff_unit.end_date', '>=', now());
                    });
            });
        }

        // Gender filter
        if (! empty($this->filters['gender'])) {
            $query->whereHas('person', function ($q) {
                $q->where('gender', $this->filters['gender']);
            });
        }

        // Hire date from filter
        if (! empty($this->filters['hire_date_from'])) {
            $query->where('institution_person.hire_date', '>=', $this->filters['hire_date_from']);
        }

        // Hire date to filter
        if (! empty($this->filters['hire_date_to'])) {
            $query->where('institution_person.hire_date', '<=', $this->filters['hire_date_to']);
        }

        // Age range filters (calculated from date_of_birth)
        if (! empty($this->filters['age_from'])) {
            $maxBirthDate = Carbon::now()->subYears((int) $this->filters['age_from'])->format('Y-m-d');
            $query->whereHas('person', function ($q) use ($maxBirthDate) {
                $q->where('date_of_birth', '<=', $maxBirthDate);
            });
        }

        if (! empty($this->filters['age_to'])) {
            $minBirthDate = Carbon::now()->subYears((int) $this->filters['age_to'] + 1)->format('Y-m-d');
            $query->whereHas('person', function ($q) use ($minBirthDate) {
                $q->where('date_of_birth', '>', $minBirthDate);
            });
        }
    }
}
