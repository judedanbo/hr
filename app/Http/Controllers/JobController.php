<?php

namespace App\Http\Controllers;

use App\Enums\GenderEnum;
use App\Exports\GradeSummaryExport;
use App\Http\Requests\StoreJobRequest;
use App\Models\InstitutionPerson;
use App\Models\Job;
use App\Models\Unit;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Maatwebsite\Excel\Excel;

class JobController extends Controller
{
    public function index()
    {
        if (Gate::denies('view all jobs')) {
            activity()
                ->causedBy(auth()->user())
                ->event('view')
                ->withProperties([
                    'result' => 'failed',
                    'user_ip' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                ])
                ->log('View All Jobs');
            return redirect()->route('dashboard')->with('error', 'You do not have permission to view all ranks.');
        }
        return Inertia::render('Job/Index', [
            'jobs' => Job::query()
                ->searchRank(request()->search)
                ->with(['category', 'institution'])
                ->withCount([
                    'staff' => function ($query) {
                        $query->active();
                        $query->whereNull('job_staff.end_date');
                    },
                    'staff as male_staff_count' => function ($query) {
                        $query->active();
                        $query->where('job_staff.end_date', null);
                        $query->maleStaff();
                    },
                    'staff as female_staff_count' => function ($query) {
                        $query->active();
                        $query->where('job_staff.end_date', null);
                        $query->femaleStaff();
                    },
                    'units'
                ])
                ->whereHas('staff', function ($query) {
                    $query->active();
                    $query->where('job_staff.end_date', null);
                })
                ->orderByRaw('job_category_id is null asc, job_category_id asc')
                ->paginate(10)
                ->withQueryString()
                ->through(fn($job) => [
                    'id' => $job->id,
                    'name' => $job->name,
                    'staff' => $job->staff_count,
                    'male_staff_count' => $job->male_staff_count,
                    'female_staff_count' => $job->female_staff_count,
                    'category' => $job->category ? [
                        'id' => $job->category->id,
                        'name' => $job->category->name,
                        'level' => $job->category->level,
                        'short_name' => $job->category->short_name,
                    ] : '',
                    'institution' => [
                        'id' => $job->institution->id,
                        'name' => $job->institution->name,
                    ],
                ]),
            'filters' => ['search' => request()->search],
        ]);
    }

    public function create()
    {

        return Job::select(['id as value', 'name as label'])
            ->get();
    }

    public function show($job)
    {
        if (Gate::denies('view job')) {
            activity()
                ->causedBy(auth()->user())
                ->event('view')
                ->withProperties([
                    'result' => 'failed',
                    'user_ip' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                ])
                ->log('View Job');
            return redirect()->route('dashboard')->with('error', 'You do not have permission to view this job.');
        }
        $job = Job::query()
            ->with([
                'staff' => function ($query) use ($job) {
                    $query->active();
                    // $query->where('job_staff.end_date', null);
                    $query->whereHas('ranks', function ($query) use ($job) {
                        $query->whereNull('job_staff.end_date');
                        $query->where('job_staff.job_id', $job);
                    });
                    $query->with(['ranks' => function ($query) {
                        $query->where('job_staff.end_date', null);
                        $query->orderBy('job_staff.start_date', 'desc');
                        // $query->where('job_id', $job);
                        // $query->with('job:id,name');
                    }]);
                    $query->when(request()->search, function ($query, $search) {
                        $query->whereHas('person', function ($query) use ($search) {
                            $query->where('first_name', 'like', "%{$search}%");
                            $query->orWhere('surname', 'like', "%{$search}%");
                        });
                    });
                },
                'staff.person',
                'institution',
                // 'units' => function ($query) {
                // $query->whereHas('staff', function ($query) {
                //     $query->active();
                //     $query->whereHas('ranks', function ($query) {
                //         $query->whereNull('job_staff.end_date');
                //     });
                // });
                // },
            ])
            ->withCount([
                'staff' => function ($query) {
                    $query->active();
                    $query->where('job_staff.end_date', null);
                },
                'staff as male_staff_count' => function ($query) {
                    $query->active();
                    $query->where('job_staff.end_date', null);
                    $query->maleStaff();
                },
                'staff as female_staff_count' => function ($query) {
                    $query->active();
                    $query->where('job_staff.end_date', null);
                    $query->femaleStaff();
                },
            ])
            ->findOrFail($job);

        // return ($job);
        return Inertia::render('Job/Show', [
            'job' => [
                'id' => $job->id,
                'name' => $job->name,
                'category' => $job->category ? [
                    'name' => $job->category->name,
                    'id' => $job->category->id,
                ] : '',
                'staff_count' => $job->staff_count,
                'male_staff_count' => $job->male_staff_count,
                'female_staff_count' => $job->female_staff_count,
                'institution' => $job->institution ? [
                    'name' => $job->institution->name,
                    'id' => $job->institution->id,
                ] : null,
                // 'subs' => $job->subs->map(fn($unit) => [
                //     'id' => $unit->id,
                //     'name' => $unit->name,
                //     'staff_count' => $unit->staff_count,
                // ]),
                'units' => $job->units,
                'staff' => $job->staff->map(fn($staff) => [
                    'id' => $staff->id,
                    'name' => $staff->person->full_name,
                    'initials' => $staff->person->initials,
                    'image' => $staff->person->image ? '/storage/' . $staff->person->image : null,
                    'staff_number' => $staff->staff_number,
                    'file_number' => $staff->file_number,
                    // 'unit' => $staff->units?->first()?->name,
                    // 'unit_id' => $staff->units?->first()?->id,
                    'rank' => $staff->ranks?->first()?->name,
                    'rank_start' => $staff->ranks?->first()?->pivot->start_date,
                    'rank_start_text' => $staff->ranks?->first()?->pivot->start_date->format('d F Y'),
                    'rank_remark' => $staff->ranks?->first()?->pivot->remarks,
                ]),
            ],
            'filters' => ['search' => request()->search],
        ]);
    }

