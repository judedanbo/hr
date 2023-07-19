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

    public function show($year)
    {
        // return InstitutionPerson::query()
        //     ->active()
        //     ->whereHas('ranks', function ($query) use ($year, $month) {
        //         $query->whereNull('end_date');
        //         $query->whereYear('start_date', '<', $year - 3);

        //         //         // $query->whereNotIn('job_id', [16, 35, 49, 65, 71]);
        //         //         // if ($month == 'april') {
        //         //         //     $query->whereMonth('start_date', '<=', 4);
        //         //         // } elseif ($month == 'october') {
        //         //         //     $query->whereMonth('start_date', '>', 4);
        //         //         // }
        //     })
        //     ->orWhereHas('units', function ($query) {
        //         $query->whereNull('staff_unit.end_date');
        //     })
        //     ->with(['person', 'institution', 'units' => function ($query) {
        //         $query->whereNull('staff_unit.end_date');
        //     }, 'ranks' => function ($query) {    
        //         $query->whereNull('end_date');
        //     }])
        //     ->get();
        return Inertia::render(
            'PromotionRank/Index',
            [
                'promotions' => InstitutionPerson::query()
                    ->when(request()->search, function ($query, $search) {
                        if ($search != '') {
                            $query->where(function ($whereQuery) use ($search) {
                                $whereQuery->where('staff_number', 'LIKE', '%' . $search . '%');
                                $whereQuery->orWhere('file_number', 'LIKE', '%' . $search . '%');

                                $whereQuery->orWhereHas('person', function ($query) use ($search) {
                                    $query->where('surname', 'LIKE', '%' . $search . '%');
                                    $query->orWhere('first_name', 'LIKE', '%' . $search . '%');
                                    $query->orWhere('other_names', 'LIKE', '%' . $search . '%');
                                });
                                $whereQuery->orWhereHas('ranks', function ($query) use ($search) {
                                    $query->whereNull('end_date');
                                    $query->where('name', 'LIKE', '%' . $search . '%');
                                });
                            });
                        }
                    })
                    ->active()
                    ->whereHas('ranks', function ($query) use ($year) {
                        // $query->whereNull('end_date');
                        // dd($year);
                        $query->whereYear('start_date', '<', $year - 3);

                        $query->whereNotIn('job_id', [16, 35, 49, 65, 71]);
                        // if ($month == 'april') {
                        //     $query->whereMonth('start_date', '<=', 4);
                        // } elseif ($month == 'october') {
                        //     $query->whereMonth('start_date', '>', 4);
                        // }
                    })
                    ->with(['person', 'institution', 'units' => function ($query) {
                        $query->whereNull('staff_unit.end_date');
                    }, 'ranks' => function ($query) {
                        $query->whereNull('end_date');
                    }])
                    ->with(['person', 'institution', 'units'  => function ($query) {
                        $query->whereNull('staff_unit.end_date');
                    }, 'ranks' => function ($query) use ($year) {
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
                    ]),
                'filters' => [
                    'search' => Request()->search,

                ],
            ]
        );
    }
}