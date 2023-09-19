<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreJobCategoryRequest;
use App\Http\Requests\UpdateJobCategoryRequest;
use App\Models\JobCategory;
use Inertia\Inertia;

class JobCategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return Inertia::render('JobCategory/Index', [

            'categories' => JobCategory::query()
                ->withCount(
                    [
                        'jobs'
                    ]
                )
                ->with(['staff' => function ($query) {
                    $query->withCount(['staff' => function ($query) {
                        $query->whereHas('statuses', function ($query) {
                            $query->where('status', 'A');
                        });
                        $query->where('job_staff.end_date', null);
                    }]);
                }])
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
                    // 'staff' => $jobCategory->staff_count,
                    'parent' => $jobCategory->parent ? [
                        'name' => $jobCategory->parent->name,
                        'id' => $jobCategory->parent->id
                    ] : '',
                    'institution' => $jobCategory->institution->name,
                    'staff' => $jobCategory->staff->sum('staff_count'),

                ]),
            'filters' => request()->all([
                'search',
            ]),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return JobCategory::select(['id as value', 'name as label'])
            ->get();
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \App\Http\Requests\StoreJobCategoryRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreJobCategoryRequest $request)
    {
        $jobCategory = JobCategory::create($request->all());
        return redirect()->route('job-category.index')->with('success', 'Job Category created.');
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\JobCategory  $jobCategory
     * @return \Illuminate\Http\Response
     */
    public function show(JobCategory $jobCategory)
    {
        $jobCategory->load(['parent', 'jobs', 'institution'])
            ->load(['jobs' => function ($query) {
                $query->withCount(['staff' => function ($query) {
                    $query->whereHas('statuses', function ($query) {
                        $query->where('status', 'A');
                    });
                    $query->where('job_staff.end_date', null);
                }]);
            }])
            ->get();
        return Inertia::render('JobCategory/Show', [
            'job_category' => [
                'id' => $jobCategory->id,
                'name' => $jobCategory->name,
                'short_name' => $jobCategory->short_name,
                'job_categories' => $jobCategory->parent,
                'jobs' => $jobCategory->jobs ? $jobCategory->jobs->map(fn ($job) => [
                    'id' => $job->id,
                    'name' => $job->name,
                    'staff' => $job->staff_count,
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