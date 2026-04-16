<?php

namespace App\Http\Controllers;

use App\Exports\UnitStaffExport;
use App\Http\Requests\StaffDirectoryFilterRequest;
use App\Http\Requests\StoreUnitRequest;
use App\Http\Requests\UpdateUnitRequest;
use App\Models\InstitutionPerson;
use App\Models\Job;
use App\Models\JobCategory;
use App\Models\Unit;
use App\Services\UnitHierarchy;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;

class UnitController extends Controller
{
    public function index($institution = null)
    {
        if (request()->user()->cannot('viewAny', Unit::class)) {
            return redirect()->route('dashboard')->with('error', 'You do not have permission to view units');
        }

        return Inertia::render('Unit/Index', [
            'units' => Unit::query()
                ->departments()
                ->hasSubs()
                ->with([
                    'institution:id,name,abbreviation',
                    'currentOffice.district.region',
                    // 'staff' => function ($query) {
                    //     $query->active();
                    //     $query->select('institution_person.id', 'person_id', 'file_number', 'staff_number', 'hire_date');
                    // },
                    'staff' => function ($query) {
                        $query->active();
                        // $query->wherePivot('end_date', null);
                    },
                    'subs' => function ($query) {
                        $query->where(function ($query) {
                            $query->whereHas('staff', function ($query) {
                                $query->active();
                            });
                            $query->orWhereHas('subs', function ($query) {
                                $query->whereHas('staff', function ($query) {
                                    $query->active();
                                });
                            });
                        });
                        $query->with([
                            'subs' => function ($query) {
                                $query->where(function ($query) {
                                    $query->whereHas('staff', function ($query) {
                                        $query->active();
                                    });
                                    $query->orWhereHas('subs', function ($query) {
                                        $query->whereHas('staff', function ($query) {
                                            $query->active();
                                        });
                                    });
                                });
                                $query->withCount([
                                    'staff' => function ($query) {
                                        $query->active();
                                    },
                                    'staff as male_staff' => function ($query) {
                                        $query->active();
                                        $query->maleStaff();
                                    },
                                    'staff as female_staff' => function ($query) {
                                        $query->active();
                                        $query->femaleStaff();
                                    },
                                ]);
                            },
                            'staff' => function ($query) {
                                $query->with(['person', 'ranks', 'units']);
                                $query->active();
                            },
                        ]);
                        $query->withCount([
                            'staff' => function ($query) {
                                $query->active();
                                // $query->wherePivot('end_date', null);
                            },
                            'staff as male_staff' => function ($query) {
                                $query->active();
                                $query->maleStaff();
                            },
                            'staff as female_staff' => function ($query) {
                                $query->active();
                                $query->femaleStaff();
                            },
                            'subs' => function ($query) {
                                $query->whereHas('staff', function ($query) {
                                    $query->active();
                                });
                            },
                        ]);
                    },
                ])
                ->withCount(
                    [
                        'staff' => function ($query) {
                            $query->active();
                        },
                        'staff as male_staff' => function ($query) {
                            $query->active();
                            $query->maleStaff();
                        },
                        'staff as female_staff' => function ($query) {
                            $query->active();
                            $query->femaleStaff();
                        },
                        'subs' => function ($query) {
                            $query->where(function ($query) {
                                $query->whereHas('staff', function ($query) {
                                    $query->active();
                                });
                                $query->orWhereHas('subs', function ($query) {
                                    $query->whereHas('staff', function ($query) {
                                        $query->active();
                                    });
                                });
                            });
                        },
                    ]
                )
                ->when(request()->institution, function ($query, $search) {
                    $query->where('institution_id', request()->institution);
                })
                ->searchUnit(request()->search)
                ->paginate(10)
                ->withQueryString()
                ->through(
                    fn ($unit) => [
                        'id' => $unit->id,
                        'name' => $unit->name,
                        'short_name' => $unit->short_name,
                        'type' => $unit->type->label(),
                        'office' => $unit->currentOffice->first() ? [
                            'id' => $unit->currentOffice->first()->id,
                            'name' => $unit->currentOffice->first()->name,
                            'district' => $unit->currentOffice->first()->district ? [
                                'id' => $unit->currentOffice->first()->district->id,
                                'name' => $unit->currentOffice->first()->district->name,
                                'region' => $unit->currentOffice->first()->district->region ? [
                                    'id' => $unit->currentOffice->first()->district->region->id,
                                    'name' => $unit->currentOffice->first()->district->region->name,
                                ] : null,
                            ] : null,
                        ] : null,
                        // 'count' =>  $unit->subs->sum(function ($sub) {
                        //     // return $sub->subs->sum('staff_count');
                        // }),
                        'staff' => $unit->staff_count + $unit->subs->sum(function ($sub) {
                            return $sub->staff_count + $sub->subs->sum('staff_count') ?? 0;
                        }), // + $unit->subs->sum(function ($sum) {
                        //     return $sum->staff_count + $sum->subs->sum(function ($sum) {
                        //         return $sum->staff_count + $sum->subs->sum('staff_count');
                        //     });
                        // }),
                        'male' => $unit,
                        'staff_list' => $unit->staff,
                        'male_staff' => $unit->male_staff + $unit->subs->sum(function ($sub) {
                            return $sub->male_staff + $sub->subs->sum('male_staff') ?? 0;
                        }),
                        // 'female_staff' => $unit->female_staff,
                        'female_staff' => $unit->female_staff + $unit->subs->sum(function ($sub) {
                            return $sub->female_staff + $sub->subs->sum('female_staff') ?? 0;
                        }),
                        'units' => $unit->subs_count,
                        'institution' => $unit->institution ? [
                            'id' => $unit->institution->id,
                            'name' => $unit->institution->name,
                        ] : null,
                    ]
                ),
            'filters' => ['search' => request()->search],
        ]);
    }

