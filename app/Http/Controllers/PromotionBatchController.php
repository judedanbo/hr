<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Models\JobStaff;
use Inertia\Inertia;

class PromotionBatchController extends Controller
{   
    public function index()
    {
        return $promotions = JobStaff::query()
        ->with(['job', 'staff' => function($query){
            $query->with(['statuses' => function($statQuery){
                $statQuery->where('status', 'A');
                $statQuery->whereNull('end_date');
            }] );
            // $query->with('person');
        }])
        // ->orderBy('start_date' , 'desc')
        ->whereYear('start_date','<=', date('Y')-3)
        ->whereNull('end_date')
        ->paginate();
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