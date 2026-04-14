<?php

namespace App\Http\Controllers;

use App\Models\Job;
use App\Models\JobCategory;
use App\Models\Unit;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

class StaffSearchOptionsController extends Controller
{
    /**
     * Get all filter options for staff advanced search
     */
    public function index(): JsonResponse
    {
        $options = Cache::remember('staff_search_options', 3600, function () {
            return [
                'jobCategories' => $this->getJobCategories(),
                'jobs' => $this->getJobs(),
                'units' => $this->getUnits(),
                'departments' => $this->getDepartments(),
                'statuses' => $this->getStatuses(),
                'genders' => $this->getGenders(),
            ];
        });

        return response()->json($options);
    }

    /**
     * Get job categories
     */
    public function jobCategories(): JsonResponse
    {
        return response()->json($this->getJobCategories());
    }

    /**
     * Get jobs/ranks
     */
    public function jobs(): JsonResponse
    {
        return response()->json($this->getJobs());
    }

    /**
     * Get units
     */
    public function units(): JsonResponse
    {
        return response()->json($this->getUnits());
    }

    /**
     * Get departments (parent units)
     */
    public function departments(): JsonResponse
    {
        return response()->json($this->getDepartments());
    }

    /**
     * Get all job categories
     */
    protected function getJobCategories(): array
    {
        return JobCategory::query()
            ->whereHas('jobs.staff')
            ->select('id', 'name', 'short_name')
            ->orderBy('name')
            ->get()
            ->map(fn ($category) => [
                'value' => $category->id,
                'label' => $category->name,
                'short_name' => $category->short_name,
            ])
            ->toArray();
    }

    /**
     * Get all jobs/ranks that have active staff
     */
    protected function getJobs(): array
    {
        return Job::query()
            ->with('category:id,name,short_name')
            ->whereHas('staff', function ($query) {
                $query->whereNull('job_staff.end_date');
            })
            ->select('id', 'name', 'job_category_id')
            ->orderBy('name')
            ->get()
            ->map(fn ($job) => [
                'value' => $job->id,
                'label' => $job->name,
                'category' => $job->category?->name,
                'category_id' => $job->job_category_id,
            ])
            ->toArray();
    }

    /**
     * Get all units that have active staff
     */
    protected function getUnits(): array
    {
        return Unit::query()
            ->with('parent:id,name,short_name')
            ->whereHas('staff', function ($query) {
                $query->whereNull('staff_unit.end_date');
            })
            ->select('id', 'name', 'short_name', 'unit_id')
            ->orderBy('name')
            ->get()
            ->map(fn ($unit) => [
                'value' => $unit->id,
                'label' => $unit->name,
                'short_name' => $unit->short_name,
                'department' => $unit->parent?->name,
                'department_id' => $unit->unit_id,
            ])
            ->toArray();
    }

    /**
     * Get all departments (parent units)
     */
    protected function getDepartments(): array
    {
        return Unit::query()
            ->whereNull('unit_id')
            ->whereHas('subs.staff', function ($query) {
                $query->whereNull('staff_unit.end_date');
            })
            ->select('id', 'name', 'short_name')
            ->orderBy('name')
            ->get()
            ->map(fn ($dept) => [
                'value' => $dept->id,
                'label' => $dept->name,
                'short_name' => $dept->short_name,
            ])
            ->toArray();
    }

    /**
     * Get available statuses
     */
    protected function getStatuses(): array
    {
        return [
            ['value' => 'A', 'label' => 'Active'],
            ['value' => 'R', 'label' => 'Retired'],
            ['value' => 'L', 'label' => 'Leave'],
            ['value' => 'S', 'label' => 'Suspended'],
            ['value' => 'E', 'label' => 'Separated'],
        ];
    }

    /**
     * Get gender options
     */
    protected function getGenders(): array
    {
        return [
            ['value' => 'M', 'label' => 'Male'],
            ['value' => 'F', 'label' => 'Female'],
        ];
    }
}
