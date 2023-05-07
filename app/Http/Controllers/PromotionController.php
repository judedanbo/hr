<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\JobStaff;
use App\Models\Job;
use App\Models\InstitutionPerson;
use Illuminate\Support\Facades\DB;

class PromotionController extends Controller
{
    protected $year;
    protected $month;
    public function __construct(int $year = null, String $month = null)
    {
        if (!$year) {
            $this->year = (int) date('Y');
        }
        $this->year = $year;

        if (!$month) {
            $this->month = 'april';
        }
        $this->month = $month;
    }
    
    public function index()
    {
        // $promotions = JobStaff::query()
        // ->with(['job', 'staff'])
        // ->select('start_date', 'job_id', DB::raw('count(staff_id) as Staff'))
        // ->groupBy('start_date', 'job_id')
        // ->orderBy('start_date' , 'desc')
        // ->where('remarks', '<>' ,'1st Appointment')
        // ->whereYear('start_date','>=', date('Y')-2)
        // ->get();
        return Inertia::render('Promotion/Index', [
            'promotions' => JobStaff::query()
            ->with(['job'])
            ->selectRaw('Year(start_date) year, count(case when month(start_date) <= 6 then 1 end) as april, count(case when month(start_date) > 6 then 1 end) as october')
            ->groupByRaw('year')
            ->orderByRaw('year desc')
            // ->havingRaw("year <= " . $this->year - 3 )
            ->where('remarks', '<>' ,'1st Appointment')
            // ->whereYear('start_date','<=', $this->year - 3)
            ->paginate()
            ->through(fn($promotion) => [
                'year' => $promotion->year,
                'job_id' => $promotion->job_id,
                // 'job_name' => $promotion->job->name,
                'april' => $promotion->april,
                'october' => $promotion->october,
            ])
        ]);
        // return $promotions;
        // $promotionsByEffectiveDates = $promotions->groupBy('start_date');
        // // return $promotionsByEffectiveDates;
        // return Inertia::render('Promotion/Index', [
        //     'promotions' => $promotionsByEffectiveDates->map(function ($promotions, $start_date) {
        //         return [
        //             'effective_date' => $start_date,
        //             'promos' => $promotions->map(function ($promotion) {
        //                 return [
        //                     'job_id' => $promotion->job_id,
        //                     'job_name' => $promotion->job->name,
        //                     'staff' => $promotion->Staff,
        //                 ];
        //             }),
        //         ];
        //     }),
        // ]);
    }

    public function show( int $year)
    {
        // return $year;
        // $promotion->load(['job', 'staff.person']);
        // return Inertia::render('Promotion/Show', [
        //     'promotion' => [
        //         'id' => $promotion->id,
        //         'job_id' => $promotion->job_id,
        //         'job_name' => $promotion->job->name,
        //         'staff_id' => $promotion->staff_id,
        //         'surname' => $promotion->staff->person->surname,
        //         'first_names' => $promotion->staff->person->first_name,
        //         'other_names' => $promotion->staff->person->other_name,
        //         'start_date' => $promotion->start_date,
        //         'end_date' => $promotion->end_date,
        //         'remarks' => $promotion->remarks,
        //     ],
        // ]);
        // return $year;
        $promotions = InstitutionPerson::query()
        ->active()
        ->whereHas('ranks', function($query) use ($year){
            $query->whereNull('end_date');
            $query->whereYear('start_date', $year);
            $query->whereNotIn('job_id', [16,35,49, 65,71]);
            $query->whereMonth('start_date', '<=', '06');
            $query->whereRaw("remarks != '1st Appointment'");
        })
        ->with(['person', 'institution', 'units', 'ranks' => function($query)use ($year){
            $query->whereNull('end_date');
            $query->whereYear('start_date','<=', $year);
            $query->whereRaw("remarks != '1st Appointment'");
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
            
        ]);

        return Inertia::render('PromotionRank/Index', [
            'promotions' => $promotions,
            'filter' => ['search' => Request()->search]
        ]
        );
        
    }
}