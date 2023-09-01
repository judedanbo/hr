<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreJobRequest;
use App\Models\Job;
use Inertia\Inertia;

class JobController extends Controller
{
    public function index()
    {
        // $jobs = Job::withCount(['staff' => function ($query) {
        //     $query->active();
        //     $query->where('job_staff.end_date', null);
        // }])
        //     // ->where('institution_id', $institution)
        //     // ->where('name', 'like', 'Prin.Auditor%')
        //     // ->with('staff')
        //     ->get();
        // return ($jobs);
        return Inertia::render('Job/Index', [
            'jobs' =>
            Job::query()
                // ->whereHas('staff', function ($query) {
                //     $query->whereHas('statuses', function ($query) {
                //         $query->where('status', 'A');
                //     });
                //     $query->where('job_staff.end_date', null);
                // })
                // ->withCount(['staff'])
                // ->when(request()->search, function ($query, $search) {
                //     $query->where('name', 'like', "%{$search}%");
                // })
                ->when(request()->search, function ($query, $search) {
                    $query->where('name', 'like', "%{$search}%");
                })
                // ->whereHas('staff', function ($query) {
                //     $query->whereHas('statuses', function ($query) {
                //         $query->where('status', 'A');
                //     });
                //     $query->where('job_staff.end_date', null);
                // })
                ->with([
                    'institution',
                    'category',
                    'staff'
                ])
                ->withCount(['staff' => function ($query) {
                    $query->whereHas('statuses', function ($query) {
                        $query->where('status', 'A');
                    });
                    $query->where('job_staff.end_date', null);
                }])
                // ->orderBy('job_category_id is null', 'asc')
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
        $job = Job::with(['staff' => function ($query) {
            // $query->active();
            $query->whereHas('statuses', function ($query) {
                $query->where('status', 'A');
            });
            $query->where('job_staff.end_date', null);
            $query->when(request()->search, function ($query, $search) {
                $query->whereHas('person', function ($query) use ($search) {
                    $query->where('first_name', 'like', "%{$search}%");
                    $query->orWhere('surname', 'like', "%{$search}%");
                });
            });
        }, 'staff.units', 'staff.ranks', 'institution'])
            ->withCount(['staff' => function ($query) {
                $query->whereHas('statuses', function ($query) {
                    $query->where('status', 'A');
                });
                $query->where('job_staff.end_date', null);
            }])
            // ->active()
            ->find($job);
        // dd($job);
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
                    'image' => $staff->person->image,
                    'staff_number' => $staff->staff_number,
                    'file_number' => $staff->file_number,
                    'unit' => $staff->units?->first()?->name,
                    'unit_id' => $staff->units?->first()?->id,
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
}