    public function show(StaffDirectoryFilterRequest $request, $unit, UnitHierarchy $hierarchy)
    {
        $unit = Unit::query()
            ->with([
                'institution',
                'parent',
                'currentOffice.district.region',
                'subs' => function ($query) {
                    $query->whereNull('end_date');
                },
            ])
            ->whereId($unit)
            ->firstOrFail();

        if ($request->user()->cannot('view', $unit)) {
            return redirect()->route('dashboard')->with('error', 'You do not have permission to view this unit');
        }

        $allIds = $hierarchy->descendantIds($unit);
        $childIdMap = $hierarchy->descendantIdsGroupedByChild($unit);

        $stats = $this->buildRootStats($unit, $allIds);
        $subs = $this->buildSubUnitCards($unit, $childIdMap);
        $rankDistribution = $this->buildRankDistribution($allIds);

        return Inertia::render('Unit/Show', [
            'unit' => [
                'id' => $unit->id,
                'name' => $unit->name,
                'type' => $unit->type->label(),
                'institution' => $unit->institution ? [
                    'id' => $unit->institution->id,
                    'name' => $unit->institution->name,
                ] : null,
                'parent' => $unit->parent ? [
                    'id' => $unit->parent->id,
                    'name' => $unit->parent->name,
                ] : null,
                'current_office' => $unit->currentOffice->first() ? [
                    'id' => $unit->currentOffice->first()->id,
                    'name' => $unit->currentOffice->first()->name,
                    'type' => $unit->currentOffice->first()->type?->label(),
                    'district' => $unit->currentOffice->first()->district ? [
                        'id' => $unit->currentOffice->first()->district->id,
                        'name' => $unit->currentOffice->first()->district->name,
                        'region' => $unit->currentOffice->first()->district->region ? [
                            'id' => $unit->currentOffice->first()->district->region->id,
                            'name' => $unit->currentOffice->first()->district->region->name,
                        ] : null,
                    ] : null,
                ] : null,
            ],
            'stats' => $stats,
            'subs' => $subs,
            'rank_distribution' => $rankDistribution,
            'staff' => $this->loadStaffPage($allIds, $request->validated()),
            'filter_options' => $this->buildFilterOptions($unit, $allIds),
            'filters' => $request->validated(),
        ]);
    }

