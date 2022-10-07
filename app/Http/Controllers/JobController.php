<?php

namespace App\Http\Controllers;

use App\Models\Job;
use Illuminate\Http\Request;
use Inertia\Inertia;

class JobController extends Controller
{
    public function index()
    {
        $jobs = Job::withCount('staff')
            // ->where('institution_id', $institution)
        // ->where('name', 'like', 'Prin.Auditor%')
        ->get();
        return Inertia::render('Job/Index', [
            'jobs' =>  Job::withCount('staff')
                ->when(request()->search, function($query, $search){
                    $query->where('name', 'like', "%{$search}%");
                })
                ->with('institution')
                ->paginate(10)
                ->withQueryString()
                ->through(fn($job)=>[
                    'id' => $job->id,
                    'name' => $job->name,
                    'staff' => $job->staff_count,
                    'institution' => [
                        'id'=>$job->institution->id,
                        'name'=>$job->institution->name,
                    ]
                    ]),
            'filters' => ['search' => request()->search],
        ]);
    }


    public function show($job)
    {
        $job = Job::with('staff.person','staff.unit','institution')
            ->withCount('staff')
            ->find($job);
        return Inertia::render('Job/Show', [
            'job' => [
                'id' => $job->id,
                'name' => $job->name,
                'staff_count' => $job->staff_count,
                'staff' => $job->staff->map(fn($staff)=>[
                    'id' => $staff->id,
                    'name' => $staff->person->full_name,
                    'staff_number' => $staff->staff_number,
                    'unit' => $staff->unit->name,
                    'unit_id' => $staff->unit->id,
                ]),
            ],
            ]);
    }
}