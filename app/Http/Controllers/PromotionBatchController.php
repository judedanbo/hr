<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Models\InstitutionPerson;
use App\Models\JobStaff;
use Inertia\Inertia;

class PromotionBatchController extends Controller
{   
    protected $year;
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
        
        // return Inertia::render('PromotionRank/Index', [
            // 'promotions' => InstitutionPerson::query()
            // return InstitutionPerson::query()
            //     ->select()
            //     ->active()
            //     ->whereHas('ranks', function($query){
            //         $query->whereNull('end_date');
            //         $query->whereYear('start_date','<=', $this->year - 3);
            //         $query->whereNotIn('job_id', [16,35,49, 65,71]);
            //         $query->whereMonth('start_date', '<=', '07');
            //     })
            //     ->with(['person', 'institution', 'units', 'ranks' => function($query){
            //         $query->whereNull('end_date');
            //         $query->whereYear('start_date','<=', $this->year - 3);
            //     }])
            //     ->orderBy(
            //         JobStaff::select('start_date')
            //             ->whereColumn('staff_id', 'institution_person.id')
            //             ->orderBy('start_date', 'desc')
            //             ->limit(1)
            //     )
            return JobStaff::query()
            ->with(['job'])
            ->selectRaw('Year(start_date) year, job_id, count(case when month(start_date) <= 6 then 1 end) as april, count(case when month(start_date) > 6 then 1 end) as october')
            ->groupByRaw('year, job_id')
            ->orderByRaw('year desc')
            // ->havingRaw("Year(start_date) <= " . $this->year - 3 )
            ->where('remarks', '<>' ,'1st Appointment')
            // ->whereYear('start_date','<=', $this->year - 3)
            ->get();
                
            // 'filter' => ['search' => Request()->search]
        // ]
        // );
    }
    public function show($year)
    {
        return Inertia::render('PromotionRank/Index', [
            'promotions' => InstitutionPerson::query()
                ->active()
                ->whereHas('ranks', function($query){
                    $query->whereNull('end_date');
                    $query->whereYear('start_date','<=', $this->year);
                    $query->whereMonth('start_date','<=', $this->month == 'april' ? '06' : '12');
                    $query->whereNotIn('job_id', [16,35,49, 65,71]);
                    $query->whereMonth('start_date', '<=', '07');
                })
                ->with(['person', 'institution', 'units', 'ranks' => function($query){
                    $query->whereNull('end_date');
                    $query->whereYear('start_date','<=', $this->year - 3);
                }])
                ->orderBy(
                    JobStaff::select('start_date')
                        ->whereColumn('staff_id', 'institution_person.id')
                        ->orderBy('start_date', 'desc')
                        ->limit(1)
                )
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
    // public function show($date)
    // {
    //     return $promotions = JobStaff::query()
    //     ->with(['job', 'staff' => function($query){
    //         $query->where('status', 'A');
    //         $query->whereNull('end_date');
    //         $query->with('person');
    //     }])
    //     // ->select('start_date', 'job_id', DB::raw('count(staff_id) as Staff'))
    //     // ->groupBy('start_date', 'job_id')
    //     ->orderBy('job_id')
    //     ->where('remarks', '<>' ,'1st Appointment')
    //     ->whereDate('start_date', $date)
    //     ->paginate();
    // }
}