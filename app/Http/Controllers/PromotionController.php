<?php

namespace App\Http\Controllers;

use App\Models\InstitutionPerson;
use App\Models\Job;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PromotionController extends Controller
{
    // protected $year;
    // protected $month;
    // public function __construct( $year = null,   $month = null)
    // {
    //     if ($year == null) {
    //         $this->year = date('Y');
    //     }
    //     $this->year = $year;

    //     if ($month == null) {
    //         $this->month = 'april';
    //     }
    //     $this->month = $month;
    // }

    public function index()
    {
        $promotions = InstitutionPerson::query()
            ->active()
            ->selectRaw(
                'jobs.id as job_id,
                jobs.name as job_name,
                 YEAR(job_staff.start_date) as year,
                 COUNT(CASE WHEN MONTH(job_staff.start_date) IN (1,2,3,4,11,12) THEN 1 END) as april,
                COUNT(CASE WHEN MONTH(job_staff.start_date) IN (5,6,7,8,9,10) THEN 1 END) as october,
                COUNT(job_staff.start_date) as total
                 '
            )
            ->join('job_staff', 'job_staff.staff_id', '=', 'institution_person.id')
            ->join('jobs', 'jobs.id', '=', 'job_staff.job_id')
            ->join('job_categories', 'job_categories.id', '=', 'jobs.job_category_id')
            ->groupBy('jobs.name', 'job_id', 'year', 'job_categories.level')
            ->when(request()->search, function ($query, $search) {
                $terms = explode(' ', $search);
                foreach ($terms as $term) {
                    $query->where(function ($searchQuery) use ($term) {
                        $searchQuery->where('jobs.name', 'like', '%' . $term . '%');
                    });
                }
                // $query->where('jobs.name', 'like', '%' . $search . '%');
                $query->orWhereRaw("year(job_staff.start_date) like '%" . $search . "%'");
            })
            ->orderBy('year', 'desc')
            ->orderBy('job_categories.level', 'asc')
            ->paginate()
            ->withQueryString();

        return Inertia::render('Promotion/Index', [
            'promotions' => $promotions->through(fn($promotion) => [
                'year' => $promotion->year,
                'job_id' => $promotion->job_id,
                'job_name' => $promotion->job_name,
                'april' => $promotion->april,
                'october' => $promotion->october,
            ]),
            'filters' => ['search' => request()->search],
        ]);
    }

    public function show(Request $request, ?int $year = null)
    {

        // dd($request->year);
        $rank = Job::find($request->rank)?->only('id', 'name');

        if ($year == null) {
            $year = date('Y');
        }

        $promotions = InstitutionPerson::query()
            ->active()
            ->currentRank()
            ->join('job_staff', 'job_staff.staff_id', '=', 'institution_person.id')
            ->join('jobs', 'jobs.id', '=', 'job_staff.job_id')
            ->join('job_categories', 'job_categories.id', '=', 'jobs.job_category_id')
            // ->where('job_staff.job_id', $request->rank)
            // ->whereNull('job_staff.end_date')
            ->when($request->rank, function ($query, $rank) {
                // $query->where('job_staff.job_id', $rank);
                $query->where('jobs.id', $rank);
            })
            ->when($request->month, function ($query, $month) {
                if ($month == 'april') {
                    $query->whereRaw('month(job_staff.start_date) IN (1,2,3,4,11,12)');
                } elseif ($month == 'october') {
                    $query->whereRaw('month(job_staff.start_date) In (5,6,7,8,9,10)');
                }
            })
            ->whereYear('job_staff.start_date', $year)
            // ->when(request()->rank, function ($query, $rank) {
            //     $query->where('job_staff.job_id', $rank);
            // })
            // ->whereHas('ranks', function ($query) use ($year) {

            //     $query->when(request()->month, function ($whenQuery, $month) {
            //         if ($month == 'april') {
            //             $whenQuery->whereRaw('month(start_date) IN (1,2,3,4,11,12)');
            //         } elseif ($month == 'october') {
            //             $whenQuery->whereRaw('month(start_date) In (5,6,7,8,9,10)');
            //         }
            //     });
            // $query->when(request()->rank, function ($whenQuery, $rank) {
            //     $whenQuery->where('job_id', $rank);
            // });
            // })
            // ->join('job_categories', 'job_categories.id', '=', 'jobs.job_category_id')
            // ->whereHas('statuses', function ($query) {
            //     $query->where('status', 'A');
            // })
            ->with([
                'ranks' => function ($query) {
                    // $query->wherePivot('job_id', request()->rank);
                },
                'person',
                'institution',
                'units',
                'statuses',
            ])
            // ->orderBy('job_categories.level')
            ->get()
            // ->paginate()
            // ->withQueryString()
            ->map(fn($staff) => [
                // 'staff' => $staff->ranks,
                'id' => $staff->id,
                'person_id' => $staff->person_id,
                'staff_number' => $staff->staff_number,
                'file_number' => $staff->file_number,
                'full_name' => $staff->person->full_name,
                // 'institution' => $staff->institution->name,
                // 'unit' => $staff->units?->first(),
                // 'rank_id' => $staff->ranks->first()?->id,
                // 'rank_name' => $staff->ranks->first()?->name,
                // 'remarks' => $staff->ranks->first()?->pivot->remarks,
                'status' => $staff->statuses->first()?->status->label(),
                'start_date' => $staff->ranks->first()?->pivot->start_date->format('d F Y'),
                'now' => date('Y-m-d'),
                // 'test_rank' => $staff->ranks,
            ]);
        // return $promotions;
        $promotions = $promotions->sortByDesc('rank_name')->groupBy('rank_name');


        return Inertia::render(
            'Promotion/Show',
            [
                'promotions' => $promotions,
                'rank' => $rank,
                'filters' => [
                    'search' => request()->search,
                    'month' => request()->month,
                    'rank' => request()->rank,
                    'year' => $year,
                ],
            ]
        );
    }

    // public function byRanks(int $year = null, int $rank = null)
    // {
    //     $promotions = InstitutionPerson::query()
    //         ->whereHas('ranks', function ($query) {
    //             $query->whereNull('end_date');
    //             $query->where('job_id', $rank);
    //         })
    //         ->whereHas('statuses', function ($query) {
    //             $query->where('status', 'A');
    //         })
    //         ->with(['person', 'institution', 'units', 'ranks' => function ($query) {
    //             $query->whereNull('end_date');
    //         }])
    //         ->get()
    //         ->map(fn ($staff) => [
    //             'id' => $staff->id,
    //             'staff_number' => $staff->staff_number,
    //             'file_number' => $staff->file_number,
    //             'full_name' => $staff->person->full_name,
    //             'institution' => $staff->institution->name,
    //             'unit' => $staff->units?->first(),
    //             'rank_id' => $staff->ranks->first()->id,
    //             'rank_name' => $staff->ranks->first()->name,
    //             'remarks' => $staff->ranks->first()->pivot->remarks,
    //             'start_date' => $staff->ranks->first()->pivot->start_date->format('d F Y'),
    //             'now' => date('Y-m-d'),
    //         ]);
    //     $promotions =  $promotions->sortByDesc('rank_name')->groupBy('rank_name');

    //     return Inertia::render(
    //         'Promotion/ByRanks',
    //         [
    //             'promotions' => $promotions,
    //             'filters' => ['search' => request()->search],
    //         ]
    //     );
    // }
}
