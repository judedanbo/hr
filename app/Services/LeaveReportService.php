<?php

namespace App\Services;

use App\DataTransferObjects\LeaveReportFilter;
use App\Enums\LeavePlanStatusEnum;
use App\Enums\LeaveRequestStatusEnum;
use App\Models\InstitutionPerson;
use App\Models\LeaveBalanceAdjustment;
use App\Models\LeaveEntitlement;
use App\Models\LeavePlan;
use App\Models\LeavePlanItem;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\LeaveYear;
use App\Models\User;
use Illuminate\Support\Collection;

class LeaveReportService
{
    public function resolveYear(LeaveReportFilter $filter): ?LeaveYear
    {
        return $filter->yearId
            ? LeaveYear::find($filter->yearId)
            : LeaveYear::query()->where('is_active', true)->first();
    }

    /**
     * Scope a report to a manager's current unit unless they may view all.
     */
    public function applyUnitScope(LeaveReportFilter $filter, User $user): LeaveReportFilter
    {
        if ($user->can('view all leave reports')) {
            return $filter;
        }

        $unitId = InstitutionPerson::query()
            ->where('person_id', $user->person_id)
            ->first()?->units()->wherePivotNull('end_date')->first()?->id;

        return $unitId ? $filter->withUnitId($unitId) : $filter;
    }

    /**
     * The active staff in scope (optionally restricted to a unit).
     *
     * @return Collection<int, InstitutionPerson>
     */
    public function staffInScope(LeaveReportFilter $filter): Collection
    {
        return InstitutionPerson::query()
            ->active()
            ->whereHas('person')
            ->when($filter->unitId, fn ($query) => $query->whereHas('units', fn ($q) => $q
                ->where('units.id', $filter->unitId)
                ->whereNull('staff_unit.end_date')))
            ->with('person')
            ->get();
    }

    /**
     * Per (staff × leave type) ledger rows for the year — the basis for every
     * aggregate below.
     *
     * @return array<int, array{staff_id:int, staff:?string, unit:?string, leave_type_id:int, leave_type:string, assigned:int, planned:int, taken:int, remaining:int}>
     */
    public function staffRows(LeaveReportFilter $filter): array
    {
        $year = $this->resolveYear($filter);
        if (! $year) {
            return [];
        }

        $types = LeaveType::query()
            ->where('is_active', true)
            ->when($filter->leaveTypeId, fn ($q) => $q->whereKey($filter->leaveTypeId))
            ->orderBy('name')
            ->get();

        $staff = $this->staffInScope($filter);
        if ($staff->isEmpty() || $types->isEmpty()) {
            return [];
        }

        $staff->load([
            'units' => fn ($q) => $q->wherePivotNull('end_date'),
            'ranks' => fn ($q) => $q->wherePivotNull('end_date'),
        ]);

        $staffIds = $staff->pluck('id');
        $typeIds = $types->pluck('id');

        $entitlements = $this->entitlementIndex($year, $typeIds);
        $adjustments = $this->adjustmentIndex($year, $staffIds, $typeIds);
        $planned = $this->plannedIndex($year, $staffIds, $typeIds);
        $taken = $this->takenIndex($year, $staffIds, $typeIds);

        $rows = [];
        foreach ($staff as $member) {
            $categoryId = $member->ranks->first()?->job_category_id;
            $unitName = $member->units->first()?->name;

            foreach ($types as $type) {
                $base = $entitlements[$type->id][$categoryId !== null ? (string) $categoryId : 'default']
                    ?? $entitlements[$type->id]['default']
                    ?? 0;
                $assigned = max(0, $base + ($adjustments[$member->id][$type->id] ?? 0));

                if ($assigned < 1) {
                    continue;
                }

                $takenDays = $taken[$member->id][$type->id] ?? 0;
                $rows[] = [
                    'staff_id' => $member->id,
                    'staff' => $member->person?->full_name,
                    'unit' => $unitName,
                    'leave_type_id' => $type->id,
                    'leave_type' => $type->name,
                    'assigned' => $assigned,
                    'planned' => $planned[$member->id][$type->id] ?? 0,
                    'taken' => $takenDays,
                    'remaining' => max(0, $assigned - $takenDays),
                ];
            }
        }

        return $rows;
    }