    /**
     * @param  int[]  $allIds
     * @return array<string, int>
     */
    private function buildRootStats(Unit $unit, array $allIds): array
    {
        $totals = InstitutionPerson::query()
            ->active()
            ->whereHas('units', fn ($q) => $q->whereIn('units.id', $allIds)->whereNull('staff_unit.end_date'))
            ->leftJoin('people', 'people.id', '=', 'institution_person.person_id')
            ->selectRaw('count(*) as total')
            ->selectRaw("sum(case when people.gender = 'M' then 1 else 0 end) as male")
            ->selectRaw("sum(case when people.gender = 'F' then 1 else 0 end) as female")
            ->first();

        $directSubs = $unit->subs->count();
        $totalDescendants = max(0, count($allIds) - 1);

        return [
            'total' => (int) ($totals->total ?? 0),
            'male' => (int) ($totals->male ?? 0),
            'female' => (int) ($totals->female ?? 0),
            'direct_subs' => $directSubs,
            'total_descendants' => $totalDescendants,
        ];
    }

    /**
     * @param  array<int, int[]>  $childIdMap
     * @return array<int, array<string, mixed>>
     */
    private function buildSubUnitCards(Unit $unit, array $childIdMap): array
    {
        return $unit->subs->map(function (Unit $sub) use ($childIdMap) {
            $subtreeIds = $childIdMap[$sub->id] ?? [$sub->id];

            $totals = InstitutionPerson::query()
                ->active()
                ->whereHas('units', fn ($q) => $q->whereIn('units.id', $subtreeIds)->whereNull('staff_unit.end_date'))
                ->leftJoin('people', 'people.id', '=', 'institution_person.person_id')
                ->selectRaw('count(*) as total')
                ->selectRaw("sum(case when people.gender = 'M' then 1 else 0 end) as male")
                ->selectRaw("sum(case when people.gender = 'F' then 1 else 0 end) as female")
                ->first();

            return [
                'id' => $sub->id,
                'name' => $sub->name,
                'type' => $sub->type->label(),
                'subs' => max(0, count($subtreeIds) - 1),
                'staff_count' => (int) ($totals->total ?? 0),
                'male_staff' => (int) ($totals->male ?? 0),
                'female_staff' => (int) ($totals->female ?? 0),
            ];
        })->values()->all();
    }

