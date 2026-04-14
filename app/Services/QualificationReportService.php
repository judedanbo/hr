<?php

namespace App\Services;

use App\DataTransferObjects\QualificationReportFilter;
use App\Enums\QualificationLevelEnum;
use App\Models\Qualification;
use Illuminate\Database\Eloquent\Builder;

class QualificationReportService
{
    /**
     * Count of distinct people at each level, using the highest qualification per person.
     * Returns an associative array keyed by level enum value with zero-filled entries.
     *
     * @return array<string, int>
     */
    public function levelDistribution(QualificationReportFilter $filter): array
    {
        $ranks = collect(QualificationLevelEnum::cases())
            ->mapWithKeys(fn ($case) => [$case->value => $case->rank()])
            ->all();

        $highestPerPerson = $this->applyFilter(Qualification::query(), $filter)
            ->approved()
            ->get(['person_id', 'level'])
            ->groupBy('person_id')
            ->map(function ($quals) use ($ranks) {
                $best = null;
                $bestRank = -1;
                foreach ($quals as $q) {
                    $r = $ranks[$q->level] ?? -1;
                    if ($r > $bestRank) {
                        $bestRank = $r;
                        $best = $q->level;
                    }
                }

                return $best;
            });

        $counts = [];
        foreach (QualificationLevelEnum::cases() as $case) {
            $counts[$case->value] = 0;
        }
        foreach ($highestPerPerson as $level) {
            if ($level !== null && isset($counts[$level])) {
                $counts[$level]++;
            }
        }

        return $counts;
    }

    /**
     * Apply filter criteria to a qualifications query. Handles simple column filters.
     * Relationship-based filters (unit, department, gender, job_category) will be added
     * in later tasks when the joins/scopes are wired up.
     */
    public function applyFilter(Builder $query, QualificationReportFilter $filter): Builder
    {
        if ($filter->level) {
            $query->where('level', $filter->level);
        }
        if ($filter->status) {
            $query->where('status', $filter->status);
        }
        if ($filter->yearFrom) {
            $query->where('year', '>=', (string) $filter->yearFrom);
        }
        if ($filter->yearTo) {
            $query->where('year', '<=', (string) $filter->yearTo);
        }
        if ($filter->institution) {
            $query->where('institution', 'like', "%{$filter->institution}%");
        }
        if ($filter->course) {
            $query->where('course', 'like', "%{$filter->course}%");
        }

        // TODO(later-tasks): unit/department/gender/job_category relationship filters.
        return $query;
    }
}
