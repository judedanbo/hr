<?php

namespace App\Transformers\Staff;

use App\Models\InstitutionPerson;

class StaffListTransformer
{
    /**
     * Transform a staff record for list/index display.
     */
    public function transform(InstitutionPerson $staff): array
    {
        return [
            'id' => $staff->id,
            'file_number' => $staff->file_number,
            'staff_number' => $staff->staff_number,
            'old_staff_number' => $staff->old_staff_number,
            'hire_date' => $staff->hire_date?->format('d M Y'),
            'hire_date_distance' => $staff->hire_date?->diffForHumans(),
            'initials' => $staff->person->initials,
            'name' => $staff->person->full_name,
            'gender' => $staff->person->gender?->label(),
            'dob' => $staff->person->date_of_birth?->format('d M Y'),
            'image' => $staff->person->image ? '/storage/' . $staff->person->image : null,
            'age' => $staff->person->age . ' years old',
            'retirement_date' => $staff->retirement_date_formatted,
            'retirement_date_distance' => $staff->retirement_date_diff,
            'current_rank' => $this->transformCurrentRank($staff),
            'current_unit' => $this->transformCurrentUnit($staff),
        ];
    }

    /**
     * Transform current rank data.
     */
    protected function transformCurrentRank(InstitutionPerson $staff): ?array
    {
        $currentRank = $staff->currentRank;

        if (! $currentRank) {
            return null;
        }

        return [
            'id' => $currentRank->id,
            'name' => $currentRank->job?->name,
            'job_id' => $currentRank->name,
            'start_date' => $currentRank->start_date?->format('d M Y'),
            'start_date_distance' => $currentRank->start_date?->diffForHumans(),
            'end_date' => $currentRank->end_date?->format('d M Y'),
            'remarks' => $currentRank->remarks,
        ];
    }

    /**
     * Transform current unit data.
     */
    protected function transformCurrentUnit(InstitutionPerson $staff): ?array
    {
        $currentUnit = $staff->currentUnit;

        if (! $currentUnit) {
            return null;
        }

        return [
            'id' => $currentUnit->unit_id,
            'rank' => $currentUnit,
            'name' => $currentUnit->unit?->name,
            'start_date' => $currentUnit->start_date?->format('d M Y'),
            'start_date_distance' => $currentUnit->start_date?->diffForHumans(),
            'end_date' => $currentUnit->end_date?->format('d M Y'),
        ];
    }

    /**
     * Transform a collection of staff for paginated results.
     * Use this with paginator's through() method.
     */
    public function transformForPagination(): callable
    {
        return fn (InstitutionPerson $staff) => $this->transform($staff);
    }
}
