<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreJobCategoryRequest;
use App\Http\Requests\UpdateJobCategoryRequest;
use App\Models\JobCategory;
use Inertia\Inertia;

class JobCategoryController extends Controller
{
    public function index()
    {
        return Inertia::render('JobCategory/Index', [

            'categories' => JobCategory::query()
                ->withCount(
                    [
                        'jobs'
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
                                $query->whereYear('job_staff.start_date', '<=', now()->year - 3);
                            },
                            'staff as all_count',
                        ]);
                    },
                ])
                ->when(request()->search, function ($query, $search) {
                    $query->where('name', "like", "%" . $search . "%");
                    $query->orWhere('short_name', 'like', "%$search%");
                })
                ->with(['parent', 'institution'])
                ->paginate()
                ->withQueryString()
                ->through(fn ($jobCategory) => [
                    'id' => $jobCategory->id,
                    'name' => $jobCategory->name,
                    'short_name' => $jobCategory->short_name,
                    'level' => $jobCategory->level,
                    'jobs' => $jobCategory->jobs_count,
                    'parent' => $jobCategory->parent ? [
                        'name' => $jobCategory->parent->name,
                        'id' => $jobCategory->parent->id
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
        $jobCategory = JobCategory::create($request->all());
        return redirect()->route('job-category.index')->with('success', 'Job Category created.');
    }


    public function show(JobCategory $jobCategory)
    {
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
                            $query->whereYear('job_staff.start_date', '<=', now()->year - 3);
                        });
                    },
                    'staff as all'

                ]);
            }])
            ->get();
        // dd($jobCategory->jobs);
        // if ($jobCategory->jobs->count() === 1) {
        //     return redirect()->route('job.show', ['job' => $jobCategory->jobs->first()->id]);
        // }
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
                'jobs' => $jobCategory->jobs ? $jobCategory->jobs->map(fn ($job) => [
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
     * @param  \App\Models\JobCategory  $jobCategory
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
     * @param  \App\Http\Requests\UpdateJobCategoryRequest  $request
     * @param  \App\Models\JobCategory  $jobCategory
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateJobCategoryRequest $request, JobCategory $jobCategory)
    {
        $jobCategory->update($request->all());
        return redirect()->route('job-category.index')->with('success', 'Job Category updated.');
    }

    public function delete(JobCategory $jobCategory)
    {
        $jobCategory->delete();
        return redirect()->route('job-category.index')->with('success', 'Job Category deleted.');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\JobCategory  $jobCategory
     * @return \Illuminate\Http\Response
     */
    public function destroy(JobCategory $jobCategory)
    {
        $jobCategory->delete();
        return redirect()->route('job-category.index')->with('success', 'Job Category deleted.');
    }
}
