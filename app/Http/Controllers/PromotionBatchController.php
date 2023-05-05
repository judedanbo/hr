<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Models\InstitutionPerson;
use App\Models\JobStaff;
use Inertia\Inertia;

class PromotionBatchController extends Controller
{   
    public function index()
    {
        return Inertia::render('PromotionRank/Index', [
            'promotions' => InstitutionPerson::query()
                ->active()
                ->whereHas('ranks', function($query){
                    $query->whereNull('end_date');
                    $query->whereYear('start_date','<=', date('Y')-3);
                })
                ->with(['person', 'institution', 'units', 'ranks' => function($query){
                    $query->whereNull('end_date');
                    $query->whereYear('start_date','<=', date('Y')-3);
                }])
                ->paginate()
                ->through(fn ($staff) => [
                    'id' => $staff->id,
                    'staff_number' => $staff->staff_number,
                    'file_number' => $staff->file_number,
                    'surname' => $staff->person->surname,
                    'first_name' => $staff->person->first_name,
                    'other_name' => $staff->person->other_name,
                    'institution' => $staff->institution->name,
                    'unit' => $staff->units?->first(),
                    'rank_id' => $staff->ranks->first()->id,
                    'rank_name' => $staff->ranks->first()->name,
                    'remarks' => $staff->ranks->first()->pivot->remarks,
                    'start_date' => $staff->ranks->first()->pivot->start_date,
                    'now' => date('Y-m-d'),
                ]),
            'filter' => ['search' => Request()->search]
        ]
        );
    }
    public function show($date)
    {
        return $promotions = JobStaff::query()
        ->with(['job', 'staff' => function($query){
            $query->where('status', 'A');
            $query->whereNull('end_date');
            $query->with('person');
        }])
        // ->select('start_date', 'job_id', DB::raw('count(staff_id) as Staff'))
        // ->groupBy('start_date', 'job_id')
        ->orderBy('job_id')
        ->where('remarks', '<>' ,'1st Appointment')
        ->whereDate('start_date', $date)
        ->paginate();
    }
}