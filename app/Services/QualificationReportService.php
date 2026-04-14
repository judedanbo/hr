<?php

namespace App\Services;

use App\DataTransferObjects\QualificationReportFilter;
use App\Enums\QualificationLevelEnum;
use App\Enums\QualificationStatusEnum;
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
     * Qualification counts per current unit, using highest qualification per person-per-unit.
     *
     * @return array<string, array<string, int>> ['Unit A' => ['masters' => 3, 'degree' => 5, ...], ...]
     */
    public function byUnit(QualificationReportFilter $filter): array
    {
        $ranks = collect(QualificationLevelEnum::cases())
            ->mapWithKeys(fn ($c) => [$c->value => $c->rank()])
            ->all();

        $rows = $this->applyFilter(Qualification::query(), $filter)
            ->where('qualifications.status', QualificationStatusEnum::Approved)
            ->join('people', 'qualifications.person_id', '=', 'people.id')
            ->join('institution_person', 'people.id', '=', 'institution_person.person_id')
            ->join('staff_unit', function ($j) {
                $j->on('staff_unit.staff_id', '=', 'institution_person.id')
                    ->whereNull('staff_unit.end_date');
            })
            ->join('units', 'staff_unit.unit_id', '=', 'units.id')
            ->get([
                'qualifications.person_id',
                'qualifications.level',
                'units.id as unit_id',
                'units.name as unit_name',
            ]);

        $highest = [];
        foreach ($rows as $row) {
            $currentLevel = $highest[$row->unit_name][$row->person_id] ?? null;
            $newRank = $ranks[$row->level] ?? -1;
            $currentRank = $currentLevel !== null ? ($ranks[$currentLevel] ?? -1) : -1;
            if ($newRank > $currentRank) {
                $highest[$row->unit_name][$row->person_id] = $row->level;
            }
        }

        $result = [];
        foreach ($highest as $unitName => $personLevels) {
            $result[$unitName] = [];
            foreach (QualificationLevelEnum::cases() as $case) {
                $result[$unitName][$case->value] = 0;
            }
            foreach ($personLevels as $level) {
                $result[$unitName][$level]++;
            }
        }

        return $result;
    }

    /**
     * People with no approved qualification record.
     * Restricted to Person records that have at least one active InstitutionPerson (end_date IS NULL).
     *
     * @return \Illuminate\Support\Collection<int, \App\Models\Person>
     */
    public function staffWithoutQualifications(QualificationReportFilter $filter): \Illuminate\Support\Collection
    {
        return \App\Models\Person::query()
            ->whereExists(function ($q) {
                $q->select(\Illuminate\Support\Facades\DB::raw(1))
                    ->from('institution_person')
                    ->whereColumn('institution_person.person_id', 'people.id')
                    ->whereNull('institution_person.end_date');
            })
            ->whereDoesntHave('qualifications', function ($q) {
                $q->where('status', QualificationStatusEnum::Approved);
            })
            ->get();
    }

    /**
     * Paginated list of qualifications with eager-loaded person context.
     */
    public function staffList(
        QualificationReportFilter $filter,
        int $perPage = 25,
    ): \Illuminate\Contracts\Pagination\LengthAwarePaginator {
        return $this->applyFilter(Qualification::query(), $filter)
            ->with(['person'])
            ->orderByDesc('year')
            ->paginate($perPage)
            ->withQueryString();
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

    /**
     * @return array<int, array{name: string, count: int}>
     */
    public function topInstitutions(QualificationReportFilter $filter, int $limit = 10): array
    {
        $rows = $this->applyFilter(Qualification::query(), $filter)
            ->approved()
            ->whereNotNull('institution')
            ->where('institution', '!=', '')
            ->get(['institution']);

        $groups = [];
        foreach ($rows as $row) {
            $key = mb_strtolower(trim($row->institution));
            if ($key === '') {
                continue;
            }
            if (! isset($groups[$key])) {
                $groups[$key] = ['count' => 0, 'labels' => []];
            }
            $groups[$key]['count']++;
            $groups[$key]['labels'][$row->institution] = ($groups[$key]['labels'][$row->institution] ?? 0) + 1;
        }

        $out = [];
        foreach ($groups as $data) {
            arsort($data['labels']);
            $displayLabel = array_key_first($data['labels']);
            $out[] = ['name' => $displayLabel, 'count' => $data['count']];
        }
        usort($out, fn ($a, $b) => $b['count'] <=> $a['count']);

        return array_slice($out, 0, $limit);
    }

    /**
     * @return array{count: int, sparkline: array<int, int>} 30-day daily submissions, newest last.
     */
    public function pendingApprovalsStats(): array
    {
        $count = Qualification::query()->pending()->count();

        $since = now()->subDays(29)->startOfDay();
        $daily = Qualification::query()
            ->pending()
            ->where('created_at', '>=', $since)
            ->selectRaw('DATE(created_at) AS d, COUNT(*) AS n')
            ->groupBy('d')
            ->pluck('n', 'd');

        $sparkline = [];
        for ($i = 29; $i >= 0; $i--) {
            $date = now()->subDays($i)->toDateString();
            $sparkline[] = (int) ($daily[$date] ?? 0);
        }

        return ['count' => $count, 'sparkline' => $sparkline];
    }

    /**
     * @return array<int, int> [2018 => 3, 2020 => 2]
     */
    public function trendByYear(QualificationReportFilter $filter): array
    {
        $rows = $this->applyFilter(Qualification::query(), $filter)
            ->approved()
            ->whereNotNull('year')
            ->where('year', '!=', '')
            ->selectRaw('year, COUNT(*) AS n')
            ->groupBy('year')
            ->get();

        $out = [];
        foreach ($rows as $row) {
            if (is_numeric($row->year)) {
                $out[(int) $row->year] = (int) $row->n;
            }
        }
        ksort($out);

        return $out;
    }
}
