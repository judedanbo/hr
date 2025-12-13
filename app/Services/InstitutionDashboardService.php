<?php

namespace App\Services;

use App\Models\Institution;
use App\Models\InstitutionPerson;
use App\Models\Job;
use App\Models\Unit;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class InstitutionDashboardService
{
    /**
     * Cache TTL in seconds (15 minutes)
     */
    protected const CACHE_TTL = 900;

    /**
     * Get all dashboard data for an institution
     */
    public function getDashboardData(Institution $institution): array
    {
        return [
            'overview' => $this->getOverviewStats($institution),
            'trends' => $this->getTrends($institution),
            'analytics' => $this->getAnalytics($institution),
            'action_items' => $this->getActionItems($institution),
            'departments' => $this->getDepartmentBreakdown($institution),
        ];
    }

    /**
     * Get overview statistics
     */
    public function getOverviewStats(Institution $institution): array
    {
        return Cache::remember(
            "institution.{$institution->id}.dashboard.overview",
            self::CACHE_TTL,
            function () use ($institution) {
                $baseQuery = InstitutionPerson::query()
                    ->where('institution_id', $institution->id);

                $activeStaff = (clone $baseQuery)->active()->count();
                $maleCount = (clone $baseQuery)->active()->maleStaff()->count();
                $femaleCount = (clone $baseQuery)->active()->femaleStaff()->count();
                $retiredCount = (clone $baseQuery)->retired()->count();

                // New hires this year
                $newHiresThisYear = (clone $baseQuery)
                    ->active()
                    ->whereYear('hire_date', now()->year)
                    ->count();

                // New hires last year (for comparison)
                $newHiresLastYear = (clone $baseQuery)
                    ->whereYear('hire_date', now()->subYear()->year)
                    ->count();

                // Calculate average tenure in years
                $avgTenure = (clone $baseQuery)
                    ->active()
                    ->whereNotNull('hire_date')
                    ->selectRaw('AVG(DATEDIFF(NOW(), hire_date) / 365.25) as avg_tenure')
                    ->value('avg_tenure');

                // Organization counts
                $departmentsCount = $institution->departments()->count();
                $divisionsCount = $institution->divisions()->count();
                $unitsCount = $institution->units()->count();

                return [
                    'active_staff' => $activeStaff,
                    'male_count' => $maleCount,
                    'female_count' => $femaleCount,
                    'retired_count' => $retiredCount,
                    'new_hires_this_year' => $newHiresThisYear,
                    'new_hires_last_year' => $newHiresLastYear,
                    'avg_tenure_years' => round($avgTenure ?? 0, 1),
                    'departments_count' => $departmentsCount,
                    'divisions_count' => $divisionsCount,
                    'units_count' => $unitsCount,
                ];
            }
        );
    }

    /**
     * Get trend data for charts
     */
    public function getTrends(Institution $institution): array
    {
        return Cache::remember(
            "institution.{$institution->id}.dashboard.trends",
            self::CACHE_TTL,
            function () use ($institution) {
                return [
                    'recruitment' => $this->getRecruitmentTrends($institution),
                    'separations' => $this->getSeparationTrends($institution),
                ];
            }
        );
    }

    /**
     * Get recruitment trends by year
     */
    protected function getRecruitmentTrends(Institution $institution): array
    {
        return InstitutionPerson::query()
            ->where('institution_id', $institution->id)
            ->join('people', 'people.id', '=', 'institution_person.person_id')
            ->whereNotNull('institution_person.hire_date')
            ->whereYear('institution_person.hire_date', '>=', now()->subYears(10)->year)
            ->select(DB::raw(
                "YEAR(institution_person.hire_date) as year,
                SUM(CASE WHEN people.gender = 'M' THEN 1 ELSE 0 END) as male,
                SUM(CASE WHEN people.gender = 'F' THEN 1 ELSE 0 END) as female,
                COUNT(*) as total"
            ))
            ->groupByRaw('YEAR(institution_person.hire_date)')
            ->orderBy('year', 'asc')
            ->get()
            ->toArray();
    }

    /**
     * Get separation trends by year
     */
    protected function getSeparationTrends(Institution $institution): array
    {
        return InstitutionPerson::query()
            ->where('institution_id', $institution->id)
            ->whereNotNull('end_date')
            ->whereYear('end_date', '>=', now()->subYears(10)->year)
            ->select(DB::raw(
                'YEAR(end_date) as year,
                COUNT(*) as count'
            ))
            ->groupByRaw('YEAR(end_date)')
            ->orderBy('year', 'asc')
            ->get()
            ->toArray();
    }

    /**
     * Get analytics data for charts
     */
    public function getAnalytics(Institution $institution): array
    {
        return Cache::remember(
            "institution.{$institution->id}.dashboard.analytics",
            self::CACHE_TTL,
            function () use ($institution) {
                return [
                    'gender' => $this->getGenderDistribution($institution),
                    'age_distribution' => $this->getAgeDistribution($institution),
                    'status' => $this->getStatusDistribution($institution),
                    'tenure_distribution' => $this->getTenureDistribution($institution),
                    'rank_distribution' => $this->getRankDistribution($institution),
                ];
            }
        );
    }

    /**
     * Get gender distribution
     */
    protected function getGenderDistribution(Institution $institution): array
    {
        $baseQuery = InstitutionPerson::query()
            ->where('institution_id', $institution->id)
            ->active();

        return [
            'male' => (clone $baseQuery)->maleStaff()->count(),
            'female' => (clone $baseQuery)->femaleStaff()->count(),
        ];
    }

    /**
     * Get age distribution in ranges
     */
    protected function getAgeDistribution(Institution $institution): array
    {
        $ranges = [
            ['min' => 18, 'max' => 29, 'label' => '18-29'],
            ['min' => 30, 'max' => 39, 'label' => '30-39'],
            ['min' => 40, 'max' => 49, 'label' => '40-49'],
            ['min' => 50, 'max' => 59, 'label' => '50-59'],
            ['min' => 60, 'max' => 100, 'label' => '60+'],
        ];

        $distribution = [];

        foreach ($ranges as $range) {
            $count = InstitutionPerson::query()
                ->where('institution_id', $institution->id)
                ->active()
                ->filterByAgeRange($range['min'], $range['max'])
                ->count();

            $distribution[] = [
                'range' => $range['label'],
                'count' => $count,
            ];
        }

        return $distribution;
    }

    /**
     * Get status distribution
     */
    protected function getStatusDistribution(Institution $institution): array
    {
        $statuses = [
            ['code' => 'A', 'label' => 'Active'],
            ['code' => 'R', 'label' => 'Retired'],
            ['code' => 'L', 'label' => 'Leave'],
            ['code' => 'S', 'label' => 'Suspended'],
            ['code' => 'E', 'label' => 'Separated'],
        ];

        $distribution = [];

        foreach ($statuses as $status) {
            $count = InstitutionPerson::query()
                ->where('institution_id', $institution->id)
                ->filterByStatus($status['code'])
                ->count();

            if ($count > 0) {
                $distribution[] = [
                    'status' => $status['label'],
                    'code' => $status['code'],
                    'count' => $count,
                ];
            }
        }

        return $distribution;
    }

    /**
     * Get tenure distribution
     */
    protected function getTenureDistribution(Institution $institution): array
    {
        $ranges = [
            ['min' => 0, 'max' => 2, 'label' => '0-2 years'],
            ['min' => 3, 'max' => 5, 'label' => '3-5 years'],
            ['min' => 6, 'max' => 10, 'label' => '6-10 years'],
            ['min' => 11, 'max' => 20, 'label' => '11-20 years'],
            ['min' => 21, 'max' => 100, 'label' => '20+ years'],
        ];

        $distribution = [];

        foreach ($ranges as $range) {
            $minDate = now()->subYears($range['max'])->format('Y-m-d');
            $maxDate = now()->subYears($range['min'])->format('Y-m-d');

            $count = InstitutionPerson::query()
                ->where('institution_id', $institution->id)
                ->active()
                ->whereNotNull('hire_date')
                ->whereBetween('hire_date', [$minDate, $maxDate])
                ->count();

            $distribution[] = [
                'range' => $range['label'],
                'count' => $count,
            ];
        }

        return $distribution;
    }

    /**
     * Get rank distribution (top 10 ranks by staff count)
     */
    protected function getRankDistribution(Institution $institution): array
    {
        return Job::query()
            ->select('jobs.id', 'jobs.name')
            ->selectRaw('COUNT(job_staff.id) as staff_count')
            ->join('job_staff', 'jobs.id', '=', 'job_staff.job_id')
            ->join('institution_person', 'job_staff.staff_id', '=', 'institution_person.id')
            ->where('institution_person.institution_id', $institution->id)
            ->whereNull('job_staff.end_date')
            ->whereExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('status')
                    ->whereColumn('status.staff_id', 'institution_person.id')
                    ->where('status.status', 'A')
                    ->where(function ($q) {
                        $q->whereNull('status.end_date')
                            ->orWhere('status.end_date', '>', now());
                    });
            })
            ->groupBy('jobs.id', 'jobs.name')
            ->orderByDesc('staff_count')
            ->take(10)
            ->get()
            ->map(function ($job) {
                return [
                    'id' => $job->id,
                    'name' => $job->name,
                    'full_name' => $job->name,
                    'count' => $job->staff_count,
                ];
            })
            ->toArray();
    }

    /**
     * Get action items requiring attention
     */
    public function getActionItems(Institution $institution): array
    {
        return Cache::remember(
            "institution.{$institution->id}.dashboard.action_items",
            self::CACHE_TTL,
            function () use ($institution) {
                $baseQuery = InstitutionPerson::query()
                    ->where('institution_id', $institution->id);

                // Staff due for promotion (3+ years in current rank)
                $dueForPromotion = (clone $baseQuery)->active()->toPromote()->count();

                // Staff nearing retirement (age > 57)
                $nearingRetirement = (clone $baseQuery)->active()->toRetire()->count();

                // Staff without current unit assignment
                $withoutUnits = (clone $baseQuery)
                    ->active()
                    ->whereDoesntHave('units', function ($query) {
                        $query->whereNull('staff_unit.end_date');
                    })
                    ->count();

                // Staff without profile picture
                $withoutPictures = (clone $baseQuery)
                    ->active()
                    ->whereHas('person', function ($query) {
                        $query->whereNull('image');
                    })
                    ->count();

                // Staff without current rank
                $withoutRanks = (clone $baseQuery)
                    ->active()
                    ->whereDoesntHave('ranks', function ($query) {
                        $query->whereNull('job_staff.end_date');
                    })
                    ->count();

                // Staff with multiple active unit assignments
                $multipleUnits = (clone $baseQuery)
                    ->active()
                    ->whereHas('units', function ($query) {
                        $query->whereNull('staff_unit.end_date');
                    })
                    ->with(['units' => function ($query) {
                        $query->whereNull('staff_unit.end_date');
                    }])
                    ->get()
                    ->filter(fn($staff) => $staff->units->count() > 1)
                    ->count();

                // Staff without gender
                $withoutGender = (clone $baseQuery)
                    ->active()
                    ->whereHas('person', function ($query) {
                        $query->whereNull('gender')
                            ->orWhere('gender', '');
                    })
                    ->count();

                $activeStaff = (clone $baseQuery)->active()->count();

                return [
                    [
                        'id' => 'due-promotion',
                        'title' => 'Staff Due for Promotion',
                        'description' => 'Staff with 3+ years in current rank',
                        'count' => $dueForPromotion,
                        'severity' => $dueForPromotion > 0 ? 'warning' : 'success',
                        'route' => 'promotion.batch.index',
                        'filter' => 'due-promotion',
                    ],
                    [
                        'id' => 'nearing-retirement',
                        'title' => 'Nearing Retirement',
                        'description' => 'Staff above 57 years old',
                        'count' => $nearingRetirement,
                        'severity' => $nearingRetirement > 10 ? 'warning' : 'success',
                        'route' => null,
                        'filter' => 'nearing-retirement',
                    ],
                    [
                        'id' => 'without-units',
                        'title' => 'Staff Without Units',
                        'description' => 'Active staff with no unit assignment',
                        'count' => $withoutUnits,
                        'severity' => $withoutUnits > 0 ? 'error' : 'success',
                        'route' => 'data-integrity.staff-without-units',
                        'filter' => 'without-units',
                    ],
                    [
                        'id' => 'without-pictures',
                        'title' => 'Staff Without Pictures',
                        'description' => 'Active staff without profile photos',
                        'count' => $withoutPictures,
                        'severity' => $activeStaff > 0 && ($withoutPictures / $activeStaff) > 0.05 ? 'warning' : 'success',
                        'route' => 'data-integrity.staff-without-pictures',
                        'filter' => 'without-pictures',
                    ],
                    [
                        'id' => 'without-ranks',
                        'title' => 'Staff Without Ranks',
                        'description' => 'Active staff with no current rank',
                        'count' => $withoutRanks,
                        'severity' => $withoutRanks > 0 ? 'error' : 'success',
                        'route' => null,
                        'filter' => 'without-ranks',
                    ],
                    [
                        'id' => 'multiple-units',
                        'title' => 'Multiple Unit Assignments',
                        'description' => 'Staff assigned to multiple units',
                        'count' => $multipleUnits,
                        'severity' => $multipleUnits > 0 ? 'warning' : 'success',
                        'route' => 'data-integrity.multiple-unit-assignments',
                        'filter' => 'multiple-units',
                    ],
                    [
                        'id' => 'without-gender',
                        'title' => 'Staff Without Gender',
                        'description' => 'Active staff with missing gender information',
                        'count' => $withoutGender,
                        'severity' => $withoutGender > 0 ? 'warning' : 'success',
                        'route' => 'data-integrity.staff-without-gender',
                        'filter' => 'without-gender',
                    ],
                ];
            }
        );
    }

    /**
     * Get department breakdown with staff counts (including all descendant units)
     */
    public function getDepartmentBreakdown(Institution $institution): array
    {
        return Cache::remember(
            "institution.{$institution->id}.dashboard.departments",
            self::CACHE_TTL,
            function () use ($institution) {
                // Get all active units for this institution
                $allUnits = Unit::where('institution_id', $institution->id)
                    ->whereNull('end_date')
                    ->get(['id', 'name', 'short_name', 'unit_id']);

                // Build parent-to-children map for efficient traversal
                $childrenMap = $allUnits->groupBy('unit_id');

                // Get departments (top-level units with no parent)
                $departments = $allUnits->whereNull('unit_id');

                // Helper to get all descendant unit IDs recursively
                $getDescendantIds = function (int $unitId) use (&$getDescendantIds, $childrenMap): Collection {
                    $descendants = collect([$unitId]);
                    $children = $childrenMap->get($unitId, collect());
                    foreach ($children as $child) {
                        $descendants = $descendants->merge($getDescendantIds($child->id));
                    }

                    return $descendants;
                };

                // Get all staff with their unit assignments and calculate counts
                $staffData = DB::table('staff_unit')
                    ->join('institution_person', 'staff_unit.staff_id', '=', 'institution_person.id')
                    ->join('people', 'institution_person.person_id', '=', 'people.id')
                    ->join('status', 'status.staff_id', '=', 'institution_person.id')
                    ->where('institution_person.institution_id', $institution->id)
                    ->whereNull('staff_unit.end_date')
                    ->where('status.status', 'A')
                    ->where(function ($q) {
                        $q->whereNull('status.end_date')
                            ->orWhere('status.end_date', '>', now());
                    })
                    ->select('staff_unit.unit_id', 'people.gender', 'institution_person.id as staff_id')
                    ->distinct()
                    ->get();

                // Group staff by unit
                $staffByUnit = $staffData->groupBy('unit_id');

                return $departments->map(function ($dept) use ($getDescendantIds, $staffByUnit, $childrenMap) {
                    $descendantIds = $getDescendantIds($dept->id);

                    // Count staff in all descendant units
                    $staffInDept = collect();
                    foreach ($descendantIds as $unitId) {
                        if ($staffByUnit->has($unitId)) {
                            $staffInDept = $staffInDept->merge($staffByUnit->get($unitId));
                        }
                    }

                    // Deduplicate by staff_id (in case staff is in multiple units)
                    $uniqueStaff = $staffInDept->unique('staff_id');

                    // Count divisions (direct children of department)
                    $divisionsCount = $childrenMap->get($dept->id, collect())->count();

                    // Count units (grandchildren - children of divisions)
                    $unitsCount = 0;
                    $divisions = $childrenMap->get($dept->id, collect());
                    foreach ($divisions as $division) {
                        $unitsCount += $childrenMap->get($division->id, collect())->count();
                    }

                    return [
                        'id' => $dept->id,
                        'name' => $dept->name,
                        'short_name' => $dept->short_name,
                        'divisions_count' => $divisionsCount,
                        'units_count' => $unitsCount,
                        'staff_count' => $uniqueStaff->count(),
                        'male_count' => $uniqueStaff->where('gender', 'M')->count(),
                        'female_count' => $uniqueStaff->where('gender', 'F')->count(),
                    ];
                })
                    ->sortBy('name')
                    ->values()
                    ->toArray();
            }
        );
    }

    /**
     * Get filtered staff list for drill-down modals
     */
    public function getFilteredStaff(Institution $institution, string $filter, array $params = []): array
    {
        $query = InstitutionPerson::query()
            ->where('institution_id', $institution->id)
            ->with(['person:id,title,first_name,other_names,surname,gender,image'])
            ->with(['currentRank.job:id,name'])
            ->with(['currentUnit.unit:id,name']);

        switch ($filter) {
            case 'gender':
                $query->active();
                if (isset($params['value'])) {
                    $params['value'] === 'M'
                        ? $query->maleStaff()
                        : $query->femaleStaff();
                }
                break;

            case 'status':
                if (isset($params['code'])) {
                    $query->filterByStatus($params['code']);
                }
                break;

            case 'age':
                $query->active();
                if (isset($params['min']) && isset($params['max'])) {
                    $query->filterByAgeRange($params['min'], $params['max']);
                }
                break;

            case 'due-promotion':
                $query->active()->toPromote();
                break;

            case 'nearing-retirement':
                $query->active()->toRetire();
                break;

            case 'without-units':
                $query->active()
                    ->whereDoesntHave('units', function ($q) {
                        $q->whereNull('staff_unit.end_date');
                    });
                break;

            case 'without-pictures':
                $query->active()
                    ->whereHas('person', function ($q) {
                        $q->whereNull('image');
                    });
                break;

            case 'without-ranks':
                $query->active()
                    ->whereDoesntHave('ranks', function ($q) {
                        $q->whereNull('job_staff.end_date');
                    });
                break;

            case 'department':
                $query->active();
                if (isset($params['id'])) {
                    $query->filterByDepartment($params['id']);
                }
                break;

            case 'rank':
                $query->active();
                if (isset($params['id'])) {
                    $query->whereHas('ranks', function ($q) use ($params) {
                        $q->where('job_staff.job_id', $params['id'])
                            ->whereNull('job_staff.end_date');
                    });
                }
                break;

            case 'active':
            default:
                $query->active();
                break;
        }

        return $query
            ->currentRank()
            ->currentUnit()
            ->take(100)
            ->get()
            ->map(function ($staff) {
                return [
                    'id' => $staff->id,
                    'name' => $staff->person->full_name ?? 'Unknown',
                    'staff_number' => $staff->staff_number,
                    'gender' => $staff->person->gender,
                    'image' => $staff->person->image,
                    'rank' => $staff->currentRank?->job?->name,
                    'unit' => $staff->currentUnit?->unit?->name,
                ];
            })
            ->toArray();
    }

    /**
     * Clear dashboard cache for an institution
     */
    public function clearCache(Institution $institution): void
    {
        $cacheKeys = [
            "institution.{$institution->id}.dashboard.overview",
            "institution.{$institution->id}.dashboard.trends",
            "institution.{$institution->id}.dashboard.analytics",
            "institution.{$institution->id}.dashboard.action_items",
            "institution.{$institution->id}.dashboard.departments",
        ];

        foreach ($cacheKeys as $key) {
            Cache::forget($key);
        }
    }
}
