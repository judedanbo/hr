<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreJobRequest;
use App\Models\Job;
use Inertia\Inertia;
use Illuminate\Support\Facades\Storage;

class JobController extends Controller
{
    public function index()
    {
        return Inertia::render('Job/Index', [
            'jobs' =>
            Job::query()
                ->searchRank(request()->search)
                ->with(['category', 'institution'])
                ->withCount(['staff' => function ($query) {
                    $query->active();
                    $query->where('job_staff.end_date', null);
                }])
                ->orderByRaw('job_category_id is null asc, job_category_id asc')
                ->paginate(10)
                ->withQueryString()
                ->through(fn ($job) => [
                    'id' => $job->id,
                    'name' => $job->name,
                    'staff' => $job->staff_count,
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

    function create()
    {
        return Job::select(['id as value', 'name as label'])
            ->get();
    }


    public function show($job)
    {
        $job = Job::query()
            ->with([
                'staff' => function ($query) use ($job) {
                    $query->active();
                    // $query->where('job_staff.end_date', null);
                    $query->whereHas('ranks', function ($query) use ($job){
                        $query->whereNull('job_staff.end_date');
                        $query->where('job_staff.job_id', $job);
                    });
                    $query->with(['ranks'=> function ($query) {
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
                'institution'])
            ->withCount([
                'staff' => function ($query) {
                    $query->whereHas('statuses', function ($query) {
                        $query->where('status', 'A');
                    });
                    $query->where('job_staff.end_date', null);
            }])
            ->find($job);
        // return($job);
        return Inertia::render('Job/Show', [
            'job' => [
                'id' => $job->id,
                'name' => $job->name,
                'staff_count' => $job->staff_count,
                'institution' => $job->institution ? [
                    'name' => $job->institution->name,
                    'id' => $job->institution->id,
                ] : null,
                'staff' => $job->staff->map(fn ($staff) => [
                    'id' => $staff->id,
                    'name' => $staff->person->full_name,
                    'initials' => $staff->person->initials,
                    'image' => $staff->person->image ? Storage::disk('avatars')->url($staff->person->image) : null,
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
        Job::create($request->validated());
        return redirect()->route('job.index')->with('success', 'Job created.');
    }

    public function stats(Job $job){
        $job->loadCount([
            'staff as total_staff_count',
            'staff as active_staff_count' => function ($query) {
                $query->active();
            },
            'staff as current_staff_count' => function ($query) {
                $query->active();
                $query->where('job_staff.end_date', null);
            },
            'staff as due_for_promotion' => function ($query) {
                $query->active();
                $query->whereYear('job_staff.start_date', '<=', now()->subYears(3)->year);
                $query->where('job_staff.end_date', null);
            },
            'staff as male_staff_count' => function ($query) {
                $query->active();
                $query->where('job_staff.end_date', null);
                $query->whereHas('person', function ($query) {
                    $query->where('gender', 'M');
                });
            },
            'staff as female_staff_count' => function ($query) {
                $query->active();
                $query->where('job_staff.end_date', null);
                $query->whereHas('person', function ($query) {
                    $query->where('gender', 'F');
                });
            }
        ]);

        // $jobs->withCount(['staff' => function ($query) {
        //     $query->active();
        //     $query->where('job_staff.end_date', null);
        // }]); 
        return [
            'id' => $job->id,
            'name' => $job->name,
            'total_staff_count' => $job->total_staff_count,
            'current_staff_count' => $job->current_staff_count,
            'due_for_promotion' => $job->due_for_promotion,
            'active_male_staff_count' => $job->male_staff_count,
            'active_female_staff_count' => $job->female_staff_count,
        ];
        // return Inertia::render('Job/Stats', [
        //     'jobs' => $jobs->map(fn ($job) => [
        //         'id' => $job->id,
        //         'name' => $job->name,
        //         'staff' => $job->staff_count,
        //     ]),
        // ]);
    }
}