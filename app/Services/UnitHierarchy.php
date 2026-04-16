<?php

namespace App\Services;

use App\Models\Unit;
use Illuminate\Support\Facades\DB;

class UnitHierarchy
{
    /**
     * Return the ID of $unit plus every active descendant unit's ID at any depth.
     *
     * @return int[]
     */
    public function descendantIds(Unit $unit): array
    {
        return $this->descendantIdsFromId($unit->id);
    }

    /**
     * For each immediate active child of $unit, return its subtree IDs (child + descendants).
     *
     * @return array<int, int[]> keyed by child unit id
     */
    public function descendantIdsGroupedByChild(Unit $unit): array
    {
        $children = DB::table('units')
            ->where('unit_id', $unit->id)
            ->whereNull('end_date')
            ->whereNull('deleted_at')
            ->pluck('id')
            ->all();

        $groups = [];
        foreach ($children as $childId) {
            $groups[$childId] = $this->descendantIdsFromId((int) $childId);
        }

        return $groups;
    }

    /**
     * BFS over the active unit tree starting from $id, returning $id plus all descendant IDs.
     *
     * @return int[]
     */
    private function descendantIdsFromId(int $id): array
    {
        $collected = [$id];
        $frontier = [$id];

        while (! empty($frontier)) {
            $next = DB::table('units')
                ->whereIn('unit_id', $frontier)
                ->whereNull('end_date')
                ->whereNull('deleted_at')
                ->pluck('id')
                ->all();

            $next = array_values(array_diff($next, $collected));

            if (empty($next)) {
                break;
            }

            $collected = array_merge($collected, $next);
            $frontier = $next;
        }

        return array_values($collected);
    }
}