    /**
     * Entitlement days_allowed for the year, indexed [leave_type_id][job_category_id|'default'].
     *
     * @param  Collection<int, int>  $typeIds
     * @return array<int, array<string, int>>
     */
    private function entitlementIndex(LeaveYear $year, Collection $typeIds): array
    {
        $index = [];

        LeaveEntitlement::query()
            ->where('leave_year_id', $year->id)
            ->whereIn('leave_type_id', $typeIds)
            ->get(['leave_type_id', 'job_category_id', 'days_allowed'])
            ->each(function (LeaveEntitlement $entitlement) use (&$index): void {
                $key = $entitlement->job_category_id !== null ? (string) $entitlement->job_category_id : 'default';
                $index[$entitlement->leave_type_id][$key] = (int) $entitlement->days_allowed;
            });

        return $index;
    }

    /**
     * Net manual adjustment days, indexed [staff_id][leave_type_id].
     *
     * @param  Collection<int, int>  $staffIds
     * @param  Collection<int, int>  $typeIds
     * @return array<int, array<int, int>>
     */
    private function adjustmentIndex(LeaveYear $year, Collection $staffIds, Collection $typeIds): array
    {
        $index = [];

        LeaveBalanceAdjustment::query()
            ->where('leave_year_id', $year->id)
            ->whereIn('staff_id', $staffIds)
            ->whereIn('leave_type_id', $typeIds)
            ->groupBy('staff_id', 'leave_type_id')
            ->selectRaw('staff_id, leave_type_id, COALESCE(SUM(days), 0) as days')
            ->get()
            ->each(function ($row) use (&$index): void {
                $index[$row->staff_id][$row->leave_type_id] = (int) $row->days;
            });

        return $index;
    }

    /**
     * Planned (proposed) days, indexed [staff_id][leave_type_id].
     *
     * @param  Collection<int, int>  $staffIds
     * @param  Collection<int, int>  $typeIds
     * @return array<int, array<int, int>>
     */
    private function plannedIndex(LeaveYear $year, Collection $staffIds, Collection $typeIds): array
    {
        $index = [];

        LeavePlanItem::query()
            ->join('leave_plans', 'leave_plan_items.leave_plan_id', '=', 'leave_plans.id')
            ->whereNull('leave_plans.deleted_at')
            ->where('leave_plans.leave_year_id', $year->id)
            ->whereIn('leave_plans.staff_id', $staffIds)
            ->whereIn('leave_plan_items.leave_type_id', $typeIds)
            ->groupBy('leave_plans.staff_id', 'leave_plan_items.leave_type_id')
            ->selectRaw('leave_plans.staff_id as staff_id, leave_plan_items.leave_type_id as leave_type_id, COALESCE(SUM(leave_plan_items.proposed_days), 0) as days')
            ->get()
            ->each(function ($row) use (&$index): void {
                $index[$row->staff_id][$row->leave_type_id] = (int) $row->days;
            });

        return $index;
    }

    /**
     * Days taken — approved_days for Approved plus actual_days for Completed —
     * indexed [staff_id][leave_type_id].
     *
     * @param  Collection<int, int>  $staffIds
     * @param  Collection<int, int>  $typeIds
     * @return array<int, array<int, int>>
     */
    private function takenIndex(LeaveYear $year, Collection $staffIds, Collection $typeIds): array
    {
        $index = [];

        LeaveRequest::query()
            ->where('leave_year_id', $year->id)
            ->whereIn('staff_id', $staffIds)
            ->whereIn('leave_type_id', $typeIds)
            ->whereIn('status', [LeaveRequestStatusEnum::Approved, LeaveRequestStatusEnum::Completed])
            ->groupBy('staff_id', 'leave_type_id')
            ->selectRaw(
                'staff_id, leave_type_id, COALESCE(SUM(CASE WHEN status = ? THEN COALESCE(actual_days, 0) ELSE COALESCE(approved_days, 0) END), 0) as days',
                [LeaveRequestStatusEnum::Completed->value],
            )
            ->get()
            ->each(function ($row) use (&$index): void {
                $index[$row->staff_id][$row->leave_type_id] = (int) $row->days;
            });

        return $index;
    }

