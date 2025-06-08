<?php

namespace App\Http\Controllers;

use App\Models\Job;

class RankStaffStatsController extends Controller
{
    public function __invoke(Job $job)
    {
        // dd($job);
        return $job
            ->loadCount([
                'activeStaff as total',
                'activeStaff as male' => function ($query) {
                    $query->where();
                },
                'activeStaff as female',
            ])
            ->load(['activeStaff.person']);
        // ->whereHas('ranks', function ($query) use ($job) {
        //     $query->whereNull('job_staff.end_date');
        //     $query->where('job_staff.job_id', $job->id);
        // });
    }
}
