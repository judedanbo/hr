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
            'promotions' =>  InstitutionPerson::query()
                ->active()
                ->join('job_staff', 'institution_person.id', '=', 'job_staff.staff_id')
                ->join('jobs', 'job_staff.job_id', '=', 'jobs.id')
                ->join('job_categories', 'jobs.job_category_id', '=', 'job_categories.id')
                ->when(request()->search, function ($query, $search) {
                    $terms = explode(' ', $search);
                    foreach ($terms as $term) {
                        $query->where(function ($searchName) use ($term) {
                            $searchName->where('jobs.name', 'like', "%{$term}%");
                        });
                    }
                })
                ->selectRaw('jobs.name as job_name, job_staff.job_id as job_id, count(case when month(job_staff.start_date) <= 4 then 1 end) as april, count(case when month(job_staff.start_date) > 4 then 1 end) as october, count(*) as staff')
                ->groupByRaw('job_name, job_id')
                ->orderByRaw('job_categories.level')
                ->whereNull('jobs.deleted_at')
                ->whereRaw("year(job_staff.start_date) < " . date('Y') - 3)
                ->paginate()
                ->withQueryString(),
            'filters' => [
                'search' => Request()->search,
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
                $searchQuery->where(function ($whereQuery) use ($search) {
                    $whereQuery->searchPerson($search);
                    $whereQuery->searchRank($search);
                });
            })
            ->with([
                'institution',
                'person',
                'units',
                'ranks' => function ($query) {
                    $query->when(request()->rank, function ($searchQuery, $rank) {
                        $searchQuery->where('job_staff.job_id', $rank);
                    });
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
                'retirement_date' => $staff->person->date_of_birth->addYears(60)->format('d M, Y'),
                'institution' => $staff->institution->name,
                'unit' => $staff->units ? [
                    'id' => $staff->units?->first()?->id,
                    'name' => $staff->units?->first()?->name,
                    'start_date' => $staff->units?->first()?->pivot->start_date?->format('d M, Y'),
                ] : [],
                'rank_id' => $staff->ranks?->first()?->id,
                'rank_name' => $staff->ranks?->first()?->name,
                'remarks' => $staff->ranks?->first()?->pivot->remarks,
                'start_date' => $staff->ranks?->first()?->pivot->start_date?->format('d M, Y'),
                'now' => date('Y-m-d'),
            ]);

        return Inertia::render(
            'PromotionRank/Show',
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