    /**
     * @param  int[]  $allIds
     * @return array<int, array<string, mixed>>
     */
    private function buildRankDistribution(array $allIds): array
    {
        return Job::query()
            ->select('jobs.id', 'jobs.name')
            ->selectRaw('COUNT(DISTINCT job_staff.id) as staff_count')
            ->join('job_staff', 'jobs.id', '=', 'job_staff.job_id')
            ->join('job_categories', 'jobs.job_category_id', '=', 'job_categories.id')
            ->join('staff_unit', 'staff_unit.staff_id', '=', 'job_staff.staff_id')
            ->whereIn('staff_unit.unit_id', $allIds)
            ->whereNull('job_staff.end_date')
            ->whereNull('staff_unit.end_date')
            ->groupBy('jobs.id', 'jobs.name', 'job_categories.level')
            ->orderBy('job_categories.level')
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

    public function staff(StaffDirectoryFilterRequest $request, Unit $unit, UnitHierarchy $hierarchy)
    {
        if ($request->user()->cannot('view', $unit)) {
            abort(403);
        }

        $unitIds = $hierarchy->descendantIds($unit);

        return Inertia::render('Unit/Show', [
            'staff' => $this->loadStaffPage($unitIds, $request->validated()),
            'filter_options' => $this->buildFilterOptions($unit, $unitIds),
            'filters' => $request->validated(),
        ]);
    }

    /**
     * Build a paginated, filtered listing of active staff across the given unit ids.
     *
     * @param  int[]  $unitIds
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    private function loadStaffPage(array $unitIds, array $filters): array
    {
        $query = InstitutionPerson::query()
            ->active()
            ->with(['person', 'ranks.category', 'units'])
            ->whereHas('units', function ($q) use ($unitIds) {
                $q->whereIn('units.id', $unitIds);
                $q->whereNull('staff_unit.end_date');
            });

        if (! empty($filters['search'])) {
            $query->search($filters['search']);
        }
        if (! empty($filters['job_category_id'])) {
            $query->whereHas('ranks', fn ($q) => $q->where('job_category_id', $filters['job_category_id']));
        }
        if (! empty($filters['rank_id'])) {
            $query->whereHas('ranks', fn ($q) => $q->where('jobs.id', $filters['rank_id']));
        }
        if (! empty($filters['sub_unit_id'])) {
            $query->whereHas('units', fn ($q) => $q->where('units.id', $filters['sub_unit_id']));
        }
        if (! empty($filters['gender'])) {
            $filters['gender'] === 'M' ? $query->maleStaff() : $query->femaleStaff();
        }
        if (! empty($filters['hire_date_from'])) {
            $query->whereDate('hire_date', '>=', $filters['hire_date_from']);
        }
        if (! empty($filters['hire_date_to'])) {
            $query->whereDate('hire_date', '<=', $filters['hire_date_to']);
        }
        if (! empty($filters['age_from'])) {
            $cutoff = now()->subYears((int) $filters['age_from'])->endOfDay();
            $query->whereHas('person', fn ($q) => $q->where('date_of_birth', '<=', $cutoff));
        }
        if (! empty($filters['age_to'])) {
            $cutoff = now()->subYears((int) $filters['age_to'] + 1)->startOfDay();
            $query->whereHas('person', fn ($q) => $q->where('date_of_birth', '>=', $cutoff));
        }

        $paginator = $query
            ->orderByRaw('(select coalesce(min(jc.level), 99) from jobs inner join job_categories jc on jc.id = jobs.job_category_id inner join job_staff on job_staff.job_id = jobs.id where job_staff.staff_id = institution_person.id and job_staff.end_date is null)')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (InstitutionPerson $staff) => $this->shapeStaffRow($staff));

        return JsonResource::collection($paginator)->response()->getData(true);
    }

    /**
     * Shape one staff row for the directory.
     *
     * @return array<string, mixed>
     */
    private function shapeStaffRow(InstitutionPerson $staff): array
    {
        $rank = $staff->ranks->first();
        $unit = $staff->units->first();

        return [
            'id' => $staff->person->id,
            'name' => $staff->person->full_name,
            'gender' => $staff->person->gender?->value,
            'dob' => $staff->person->date_of_birth?->format('d M Y'),
            'dob_raw' => $staff->person->date_of_birth?->format('Y-m-d'),
            'initials' => $staff->person->initials,
            'hire_date' => $staff->hire_date?->format('d M Y'),
            'hire_date_raw' => $staff->hire_date?->format('Y-m-d'),
            'staff_number' => $staff->staff_number,
            'file_number' => $staff->file_number,
            'image' => $staff->person->image ? '/storage/' . $staff->person->image : null,
            'rank' => $rank ? [
                'id' => $rank->id,
                'name' => $rank->name,
                'start_date' => $rank->pivot->start_date?->format('d M Y'),
                'remarks' => $rank->pivot->remarks,
                'cat' => $rank->category,
                'category_id' => $rank->job_category_id,
            ] : null,
            'unit' => $unit ? [
                'id' => $unit->id,
                'name' => $unit->name,
                'start_date' => $unit->pivot->start_date?->format('d M Y'),
                'duration' => $unit->pivot->start_date?->diffForHumans(),
            ] : null,
        ];
    }

    /**
     * Build dropdown option lists derived from all staff in the descendant set.
     *
     * @param  int[]  $unitIds
     * @return array<string, mixed>
     */
    private function buildFilterOptions(Unit $unit, array $unitIds): array
    {
        $categories = JobCategory::query()
            ->whereHas('jobs.activeStaff', fn ($q) => $q->whereHas('units', fn ($u) => $u->whereIn('units.id', $unitIds)))
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn ($c) => ['value' => $c->id, 'label' => $c->name])
            ->values()
            ->all();

        $ranks = Job::query()
            ->whereHas('activeStaff', fn ($q) => $q->whereHas('units', fn ($u) => $u->whereIn('units.id', $unitIds)))
            ->orderBy('name')
            ->get(['id', 'name', 'job_category_id'])
            ->map(fn ($r) => ['value' => $r->id, 'label' => $r->name, 'category_id' => $r->job_category_id])
            ->values()
            ->all();

        $subUnits = Unit::query()
            ->where('unit_id', $unit->id)
            ->whereNull('end_date')
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn ($u) => ['value' => $u->id, 'label' => $u->name])
            ->values()
            ->all();

