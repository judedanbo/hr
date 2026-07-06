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
     * Depth-first ordered subtree: the unit itself first, then every active
     * descendant, siblings ordered by name. Same active-unit filters as
     * descendantIds() (end_date and deleted_at must be null).
     *
     * @return array<int, array{id: int, name: string, depth: int}>
     */
    public function orderedSubtree(Unit $unit): array
    {
        $records = DB::table('units')
            ->whereIn('id', $this->descendantIdsFromId($unit->id))
            ->orderBy('name')
            ->get(['id', 'name', 'unit_id']);

        $childrenByParent = [];
        foreach ($records as $record) {
            $childrenByParent[$record->unit_id][] = $record;
        }

        $ordered = [];
        $visit = function (object $record, int $depth) use (&$visit, &$ordered, $childrenByParent): void {
            $ordered[] = ['id' => (int) $record->id, 'name' => $record->name, 'depth' => $depth];
            foreach ($childrenByParent[$record->id] ?? [] as $child) {
                $visit($child, $depth + 1);
            }
        };

        $root = $records->firstWhere('id', $unit->id);
        if ($root !== null) {
            $visit($root, 0);
        }

        return $ordered;
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
