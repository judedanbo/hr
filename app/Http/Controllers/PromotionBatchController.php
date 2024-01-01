<?php

namespace App\Http\Controllers;

use App\Models\InstitutionPerson;
use App\Models\JobStaff;
use Inertia\Inertia;

class PromotionBatchController extends Controller
{
    public function index()
    {
        return Inertia::render('PromotionRank/Index', [
            'promotions' => JobStaff::query()
                ->with(['job'])
                ->selectRaw('Year(start_date) year, job_id, count(case when month(start_date) <= 6 then 1 end) as april, count(case when month(start_date) > 6 then 1 end) as october')
                ->groupByRaw('year, job_id')
                ->orderByRaw('year desc')

                // ->where('remarks', '<>' ,'1st Appointment')

                ->paginate(4)
                ->withQueryString(),
            'filters' => [
                'search' => Request()->search, 
                // "year" => 
            ],
        ]);
    }

    public function show($year)
    {
       $promotionList = InstitutionPerson::query()
                ->active()
                // ->otherRanks()
                ->promotion($year)

                ->when(request()->search, function ($searchQuery, $search) {
                    $searchQuery->where(function($whereQuery) use ($search) {
                        $whereQuery->searchPerson($search);
                        $whereQuery->searchRank($search);
                    });
                })
                ->with([
                    'person',
                    'ranks' => function($query){
                        // $query->take(1);
                        // $query->where('name', 'like', '%principal%');
                        // $query->whereRaw('start_date');
                        $query->wherePivotNull('end_date');
                    }
                ])
                ->paginate()
                ->withQueryString()
                    ->through(fn ($staff) => [
                        'staff_id' => $staff->id,
                        'staff_number' => $staff->staff_number,
                        'file_number' => $staff->file_number,
                        'staff_name' => $staff->person->full_name,
                        'retirement_date' => $staff->person->date_of_birth->addYears(60)->format('Y-m-d'),
                        'institution' => $staff->institution->name,
                        'unit' => $staff->units ? [
                            'id' => $staff->units?->first()?->id,
                            'name' => $staff->units?->first()?->name,
                            'start_date' => $staff->units?->first()?->pivot->start_date,
                        ] : [],
                        'rank_id' => $staff->ranks?->first()?->id,
                        'rank_name' => $staff->ranks?->first()?->name,
                        'remarks' => $staff->ranks?->first()?->pivot->remarks,
                        'start_date' => $staff->ranks?->first()?->pivot->start_date,
                        'now' => date('Y-m-d'),
                    ]);
       
        return Inertia::render(
            'PromotionRank/Index',
            [
                'promotions' => $promotionList,
                'filters' => [
                    'search' => Request()->search,
                    'year' => $year 
                ],
            ]
        );
    }
}