    public function store(StoreJobRequest $request)
    {
        if (Gate::denies('create job')) {
            activity()
                ->causedBy(auth()->user())
                ->event('create')
                ->withProperties([
                    'result' => 'failed',
                    'user_ip' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                ])
                ->log('Created Job');
            return redirect()->back()->with('error', 'You do not have permission to create a job.');
        }
        Job::create($request->validated());

        return redirect()->route('job.index')->with('success', 'Job created.');
    }

    public function stats(Job $job)
    {
        $job->loadCount([
            'staff as total_staff_count',
            'staff as active_staff_count' => function ($query) use ($job) {
                $query->active();
                $query->where('job_staff.job_id', $job->id);
                $query->whereNull('job_staff.end_date');
            },
            'staff as current_staff_count' => function ($query) use ($job) {
                $query->active();
                $query->where('job_staff.job_id', $job->id);
                $query->whereNull('job_staff.end_date');
            },
            'staff as due_for_promotion' => function ($query) {
                $query->active();
                $query->whereYear('job_staff.start_date', '<=', now()->subYears(3)->year);
                $query->where(function ($query) {
                    $query->whereNull('job_staff.end_date');
                    $query->orWhere('job_staff.end_date', '>', now());
                });
            },
            'staff as male_staff_count' => function ($query) {
                $query->active();
                $query->where('job_staff.end_date', null);
                $query->whereHas('person', function ($query) {
                    $query->where('gender', GenderEnum::MALE);
                });
            },
            'staff as female_staff_count' => function ($query) {
                $query->active();
                $query->where('job_staff.end_date', null);
                $query->whereHas('person', function ($query) {
                    $query->where('gender', GenderEnum::FEMALE);
                });
            },
        ]);

        // $jobs->withCount(['staff' => function ($query) {
        //     $query->active();
        //     $query->where('job_staff.end_date', null);
        // }]);
        // $stats = new \stdClass();
        // $stats->label = "Gender Statistics";
        // $stats->borderWidth = 0;
        // $stats->backgroundColor = ['#2563eb', '#fb7185'];
        // $stats->data = [
        //     $job->male_staff_count,
        //     $job->female_staff_count,
        // ];

        return [
            'id' => $job->id,
            'name' => $job->name,
            'total_staff_count' => $job->total_staff_count,
            'current_staff_count' => $job->current_staff_count,
            'due_for_promotion' => $job->due_for_promotion,
            // 'gender_stats' => [$stats],
            'male_count' => $job->male_staff_count,
            'female_count' => $job->female_staff_count,
        ];
    }

