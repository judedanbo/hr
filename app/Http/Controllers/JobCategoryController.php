<?php

namespace App\Http\Controllers;

use App\Exports\HarmonizedGradeSummaryExport;
use App\Http\Requests\StoreJobCategoryRequest;
use App\Http\Requests\UpdateJobCategoryRequest;
use App\Models\Job;
use App\Models\JobCategory;
use Carbon\Carbon;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Maatwebsite\Excel\Excel;

class JobCategoryController extends Controller
{
    public function index()
    {
        if (!Gate::allows('view all job categories')) {
            activity()
                ->causedBy(auth()->user())
                ->event('index')
                ->withProperties([
                    'result' => 'failed',
                    'user_ip' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                ])
                ->log('attempted access to view all job categories');
            return redirect()->back()->with('error', 'You are not authorized to all job categories.');
        }
        activity()
            ->causedBy(auth()->user())
            ->event('index')
            ->withProperties([
                'result' => 'success',
                'user_ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ])
            ->log('viewed all job categories');
        return Inertia::render('JobCategory/Index', [
            'categories' => JobCategory::query()
                ->withCount(
                    [
                        'jobs',
                    ]
                )
                ->with([
                    'staff' => function ($query) {
                        $query->withCount([
                            'staff as active_count' => function ($query) {
                                $query->active();
                                $query->where('job_staff.end_date', null);
                            },
                            'staff as promotion_count' => function ($query) {
                                $query->active();
                                $query->where('job_staff.end_date', null);
                                $query->whereYear('job_staff.start_date', '<=', Carbon::now()->subYears(3));
                            },
                            'staff as all_count',
                        ]);
                    },
                ])
                ->when(request()->search, function ($query, $search) {
                    $query->where('name', 'like', '%' . $search . '%');
                    $query->orWhere('short_name', 'like', "%$search%");
                })
                ->with(['parent', 'institution'])
                ->paginate()
                ->withQueryString()
                ->through(fn($jobCategory) => [
                    'id' => $jobCategory->id,
                    'name' => $jobCategory->name,
                    'short_name' => $jobCategory->short_name,
                    'level' => $jobCategory->level,
                    'jobs' => $jobCategory->jobs_count,
                    'parent' => $jobCategory->parent ? [
                        'name' => $jobCategory->parent->name,
                        'id' => $jobCategory->parent->id,
                    ] : '',
                    'institution' => $jobCategory->institution->name,
                    'institution_id' => $jobCategory->institution->id,
                    'staff' => $jobCategory->staff->sum('active_count'),
                    'promotion' => $jobCategory->staff->sum('promotion_count'),
                    'all' => $jobCategory->staff->sum('all_count'),

                ]),
            'filters' => request()->all([
                'search',
            ]),
        ]);
    }

    public function create()
    {
        return JobCategory::select(['id as value', 'name as label'])
            ->get();
    }

    public function store(StoreJobCategoryRequest $request)
    {
        if (!Gate::allows('create job category')) {
            activity()
                ->causedBy(auth()->user())
                ->event('store')
                ->withProperties([
                    'result' => 'failed',
                    'user_ip' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                ])
                ->log('attempted to create a job category');
            return redirect()->back()->with('error', 'You are not authorized to create a job category.');
        }
        $jobCategory = JobCategory::create($request->all());

        return redirect()->route('job-category.index')->with('success', 'Job Category created.');
    }

    public function show(JobCategory $jobCategory)
    {
        if (!Gate::allows('view job category')) {
            activity()
                ->causedBy(auth()->user())
                ->event('show')
                ->withProperties([
                    'result' => 'failed',
                    'user_ip' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                ])
                ->log('attempted to view a job category');
            return redirect()->back()->with('error', 'You are not authorized to view this job category.');
        }
        $jobCategory->load(['parent', 'jobs', 'institution'])
            ->load(['jobs' => function ($query) {
                $query->withCount([
                    'activeStaff' => function ($query) {
                        $query->active();
                    },
                    'activeStaff as promotion' => function ($query) {
                        $query->active();
                        $query->whereHas('ranks', function ($query) {
                            $query->whereNull('job_staff.end_date');
                            $query->whereYear('job_staff.start_date', '<=', Carbon::now()->subYears(3));
                        });
                    },
                    'staff as all',

                ]);
            }])
            ->get();

        // if ($jobCategory->jobs->count() === 1) {
        //     return redirect()->route('job.show', ['job' => $jobCategory->jobs->first()->id]);
        // }
        activity()
            ->causedBy(auth()->user())
            ->performedOn($jobCategory)
            ->event('show')
            ->withProperties([
                'result' => 'success',
                'user_ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ])
            ->log('viewed a job category');
        return Inertia::render('JobCategory/Show', [
            'category' => [
                'id' => $jobCategory->id,
                'name' => $jobCategory->name,
                'short_name' => $jobCategory->short_name,
                'job_categories' => $jobCategory->parent,
                'institution_id' => $jobCategory->institution_id,
                'level' => $jobCategory->level,
                'job_category_id' => $jobCategory->job_category_id,
                'start_date' => $jobCategory->start_date?->format('Y-m-d'),
                'jobs' => $jobCategory->jobs ? $jobCategory->jobs->map(fn($job) => [
                    'id' => $job->id,
                    'name' => $job->name,
                    'staff_count' => $job->active_staff_count,
                    'promotion_count' => $job->promotion,
                    'all_count' => $job->all,
                ]) : '',
                'parent' => $jobCategory->parent ? [
                    'id' => $jobCategory->parent->id,
                    'name' => $jobCategory->parent->name,
                    'short_name' => $jobCategory->parent->short_name,
                ] : '',
                'institution' => $jobCategory->institution,
            ],
            'filters' => request()->all([
                'search',
            ]),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function edit(JobCategory $jobCategory)
    {
        return Inertia::render('JobCategory/Edit', [
            'job_category' => JobCategory::select(['id', 'name', 'short_name'])
                ->get(),

        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateJobCategoryRequest $request, JobCategory $jobCategory)
    {
        if (!Gate::allows('edit job category')) {
            activity()
                ->causedBy(auth()->user())
                ->event('update')
                ->withProperties([
                    'result' => 'failed',
                    'user_ip' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                ])
                ->log('attempted to update a job category');
            return redirect()->back()->with('error', 'You are not authorized to update this job category.');
        }
        $jobCategory->update($request->all());

        return redirect()->route('job-category.index')->with('success', 'Job Category updated.');
    }

    public function delete(JobCategory $jobCategory)
    {
        if (!Gate::allows('delete job category')) {
            activity()
                ->causedBy(auth()->user())
                ->event('delete')
                ->withProperties([
                    'result' => 'failed',
                    'user_ip' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                ])
                ->log('attempted to delete a job category');
            return redirect()->back()->with('error', 'You are not authorized to delete this job category.');
        }
        $jobCategory->delete();

        return redirect()->route('job-category.index')->with('success', 'Job Category deleted.');
    }
    /**
     * Restore the specified resource from storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function restore(JobCategory $jobCategory)
    {
        if (!Gate::allows('restore job category')) {
            activity()
                ->causedBy(auth()->user())
                ->event('restore')
                ->withProperties([
                    'result' => 'failed',
                    'user_ip' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                ])
                ->log('attempted to restore a job category');
            return redirect()->back()->with('error', 'You are not authorized to restore this job category.');
        }
        $jobCategory->restore();

        return redirect()->route('job-category.index')->with('success', 'Job Category restored.');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function destroy(JobCategory $jobCategory)
    {
        if (!Gate::allows('destroy job category')) {
            activity()
                ->causedBy(auth()->user())
                ->event('destroy')
                ->withProperties([
                    'result' => 'failed',
                    'user_ip' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                ])
                ->log('attempted to destroy a job category');
            return redirect()->back()->with('error', 'You are not authorized to destroy this job category.');
        }
        $jobCategory->forceDelete();

        return redirect()->route('job-category.index')->with('success', 'Job Category deleted.');
    }

    public function summary(Excel $excel)
    {
        if (!Gate::allows('download job summary')) {
            activity()
                ->causedBy(auth()->user())
                ->event('download')
                ->withProperties([
                    'result' => 'failed',
                    'user_ip' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                ])
                ->log('attempted to download job category summary');
            return redirect()->back()->with('error', 'You are not authorized to download job summary.');
        }
        activity()
            ->causedBy(auth()->user())
            ->event('download')
            ->withProperties([
                'result' => 'success',
                'user_ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ])
            ->log('downloaded job category summary');
        return $excel->download(new HarmonizedGradeSummaryExport, 'harmonized grades summary.xlsx');
    }
}
