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

                ->paginate()
                ->withQueryString(),
            'filters' => ['search' => Request()->search],
        ]);
    }

    public function show($year, $month)
    {
        return Inertia::render('PromotionRank/Index', [
            'promotions' => InstitutionPerson::query()
                ->when(request()->search, function ($query, $search) {
                    $query->where('staff_number', 'LIKE', '%' . $search . '%');
                    // ->orWhere('file_number', 'LIKE', '%'.$search.'%');
                })
                ->whereHas('statuses', function ($query) {
                    $query->where('status', 'A');
                })
                ->whereHas('ranks', function ($query) use ($year, $month) {
                    $query->whereNull('end_date');
                    $query->whereYear('start_date', '<', $year - 3);

                    $query->whereNotIn('job_id', [16, 35, 49, 65, 71]);
                    if ($month == 'april') {
                        $query->whereMonth('start_date', '<=', 6);
                    } elseif ($month == 'october') {
                        $query->whereMonth('start_date', '>', 6);
                    }
                })
                ->with(['person', 'institution', 'units', 'ranks' => function ($query) use ($year) {
                    $searchYear = $year - 3;

                    $query->whereNull('end_date');
                    $query->whereYear('start_date', '<', $searchYear);
                }])
                ->orderBy(
                    JobStaff::select('start_date')
                        ->whereColumn('staff_id', 'institution_person.id')
                        ->orderBy('start_date', 'desc')
                        ->limit(1)
                )
                ->paginate()
                ->withQueryString()
                ->through(fn ($staff) => [
                    'id' => $staff->id,
                    'staff_number' => $staff->staff_number,
                    'file_number' => $staff->file_number,
                    'surname' => $staff->person->surname,
                    'first_name' => $staff->person->first_name,
                    'other_name' => $staff->person->other_name,
                    'institution' => $staff->institution->name,
                    'unit' => $staff->units?->first(),
                    'rank_id' => $staff->ranks?->first()?->id,
                    'rank_name' => $staff->ranks?->first()?->name,
                    'remarks' => $staff->ranks?->first()?->pivot->remarks,
                    'start_date' => $staff->ranks?->first()?->pivot->start_date,
                    'now' => date('Y-m-d'),
                ]),
            'filters' => [
                'search' => Request()->search,
                'year' => $year,
                'month' => $month,
            ],
        ]
        );
    }
}
