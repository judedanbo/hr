<?php

namespace App\Http\Controllers;

use App\Models\JobCategory;
use Inertia\Inertia;

class CategoryRanks extends Controller
{
    public function show($category)
    {
        return Inertia::render('CategoryRanks/Index', [
            'ranks' => JobCategory::find($category)
                ->jobs()
                ->withCount('staff')
                ->when(request('search'), fn ($query, $search) => $query->where('name', 'like', '%' . $search . '%'))
                ->paginate()
                ->withQueryString()
                ->through(fn ($job) => [
                    'id' => $job->id,
                    'name' => $job->name,
                    'staff_count' => $job->staff_count,
                    'category_id' => $job->job_category_id,
                ]),
            'category' => $category,
            // 'ranks' => $category->jobs->map(fn($job)=> [
            //         'id' => $job->id,
            //         'name' => $job->name
            'filters' => ['search' => request()->search],
        ]);
        // return $ranks;
    }
}