    public function unitStats($job)
    {
        return InstitutionPerson::query()
            ->selectRaw(
                "units.name,
                SUM(IF(people.gender = 'M', 1, 0)) AS male_staff_count,
                SUM(IF(people.gender = 'F', 1, 0)) AS female_staff_count,
                MAX(job_staff.id) AS last_promotion_date,
                count(*) as total_staff"
            )
            ->join('staff_unit', 'staff_unit.staff_id', 'institution_person.id')
            ->join('units', 'units.id', 'staff_unit.unit_id')
            ->join('job_staff', 'job_staff.staff_id', 'institution_person.id')
            ->join('jobs', 'jobs.id', 'job_staff.job_id')
            ->join('people', 'people.id', 'institution_person.person_id')
            ->where(function ($query) {
                $query->whereNull('staff_unit.end_date');
                $query->orWhere('staff_unit.end_date', '>=', now());
            })
            ->whereNull('units.deleted_at')
            ->whereNull('jobs.deleted_at')
            ->where('jobs.id', $job)
            ->groupByRaw('units.name, units.id')
            ->active()
            ->get();

        return Unit::query()
            ->whereHas('staff', function ($query) use ($job) {
                $query->active();
                $query->whereHas('ranks', function ($query) use ($job) {
                    $query->where('job_staff.job_id', $job);
                    $query->whereNull('job_staff.end_date');
                    $query->orWhere('job_staff.end_date', '>=', now());
                });
                // $query->where('job_staff.job_id', $job);
                // $query->whereNull('job_staff.end_date');
            })
            ->withCount(
                [
                    'staff' => function ($query) {
                        $query->active();
                        $query->whereHas('ranks', function ($query) {
                            // $query->where('job_staff.job_id', $job);
                            $query->whereNull('job_staff.end_date');
                            $query->orWhere('job_staff.end_date', '>=', now());
                        });
                        // $query->where('job_staff.job_id', $job);
                        // $query->whereNull('job_staff.end_date');
                    },
                    'subs' => function ($query) {
                        $query->whereHas('staff', function ($query) {
                            $query->active();
                            $query->search(request()->search);
                        });
                    },
                ]
            )
            ->with([
                'staff' => function ($query) {
                    $query->with(['person', 'ranks', 'units']);
                    $query->active();
                    $query->whereHas('ranks', function ($query) {
                        $query->whereNull('job_staff.end_date');
                        $query->orWhere('job_staff.end_date', '>=', now());
                    });
                },
                'subs' => function ($query) {
                    $query->whereHas('staff', function ($query) {
                        $query->active();
                        $query->search(request()->search);
                    });
                    $query->with(['staff' => function ($query) {
                        $query->with(['person', 'ranks', 'units']);
                        $query->active();
                        $query->search(request()->search);
                    }]);
                    $query->withCount([
                        'staff' => function ($query) {
                            $query->active();
                            $query->search(request()->search);
                        },
                        'subs' => function ($query) {
                            $query->whereHas('staff', function ($query) {
                                $query->active();
                            });
                        },
                    ]);
                },
            ])
            ->get();
        $job = Job::query()
            ->withCount([
                'staff',
                'staff as active_staff_count' => function ($query) {
                    $query->active();
                    $query->where('job_staff.end_date', null);
                    $query->whereHas('units');
                    $query->with('units');
                },
            ])
            ->with(['staff' => function ($query) {
                $query->active();
                $query->where('job_staff.end_date', null);
                $query->whereHas('units');
                $query->with(['units' => function ($query) {
                    $query->whereNull('staff_unit.end_date');
                    // $query->select(['id', 'name']);
                }]);
            }])
            ->find($job);

        return $job->loadCount([
            'staff as total_staff_count',
            'staff as total_staff' => function ($query) {
                $query->active();
                $query->where('job_staff.end_date', null);
                $query->groupBy(Unit::select('unit_id')
                    ->whereColumn('unit_id', 'units.id'));
            },
            'staff as active_staff_count' => function ($query) use ($job) {
                $query->active();
                $query->where('job_staff.job_id', $job->id);
                $query->whereNull('job_staff.end_date');
                $query->whereHas('units');
            },

        ]);
        // ->load(['staff' => function ($query) {
        //     $query->active();
        //     $query->where('job_staff.end_date', null);
        //     $query->whereHas('units');
        // }]);
        // return
    }

    public function units(Job $job)
    {
        return $job->loadCount('staff');
    }

    public function update(StoreJobRequest $request, Job $job)
    {
        if (Gate::denies('edit job')) {
            activity()
                ->causedBy(auth()->user())
                ->performedOn($job)
                ->event('update')
                ->withProperties([
                    'result' => 'failed',
                    'user_ip' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                ])
                ->log('Updated Rank');

            return redirect()->back()->with('error', 'You do not have permission to edit this rank.');
        }

        return redirect()->route('job.index')->with('success', 'Rank updated.');
    }

    public function delete(Job $job)
    {
        if (Gate::denies('delete job')) {
            activity()
                ->causedBy(auth()->user())
                ->performedOn($job)
                ->event('delete')
                ->withProperties([
                    'result' => 'failed',
                    'user_ip' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                ])
                ->log('Deleted Rank');
            return redirect()->back()->with('error', 'You do not have permission to delete this rank.');
        }
        $job->delete();

        return redirect()->route('job.index')->with('success', 'Rank updated.');
    }

    public function summary(Excel $excel)
    {
        if (Gate::denies('download job summary')) {
            activity()
                ->causedBy(auth()->user())
                ->event('download')
                ->withProperties([
                    'result' => 'failed',
                    'user_ip' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                ])
                ->log('Rank Summary');
            return redirect()->back()->with('error', 'You do not have permission to download this data.');
        }
        activity()
            ->causedBy(auth()->user())
            ->event('download')
            ->withProperties([
                'result' => 'success',
                'user_ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ])
            ->log('Rank Summary');
        return $excel->download(new GradeSummaryExport, 'grades.xlsx');
    }
}