        return [
            'job_categories' => $categories,
            'ranks' => $ranks,
            'sub_units' => $subUnits,
            'genders' => [
                ['value' => 'M', 'label' => 'Male'],
                ['value' => 'F', 'label' => 'Female'],
            ],
        ];
    }

    public function store(StoreUnitRequest $request)
    {
        if (request()->user()->cannot('create', Unit::class)) {
            return redirect()->route('dashboard')->with('error', 'You do not have permission to create a unit');
        }
        $unit = Unit::create($request->validated());

        return redirect()->route('unit.show', $unit->id)->with('success', 'Unit created successfully');
    }

    public function update(UpdateUnitRequest $request, Unit $unit)
    {
        if (request()->user()->cannot('edit', Unit::class)) {
            return redirect()->route('dashboard')->with('error', 'You do not have permission to update this unit');
        }
        $unit->update($request->validated());

        return redirect()->back()->with('success', 'Unit updated successfully');
    }

    public function delete(Unit $unit)
    {
        if (request()->user()->cannot('delete', $unit)) {
            return redirect()->route('dashboard')->with('error', 'You do not have permission to delete this unit');
        }

        // Check for active staff assignments in this unit
        $activeStaffCount = $unit->staff()->count();
        if ($activeStaffCount > 0) {
            return redirect()->back()->with('error', "Cannot delete unit. There are {$activeStaffCount} staff member(s) currently assigned to this unit. Please reassign or remove them first.");
        }

        // Check for active staff in sub-units
        $subUnitsWithStaff = $unit->subs()->whereHas('staff')->count();
        if ($subUnitsWithStaff > 0) {
            return redirect()->back()->with('error', "Cannot delete unit. There are {$subUnitsWithStaff} sub-unit(s) with active staff assignments. Please reassign or remove staff from sub-units first.");
        }

        $unit->delete();

        return redirect()->route('unit.index')->with('success', 'Unit deleted successfully');
    }

    public function details(Unit $unit)
    {
        return [
            'id' => $unit->id,
            'name' => $unit->name,
            'short_name' => $unit->short_name,
            'type' => $unit->type,
            'institution_id' => $unit->institution_id,
            'unit_id' => $unit->unit_id,
            'start_date' => $unit->start_date?->format('Y-m-d'),
            'end_date' => $unit->end_date?->format('Y-m-d'),

        ];
        // return $unit->only(['id', 'name', 'short_name', 'type', 'institution_id', 'unit_id', 'start_date', 'end_date']);
    }

    public function addSub(Request $request, Unit $unit)
    {
        if (request()->user()->cannot('create', Unit::class)) {
            return redirect()->route('dashboard')->with('error', 'You do not have permission to add a sub unit');
        }
        $newSub = $request->only(
            ['name', 'short_name', 'type', 'unit_id', 'start_date', 'end_date']
        );
        $newSub['institution_id'] = $unit->institution_id;

        $unit->subs()->create($newSub);

        // $unit->subs()->attach($request->sub_id, ['start_date' => $request->start_date]);
        return redirect()->back()->with('success', 'Sub unit added successfully');
    }

    // public function list(){
    //     return 'unit list';
    //     // return Unit::department()->get()->map(fn ($unit) => [
    //     //     'value' => $unit->id,
    //     //     'label' => $unit->name,
    //     // ]);
    // }

    public function download(Unit $unit, Request $request)
    {
        if ($request->user()->cannot('download active staff data', Unit::class)) {
            return redirect()->back()->with('error', 'You do not have permission to download this unit\'s staff');
        }

        $filters = $request->only([
            'search',
            'job_category_id',
            'rank_id',
            'sub_unit_id',
            'gender',
            'hire_date_from',
            'hire_date_to',
            'age_from',
            'age_to',
        ]);

        return Excel::download(
            new UnitStaffExport($unit, $filters),
            Str::of($unit->name)
                ->title()
                ->replaceMatches('/[^A-Za-z0-9]++/', '-')
                ->__toString()
                // str_replace(array("/", "\\"), '-', $unit->name)
                . ' staff.xlsx'
        );
    }
}
