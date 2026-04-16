<?php

namespace App\Services;

use App\DataTransferObjects\QualificationReportFilter;
use App\Enums\QualificationLevelEnum;
use App\Enums\QualificationStatusEnum;
use App\Models\InstitutionPerson;
use App\Models\Qualification;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

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
        return $this->remember('levelDistribution', $filter, function () use ($filter) {
            $highestPerPerson = $this->applyFilter(Qualification::query(), $filter)
                ->approved()
                ->get(['person_id', 'level'])
                ->groupBy('person_id')
                ->map(function ($quals) {
                    $best = null;
                    $bestRank = -1;
                    foreach ($quals as $q) {
                        $case = QualificationLevelEnum::normalize($q->level);
                        if ($case === null) {
                            continue;
                        }
                        if ($case->rank() > $bestRank) {
                            $bestRank = $case->rank();
                            $best = $case->value;
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
        });
    }

    /**
     * Qualification counts per current unit, using highest qualification per person-per-unit.
     *
     * @return array<string, array<string, int>> ['Unit A' => ['masters' => 3, 'degree' => 5, ...], ...]
     */
    public function byUnit(QualificationReportFilter $filter): array
    {
        return $this->remember('byUnit', $filter, function () use ($filter) {
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
                $case = QualificationLevelEnum::normalize($row->level);
                if ($case === null) {
                    continue;
                }
                $currentLevel = $highest[$row->unit_name][$row->person_id] ?? null;
                $currentRank = $currentLevel !== null ? ($ranks[$currentLevel] ?? -1) : -1;
                if ($case->rank() > $currentRank) {
                    $highest[$row->unit_name][$row->person_id] = $case->value;
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
        });
    }

    /**
     * People with no approved qualification record.
     * Restricted to Person records that have at least one active InstitutionPerson (end_date IS NULL).
     *
     * @return \Illuminate\Support\Collection<int, \App\Models\Person>
     */
    public function staffWithoutQualifications(QualificationReportFilter $filter): \Illuminate\Support\Collection
    {
        return $this->remember('staffWithoutQualifications', $filter, function () {
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
        });
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
     * Apply filter criteria to a qualifications query.
     * All column references are table-qualified so the filter can be safely
     * composed with queries that join related tables sharing column names
     * (e.g. staff_unit.status, institution_person.end_date).
     */
    public function applyFilter(Builder $query, QualificationReportFilter $filter): Builder
    {
        if ($filter->level) {
            $query->where('qualifications.level', $filter->level);
        }
        if ($filter->status) {
            $query->where('qualifications.status', $filter->status);
        }
        if ($filter->yearFrom) {
            $query->where('qualifications.year', '>=', (string) $filter->yearFrom);
        }
        if ($filter->yearTo) {
            $query->where('qualifications.year', '<=', (string) $filter->yearTo);
        }
        if ($filter->institution) {
            $query->where('qualifications.institution', 'like', "%{$filter->institution}%");
        }
        if ($filter->course) {
            $query->where('qualifications.course', 'like', "%{$filter->course}%");
        }

        if ($filter->gender) {
            $query->whereExists(function ($q) use ($filter) {
                $q->select(DB::raw(1))
                    ->from('people')
                    ->whereColumn('people.id', 'qualifications.person_id')
                    ->where('people.gender', $filter->gender);
            });
        }

        if ($filter->unitId) {
            $query->whereExists(function ($q) use ($filter) {
                $q->select(DB::raw(1))
                    ->from('staff_unit')
                    ->join('institution_person', 'staff_unit.staff_id', '=', 'institution_person.id')
                    ->whereColumn('institution_person.person_id', 'qualifications.person_id')
                    ->whereNull('staff_unit.end_date')
                    ->where('staff_unit.unit_id', $filter->unitId);
            });
        }

        if ($filter->departmentId) {
            $descendants = $this->unitDescendantIds($filter->departmentId);
            $query->whereExists(function ($q) use ($descendants) {
                $q->select(DB::raw(1))
                    ->from('staff_unit')
                    ->join('institution_person', 'staff_unit.staff_id', '=', 'institution_person.id')
                    ->whereColumn('institution_person.person_id', 'qualifications.person_id')
                    ->whereNull('staff_unit.end_date')
                    ->whereIn('staff_unit.unit_id', $descendants);
            });
        }

        return $query;
    }

    /**
     * All unit IDs under (and including) the given unit — walked via `units.unit_id` parent chain.
     *
     * @return array<int, int>
     */
    private function unitDescendantIds(int $rootId): array
    {
        $ids = [$rootId];
        $frontier = [$rootId];
        while (! empty($frontier)) {
            $children = DB::table('units')
                ->whereIn('unit_id', $frontier)
                ->pluck('id')
                ->all();
            $frontier = array_values(array_diff($children, $ids));
            $ids = array_merge($ids, $frontier);
        }

        return $ids;
    }

    /**
     * @return array<int, array{name: string, count: int}>
     */
    public function topInstitutions(QualificationReportFilter $filter, int $limit = 10): array
    {
        return $this->remember("topInstitutions:{$limit}", $filter, function () use ($filter, $limit) {
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
        });
    }

    /**
     * Top qualification titles (free-text `qualification` column) with casing/trimming normalized.
     *
     * @return array<int, array{name: string, count: int}>
     */
    public function topQualifications(QualificationReportFilter $filter, int $limit = 10): array
    {
        return $this->remember("topQualifications:{$limit}", $filter, function () use ($filter, $limit) {
            $rows = $this->applyFilter(Qualification::query(), $filter)
                ->approved()
                ->whereNotNull('qualification')
                ->where('qualification', '!=', '')
                ->get(['qualification']);

            $groups = [];
            foreach ($rows as $row) {
                $key = mb_strtolower(trim($row->qualification));
                if ($key === '') {
                    continue;
                }
                if (! isset($groups[$key])) {
                    $groups[$key] = ['count' => 0, 'labels' => []];
                }
                $groups[$key]['count']++;
                $groups[$key]['labels'][$row->qualification] = ($groups[$key]['labels'][$row->qualification] ?? 0) + 1;
            }

            $out = [];
            foreach ($groups as $data) {
                arsort($data['labels']);
                $out[] = ['name' => array_key_first($data['labels']), 'count' => $data['count']];
            }
            usort($out, fn ($a, $b) => $b['count'] <=> $a['count']);

            return array_slice($out, 0, $limit);
        });
    }

    /**
     * Highest-qualification-per-person count split by gender.
     *
     * @return array<string, array{M: int, F: int}> keyed by level enum value
     */
    public function levelByGender(QualificationReportFilter $filter): array
    {
        return $this->remember('levelByGender', $filter, function () use ($filter) {
            $rows = $this->applyFilter(Qualification::query(), $filter)
                ->approved()
                ->join('people', 'qualifications.person_id', '=', 'people.id')
                ->whereIn('people.gender', ['M', 'F'])
                ->get([
                    'qualifications.person_id',
                    'qualifications.level',
                    'people.gender',
                ]);

            // Highest level per person
            $highest = [];  // [person_id] => ['level' => enumValue, 'gender' => M|F]
            foreach ($rows as $row) {
                $case = QualificationLevelEnum::normalize($row->level);
                if ($case === null) {
                    continue;
                }
                $current = $highest[$row->person_id] ?? null;
                if ($current === null || $case->rank() > $current['rank']) {
                    $highest[$row->person_id] = [
                        'level' => $case->value,
                        'rank' => $case->rank(),
                        'gender' => $row->gender,
                    ];
                }
            }

            $out = [];
            foreach (QualificationLevelEnum::cases() as $case) {
                $out[$case->value] = ['M' => 0, 'F' => 0];
            }
            foreach ($highest as $entry) {
                $gender = $entry['gender'] === 'M' ? 'M' : 'F';
                $out[$entry['level']][$gender]++;
            }

            return $out;
        });
    }

    /**
     * @return array{count: int, sparkline: array<int, int>, oldestDays: int|null} 30-day daily submissions, newest last; oldestDays is whole days since earliest pending record.
     */
    public function pendingApprovalsStats(?QualificationReportFilter $filter = null): array
    {
        $scope = $this->filterForPending($filter);

        return $this->remember('pendingApprovalsStats', $scope, function () use ($scope) {
            $count = $this->applyFilter(Qualification::query(), $scope)->pending()->count();

            $since = now()->subDays(29)->startOfDay();
            $daily = $this->applyFilter(Qualification::query(), $scope)
                ->pending()
                ->where('qualifications.created_at', '>=', $since)
                ->selectRaw('DATE(qualifications.created_at) AS d, COUNT(*) AS n')
                ->groupBy('d')
                ->pluck('n', 'd');

            $sparkline = [];
            for ($i = 29; $i >= 0; $i--) {
                $date = now()->subDays($i)->toDateString();
                $sparkline[] = (int) ($daily[$date] ?? 0);
            }

            $oldest = $this->applyFilter(Qualification::query(), $scope)->pending()->min('qualifications.created_at');
            $oldestDays = $oldest
                ? (int) Carbon::parse($oldest)->diffInDays(now())
                : null;

            return ['count' => $count, 'sparkline' => $sparkline, 'oldestDays' => $oldestDays];
        });
    }

    /**
     * Strip the status clause from the filter so the pending KPI keeps its
     * "pending" meaning even when the rest of the report is filtered by a
     * different status (e.g. Approved). Other clauses — dept/unit/year/etc. —
     * still narrow the count.
     */
    private function filterForPending(?QualificationReportFilter $filter): QualificationReportFilter
    {
        if ($filter === null) {
            return new QualificationReportFilter;
        }

        return new QualificationReportFilter(
            unitId: $filter->unitId,
            departmentId: $filter->departmentId,
            level: $filter->level,
            status: null,
            yearFrom: $filter->yearFrom,
            yearTo: $filter->yearTo,
            gender: $filter->gender,
            jobCategoryId: $filter->jobCategoryId,
            institution: $filter->institution,
            course: $filter->course,
        );
    }

    /**
     * Count of active staff (InstitutionPerson with no end_date), optionally
     * narrowed by the filter's department_id / unit_id. Serves as the
     * denominator for "covered / without quals" percentages in the KPI row.
     */
    public function activeStaffCount(?QualificationReportFilter $filter = null): int
    {
        $query = InstitutionPerson::query()->whereNull('end_date');

        if ($filter?->unitId) {
            $unitId = $filter->unitId;
            $query->whereExists(function ($q) use ($unitId) {
                $q->select(DB::raw(1))
                    ->from('staff_unit')
                    ->whereColumn('staff_unit.staff_id', 'institution_person.id')
                    ->whereNull('staff_unit.end_date')
                    ->where('staff_unit.unit_id', $unitId);
            });
        } elseif ($filter?->departmentId) {
            $descendants = $this->unitDescendantIds($filter->departmentId);
            $query->whereExists(function ($q) use ($descendants) {
                $q->select(DB::raw(1))
                    ->from('staff_unit')
                    ->whereColumn('staff_unit.staff_id', 'institution_person.id')
                    ->whereNull('staff_unit.end_date')
                    ->whereIn('staff_unit.unit_id', $descendants);
            });
        }

        return $query->count();
    }

    /**
     * Scope a filter to the user's accessible units based on their permissions.
     *
     * - view.all  → filter is returned unchanged.
     * - view.own_unit → resolve the user's current unit and inject it into the filter.
     *   If the unit cannot be resolved the filter is returned unchanged.
     * - no matching permission → filter returned unchanged.
     */
    public function applyUnitScope(QualificationReportFilter $filter, User $user): QualificationReportFilter
    {
        if ($user->can('qualifications.reports.view.all')) {
            return $filter;
        }

        if ($user->can('qualifications.reports.view.own_unit')) {
            $unitId = $this->resolveCurrentUnitId($user);
            if ($unitId !== null) {
                return $filter->withUnitId($unitId);
            }
        }

        return $filter;
    }

    private function resolveCurrentUnitId(User $user): ?int
    {
        if ($user->person_id === null) {
            return null;
        }

        $row = DB::table('staff_unit')
            ->join('institution_person', 'staff_unit.staff_id', '=', 'institution_person.id')
            ->where('institution_person.person_id', $user->person_id)
            ->whereNull('institution_person.end_date')
            ->whereNull('staff_unit.end_date')
            ->orderByDesc('staff_unit.start_date')
            ->select('staff_unit.unit_id')
            ->first();

        return $row?->unit_id;
    }

    private function remember(string $method, QualificationReportFilter $filter, \Closure $callback): mixed
    {
        $version = Cache::get('qual-report:version', 0);
        $key = "qual-report:v{$version}:{$method}:{$filter->cacheKey()}";

        return Cache::remember($key, now()->addMinutes(10), $callback);
    }

    /**
     * @return array<int, int> [2018 => 3, 2020 => 2]
     */
    public function trendByYear(QualificationReportFilter $filter): array
    {
        return $this->remember('trendByYear', $filter, function () use ($filter) {
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
        });
    }
}
