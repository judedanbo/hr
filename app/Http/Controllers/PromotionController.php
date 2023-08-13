<?php

namespace App\Http\Controllers;

use App\Models\InstitutionPerson;
use App\Models\JobStaff;
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
        return Inertia::render('Promotion/Index', [
            'promotions' => JobStaff::query()
                ->with(['job'])
                ->selectRaw('Year(start_date) year, job_id, count(case when month   (start_date) <= 6 then 1 end) as april, count(case when month(start_date) > 6 then 1 end) as october')
                ->whereHas('staff', function ($query) {
                    $query->whereHas('statuses', function ($query) {
                        $query->where('status', 'A');
                    });
                })
                ->when(request()->search, function ($query, $search) {
                    $query->whereHas('job', function ($query) use ($search) {
                        $query->where('name', 'like', '%' . $search . '%');
                    });
                })
                ->groupByRaw('year, job_id')
                ->orderByRaw('year desc')
                ->whereNull('end_date')
                ->whereNotIn('job_id', [16, 35, 49, 65, 71])
                ->paginate()
                ->withQueryString()
                ->through(fn ($promotion) => [
                    'year' => $promotion->year,
                    'job_id' => $promotion->job_id,
                    'job_name' => $promotion->job->name,
                    'april' => $promotion->april,
                    'october' => $promotion->october,
                ]),
            'filters' => ['search' => request()->search],
        ]);
    }

    public function show(int $year = null)
    {

        if ($year == null) {
            $year = date('Y');
        }

        $promotions = InstitutionPerson::query()
            ->whereHas('ranks', function ($query) use ($year) {
                $query->whereNull('end_date');
                $query->whereYear('start_date', $year);
                $query->whereNotIn('job_id', [16, 35, 49, 65, 71]);
                // if ($month == 'april') {
                //     $query->whereMonth('start_date', '<=', 6);
                // } elseif ($month == 'october') {
                //     $query->whereMonth('start_date', '>', 6);
                // }
                // $query->whereMonth('start_date', '<=', $month == 'april' ? 6 : 12);
                // $query->when(request()->search, function ($whenQuery, $search) {
                //     $whenQuery->where('name', 'like', '%' . $search . '%');
                // });
                $query->when(request()->month, function ($whenQuery, $month) {
                    if ($month == 'april') {
                        $whenQuery->whereMonth('start_date', '<=', 4);
                    } elseif ($month == 'october') {
                        $whenQuery->whereMonth('start_date', '>', 4);
                    }
                });
                $query->when(request()->rank, function ($whenQuery, $rank) {
                    $whenQuery->where('job_id', $rank);
                });
            })
            ->whereHas('statuses', function ($query) {
                $query->where('status', 'A');
            })
            ->with(['person', 'institution', 'units', 'ranks' => function ($query) use ($year) {
                $query->whereNull('end_date');
                $query->whereYear('start_date', '<=', $year);
            }])
            ->get()
            // ->paginate()
            // ->withQueryString()
            ->map(fn ($staff) => [
                'id' => $staff->id,
                'staff_number' => $staff->staff_number,
                'file_number' => $staff->file_number,
                'full_name' => $staff->person->full_name,
                'institution' => $staff->institution->name,
                'unit' => $staff->units?->first(),
                'rank_id' => $staff->ranks->first()->id,
                'rank_name' => $staff->ranks->first()->name,
                'remarks' => $staff->ranks->first()->pivot->remarks,
                'start_date' => $staff->ranks->first()->pivot->start_date->format('d F Y'),
                'now' => date('Y-m-d'),
            ]);
        $promotions =  $promotions->sortByDesc('rank_name')->groupBy('rank_name');

        return Inertia::render(
            'Promotion/Show',
            [
                'promotions' => $promotions,
                'filters' => [
                    'search' => request()->search,
                    'month' => request()->month,
                    'rank' => request()->rank,
                    'year' => $year,
                ],
            ]
        );
    }

    public function byRanks(int $year = null, int $rank = null)
    {
        $promotions = InstitutionPerson::query()
            ->whereHas('ranks', function ($query) {
                $query->whereNull('end_date');
                $query->where('job_id', $rank);
            })
            ->whereHas('statuses', function ($query) {
                $query->where('status', 'A');
            })
            ->with(['person', 'institution', 'units', 'ranks' => function ($query) {
                $query->whereNull('end_date');
            }])
            ->get()
            ->map(fn ($staff) => [
                'id' => $staff->id,
                'staff_number' => $staff->staff_number,
                'file_number' => $staff->file_number,
                'full_name' => $staff->person->full_name,
                'institution' => $staff->institution->name,
                'unit' => $staff->units?->first(),
                'rank_id' => $staff->ranks->first()->id,
                'rank_name' => $staff->ranks->first()->name,
                'remarks' => $staff->ranks->first()->pivot->remarks,
                'start_date' => $staff->ranks->first()->pivot->start_date->format('d F Y'),
                'now' => date('Y-m-d'),
            ]);
        $promotions =  $promotions->sortByDesc('rank_name')->groupBy('rank_name');

        return Inertia::render(
            'Promotion/ByRanks',
            [
                'promotions' => $promotions,
                'filters' => ['search' => request()->search],
            ]
        );
    }
}