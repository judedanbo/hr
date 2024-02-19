<?php

namespace App\Http\Controllers;

use App\Models\Job;
use Illuminate\Http\Request;
use Inertia\Inertia;

class RankStaffController extends Controller
{
    public function index($rank){
        $staff =  Job::find($rank)
                ->staff()
                ->active() // TODO Check for staff who has exited this ranks
                ->whereHas('ranks', function ($query) use ($rank){
                    $query->whereNull('job_staff.end_date');
                    $query->where('job_staff.job_id', $rank);
                })
                ->with(['person', 'units', 'ranks'])
                ->paginate()
                ->withQueryString()
                ->through(fn($staff)=>[
                    'id' => $staff->id,
                    'file_number' => $staff->file_number,
                    'staff_number' => $staff->staff_number,
                    'name'=>$staff->person->full_name,
                    'current_unit' => [
                        'id' => $staff->units->first()?->id,
                        'name' => $staff->units->first()?->name,
                        'start_date' => $staff->units->first()?->pivot->start_date->format('d M, Y'),
                        'remarks' => $staff->ranks->first()?->pivot->remarks,
                    ],
                    'last_promotion' => [
                        'start_date' => $staff->ranks->first()?->pivot->start_date->format('d M, Y'),
                        'remarks' => $staff->ranks->first()?->pivot->remarks,
                    ]

                ]);
        // Inertia::render('')
        return $staff ;
    }
}