    /**
     * The full report payload for the dashboard page.
     *
     * @return array<string, mixed>
     */
    public function summary(LeaveReportFilter $filter): array
    {
        $year = $this->resolveYear($filter);
        $rows = collect($this->staffRows($filter));
        $staff = $this->staffInScope($filter);

        $byType = $rows->groupBy('leave_type')
            ->map(fn (Collection $group, string $type): array => [
                'leave_type' => $type,
                'assigned' => $group->sum('assigned'),
                'planned' => $group->sum('planned'),
                'taken' => $group->sum('taken'),
                'remaining' => $group->sum('remaining'),
            ])->values()->all();

        $byStaff = $rows->groupBy('staff_id')
            ->map(fn (Collection $group): array => [
                'staff' => $group->first()['staff'],
                'unit' => $group->first()['unit'],
                'assigned' => $group->sum('assigned'),
                'planned' => $group->sum('planned'),
                'taken' => $group->sum('taken'),
                'remaining' => $group->sum('remaining'),
            ])->values()->all();

        return [
            'year' => $year?->year,
            'utilisationByType' => $byType,
            'planVsActual' => array_map(fn (array $r): array => [
                'leave_type' => $r['leave_type'],
                'planned' => $r['planned'],
                'taken' => $r['taken'],
            ], $byType),
            'staffTotals' => $byStaff,
            'liability' => (int) $rows->sum('remaining'),
            'compliance' => $this->compliance($filter, $year, $staff),
            'absencePattern' => $this->absencePattern($year, $staff),
            'kpis' => [
                'total_taken' => (int) $rows->sum('taken'),
                'total_remaining' => (int) $rows->sum('remaining'),
                'total_assigned' => (int) $rows->sum('assigned'),
                'staff_count' => $staff->count(),
            ],
        ];
    }

    /**
     * @param  Collection<int, InstitutionPerson>  $staff
     * @return array{submitted:int, total:int, rate:int, non_submitters:array<int, ?string>}
     */
    private function compliance(LeaveReportFilter $filter, ?LeaveYear $year, Collection $staff): array
    {
        if (! $year || $staff->isEmpty()) {
            return ['submitted' => 0, 'total' => $staff->count(), 'rate' => 0, 'non_submitters' => []];
        }

        $submittedIds = LeavePlan::query()
            ->where('leave_year_id', $year->id)
            ->where('status', LeavePlanStatusEnum::Submitted)
            ->whereIn('staff_id', $staff->pluck('id'))
            ->pluck('staff_id')
            ->all();

        $submitted = count($submittedIds);
        $nonSubmitters = $staff->reject(fn (InstitutionPerson $s): bool => in_array($s->id, $submittedIds, true))
            ->map(fn (InstitutionPerson $s): ?string => $s->person?->full_name)
            ->values()->all();

        return [
            'submitted' => $submitted,
            'total' => $staff->count(),
            'rate' => $staff->count() ? (int) round($submitted / $staff->count() * 100) : 0,
            'non_submitters' => $nonSubmitters,
        ];
    }

    /**
     * Absence-pattern flags: per staff number of leave spells, total days, and a
     * Bradford-style factor (spells² × days). Counts both Approved and Completed
     * (early-returned) leave, using the days actually taken for Completed spells.
     *
     * @param  Collection<int, InstitutionPerson>  $staff
     * @return array<int, array{staff:?string, spells:int, days:int, bradford:int}>
     */
    private function absencePattern(?LeaveYear $year, Collection $staff): array
    {
        if (! $year || $staff->isEmpty()) {
            return [];
        }

        $stats = LeaveRequest::query()
            ->selectRaw(
                'staff_id, COUNT(*) as spells, COALESCE(SUM(CASE WHEN status = ? THEN COALESCE(actual_days, approved_days, 0) ELSE COALESCE(approved_days, 0) END), 0) as days',
                [LeaveRequestStatusEnum::Completed->value],
            )
            ->where('leave_year_id', $year->id)
            ->whereIn('status', [LeaveRequestStatusEnum::Approved, LeaveRequestStatusEnum::Completed])
            ->whereIn('staff_id', $staff->pluck('id'))
            ->groupBy('staff_id')
            ->get()
            ->keyBy('staff_id');

        return $staff->map(function (InstitutionPerson $s) use ($stats): array {
            $row = $stats->get($s->id);
            $spells = (int) ($row->spells ?? 0);
            $days = (int) ($row->days ?? 0);

            return [
                'staff' => $s->person?->full_name,
                'spells' => $spells,
                'days' => $days,
                'bradford' => $spells * $spells * $days,
            ];
        })
            ->filter(fn (array $r): bool => $r['spells'] > 0)
            ->sortByDesc('bradford')
            ->values()
            ->all();
    }
}
