<?php

namespace App\Http\Controllers;

use App\Enums\OfficeTypeEnum;
use App\Enums\QualificationLevelEnum;
use App\Enums\QualificationStatusEnum;
use App\Enums\StaffTypeEnum;
use App\Models\InstitutionPerson;
use App\Models\Office;
use App\Models\Qualification;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

class StaffStatisticsController extends Controller
{
    /**
     * Return aggregate staff statistics.
     */
    public function index(): JsonResponse
    {
        $statistics = Cache::remember('staff_statistics', 3600, fn () => [
            'total_staff' => $this->totalStaff(),
            'regional_offices' => $this->officeCountByType(OfficeTypeEnum::REGIONAL),
            'district_offices' => $this->officeCountByType(OfficeTypeEnum::DISTRICT),
            'field_staff' => $this->fieldStaff(),
            'professionals' => $this->professionals(),
            'professions' => $this->professions(),
        ]);

        return response()->json($statistics);
    }

    /**
     * Count of currently active staff.
     */
    protected function totalStaff(): int
    {
        return InstitutionPerson::query()->active()->count();
    }

    /**
     * Count of offices for a given type.
     */
    protected function officeCountByType(OfficeTypeEnum $type): int
    {
        return Office::query()->where('type', $type)->count();
    }

    /**
     * Count of active field staff (audit staff).
     *
     * Field staff are identified by their current StaffType being either
     * Field (FS) or Field Support Service (FSS).
     */
    protected function fieldStaff(): int
    {
        return InstitutionPerson::query()
            ->active()
            ->whereHas('type', function ($query) {
                $query->whereIn('staff_type', [
                    StaffTypeEnum::Field->value,
                    StaffTypeEnum::SupportService->value,
                ]);
                $query->where(function ($dateQuery) {
                    $dateQuery->whereNull('end_date');
                    $dateQuery->orWhere('end_date', '>', now());
                });
            })
            ->count();
    }

    /**
     * Count of active staff who hold at least one approved
     * professional-level qualification.
     */
    protected function professionals(): int
    {
        return InstitutionPerson::query()
            ->active()
            ->whereHas('person.qualifications', function ($query) {
                $query->where('level', QualificationLevelEnum::Professional->value);
                $query->where('status', QualificationStatusEnum::Approved->value);
            })
            ->count();
    }

    /**
     * Count of distinct professions held by staff, derived from
     * approved professional-level qualifications.
     */
    protected function professions(): int
    {
        return Qualification::query()
            ->where('level', QualificationLevelEnum::Professional->value)
            ->where('status', QualificationStatusEnum::Approved->value)
            ->whereNotNull('course')
            ->where('course', '<>', '')
            ->distinct()
            ->count('course');
    }
}
