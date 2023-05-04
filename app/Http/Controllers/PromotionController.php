<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\JobStaff;
use Illuminate\Support\Facades\DB;

class PromotionController extends Controller
{
    public function index()
    {
        $promotions = JobStaff::query()
        ->with(['job', 'staff'])
        ->select('start_date', 'job_id', DB::raw('count(staff_id) as Staff'))
        ->groupBy('start_date', 'job_id')
        ->orderBy('start_date' , 'desc')
        ->where('remarks', '<>' ,'1st Appointment')
        ->whereYear('start_date','>=', date('Y')-2)
        ->get();
        
        $promotionsByEffectiveDates = $promotions->groupBy('start_date');
        // return $promotionsByEffectiveDates;
        return Inertia::render('Promotion/Index', [
            'promotions' => $promotionsByEffectiveDates->map(function ($promotions, $start_date) {
                return [
                    'effective_date' => $start_date,
                    'promos' => $promotions->map(function ($promotion) {
                        return [
                            'job_id' => $promotion->job_id,
                            'job_name' => $promotion->job->name,
                            'staff' => $promotion->Staff,
                        ];
                    }),
                ];
            }),
        ]);
    }

    public function show(JobStaff $promotion)
    {
        $promotion->load(['job', 'staff.person']);
        return Inertia::render('Promotion/Show', [
            'promotion' => [
                'id' => $promotion->id,
                'job_id' => $promotion->job_id,
                'job_name' => $promotion->job->name,
                'staff_id' => $promotion->staff_id,
                'surname' => $promotion->staff->person->surname,
                'first_names' => $promotion->staff->person->first_name,
                'other_names' => $promotion->staff->person->other_name,
                'start_date' => $promotion->start_date,
                'end_date' => $promotion->end_date,
                'remarks' => $promotion->remarks,
            ],
        ]);
    }
}