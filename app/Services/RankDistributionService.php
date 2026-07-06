<?php

namespace App\Services;

use App\Models\Job;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class RankDistributionService
{
    public function __construct(private readonly UnitHierarchy $hierarchy) {}

    /**
     * Count active staff per rank across the given unit ids.
     * Pass null to count across every unit (service-wide).
     *
     * @param  int[]|null  $unitIds
     * @return array<int, array{id: int, name: string, full_name: string, count: int}>
     */
    public function forUnits(?array $unitIds): array
    {
        return $this->baseQuery($unitIds)
            ->select('jobs.id', 'jobs.name')
            ->selectRaw('COUNT(DISTINCT institution_person.id) as staff_count')
            ->groupBy('jobs.id', 'jobs.name', 'job_categories.level')
            ->orderBy('job_categories.level')
            ->orderBy('jobs.name')
            ->get()
            ->map(fn ($job) => [
                'id' => $job->id,
                'name' => $job->name,
                'full_name' => $job->name,
                'count' => (int) $job->staff_count,
            ])
            ->values()
            ->all();
    }

    /**
     * Rank rows against every unit in the given unit's subtree as columns,
     * depth-first order, the unit itself first.
     *
     * @return array{
     *   columns: array<int, array{id: int, name: string, depth: int}>,
     *   rows: array<int, array{id: int, name: string, counts: array<int, int>, total: int}>,
     *   column_totals: array<int, int>,
     *   grand_total: int
     * }
     */
    public function matrixForUnit(Unit $unit): array
    {
        $columns = $this->hierarchy->orderedSubtree($unit);
        $allIds = array_column($columns, 'id');

        return $this->buildMatrix($columns, $this->cellCounts($allIds), $this->forUnits($allIds));
    }

    /**
     * Rank rows against every department as columns (alphabetical), each
     * department's counts aggregated over its entire subtree.
     *
     * @return array{
     *   columns: array<int, array{id: int, name: string, depth: int}>,
     *   rows: array<int, array{id: int, name: string, counts: array<int, int>, total: int}>,
     *   column_totals: array<int, int>,
     *   grand_total: int
     * }
     */
    public function matrixByDepartments(): array
    {
        $departments = Unit::query()->departments()->orderBy('name')->get(['id', 'name']);

        $columns = [];
        $cells = [];
        $allIds = [];
        foreach ($departments as $department) {
            $columns[] = ['id' => $department->id, 'name' => $department->name, 'depth' => 0];

            $subtreeIds = $this->hierarchy->descendantIds($department);
            $allIds = array_merge($allIds, $subtreeIds);

            foreach ($this->forUnits($subtreeIds) as $rank) {
                $cells[$rank['id']][$department->id] = $rank['count'];
            }
        }

        return $this->buildMatrix($columns, $cells, $this->forUnits(array_values(array_unique($allIds))));
    }

    /**
     * Distinct staff count per rank per unit for the given unit ids.
     *
     * @param  int[]  $unitIds
     * @return array<int, array<int, int>> keyed by rank id, then unit id
     */
    private function cellCounts(array $unitIds): array
    {
        $rows = $this->baseQuery($unitIds)
            ->select('jobs.id as rank_id', 'staff_unit.unit_id')
            ->selectRaw('COUNT(DISTINCT institution_person.id) as staff_count')
            ->groupBy('jobs.id', 'staff_unit.unit_id')
            ->get();

        $cells = [];
        foreach ($rows as $row) {
            $cells[(int) $row->rank_id][(int) $row->unit_id] = (int) $row->staff_count;
        }

        return $cells;
    }

    /**
     * Rank totals come from an aggregate DISTINCT count over the full id set,
     * not a row sum, so they reconcile with the counts shown on the unit page
     * even when a staff member holds active assignments in several units.
     *
     * @param  array<int, array{id: int, name: string, depth: int}>  $columns
     * @param  array<int, array<int, int>>  $cells
     * @param  array<int, array{id: int, name: string, full_name: string, count: int}>  $rankTotals
     * @return array{
     *   columns: array<int, array{id: int, name: string, depth: int}>,
     *   rows: array<int, array{id: int, name: string, counts: array<int, int>, total: int}>,
     *   column_totals: array<int, int>,
     *   grand_total: int
     * }
     */
    private function buildMatrix(array $columns, array $cells, array $rankTotals): array
    {
        $columnTotals = array_fill_keys(array_column($columns, 'id'), 0);
        $grandTotal = 0;
        $rows = [];

        foreach ($rankTotals as $rank) {
            $counts = [];
            foreach ($columns as $column) {
                $count = $cells[$rank['id']][$column['id']] ?? 0;
                $counts[$column['id']] = $count;
                $columnTotals[$column['id']] += $count;
            }

            $grandTotal += $rank['count'];
            $rows[] = [
                'id' => $rank['id'],
                'name' => $rank['name'],
                'counts' => $counts,
                'total' => $rank['count'],
            ];
        }

        return [
            'columns' => $columns,
            'rows' => $rows,
            'column_totals' => $columnTotals,
            'grand_total' => $grandTotal,
        ];
    }

    /**
     * Active staff joined to their current rank and current unit assignment,
     * optionally restricted to the given unit ids.
     *
     * @param  int[]|null  $unitIds
     */
    private function baseQuery(?array $unitIds): Builder
    {
        return Job::query()
            ->join('job_staff', function ($join) {
                $join->on('jobs.id', '=', 'job_staff.job_id')
                    ->whereNull('job_staff.end_date')
                    ->whereNull('job_staff.deleted_at');
            })
            ->join('institution_person', 'institution_person.id', '=', 'job_staff.staff_id')
            ->join('staff_unit', function ($join) use ($unitIds) {
                $join->on('staff_unit.staff_id', '=', 'institution_person.id')
                    ->whereNull('staff_unit.end_date')
                    ->whereNull('staff_unit.deleted_at');

                if ($unitIds !== null) {
                    $join->whereIn('staff_unit.unit_id', $unitIds);
                }
            })
            ->join('job_categories', 'jobs.job_category_id', '=', 'job_categories.id')
            ->whereExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('status')
                    ->whereColumn('status.staff_id', 'institution_person.id')
                    ->whereNull('status.deleted_at')
                    ->where('status.status', 'A')
                    ->where(function ($inner) {
                        $inner->whereNull('status.end_date')
                            ->orWhere('status.end_date', '>', now());
                    });
            });
    }
}
