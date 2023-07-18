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
            'jobs' => Job::withCount(['staff' => function ($query) {
                $query->active();
                $query->where('job_staff.end_date', null);
            }])
                ->when(request()->search, function ($query, $search) {

                    $query->where('name', 'like', "%{$search}%");
                })
                ->with(['institution', 'staff' => function ($query) {
                    $query->active();
                }])

                ->paginate(10)
                ->withQueryString()
                ->through(fn ($job) => [
                    'id' => $job->id,
                    'name' => $job->name,
                    'staff' => $job->staff_count,
                    'institution' => [
                        'id' => $job->institution->id,
                        'name' => $job->institution->name,
                    ],
                ]),
            'filters' => ['search' => request()->search],
        ]);
    }

    public function show($job)
    {
        $job = Job::with(['staff' => function ($query) {
            $query->active();
            $query->where('job_staff.end_date', null);
        }, 'staff.units', 'institution'])
            ->withCount(['staff' => function ($query) {
                $query->active();
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
                    'staff_number' => $staff->staff_number,
                    'unit' => $staff->units?->first()?->name,
                    'unit_id' => $staff->units?->first()?->id,
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