<?php

namespace App\Http\Controllers;

use App\Models\InstitutionPerson;
use App\Models\JobStaff;
use Illuminate\Database\Eloquent\Builder;
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
                ->whereNull('job_staff.end_date')
                ->whereRaw("year(job_staff.start_date) < ?",  [date('Y') - 3])
                ->paginate()
                ->withQueryString(),
            'filters' => [
                'search' => Request()->search,
            ],
        ]);
    }

    public function show($year)
    {
        // return InstitutionPerson::query()
        //     ->active()
        //     ->promotion($year)
        //     ->unit()
        //     ->rank()
        //     ->with([
        //         'institution',
        //         'person',
        //         'units' => function ($query) {
        //             $query->whereNull('staff_unit.end_date');
        //         },
        //         'ranks' => function ($query) {
        //             $query->whereNull('job_staff.end_date');
        //         }
        //     ])
        //     ->paginate();
        // return InstitutionPerson::query()
        //     // ->join('job_staff', 'institution_person.id', '=', 'job_staff.staff_id')
        //     // ->join('jobs', 'job_staff.job_id', '=', 'jobs.id')
        //     // ->join('job_categories', 'jobs.job_category_id', '=', 'job_categories.id')
        //     ->active()
        //     ->promotion($year)
        //     ->unit()
        //     ->rank()
        //     ->with([
        //         'institution',
        //         'person',
        //         'units' => function ($query) {
        //             $query->whereNull('staff_unit.end_date');
        //         },
        //         'ranks' => function ($query) {
        //             $query->whereNull('job_staff.end_date');
        //         }
        //     ])
        //     // ->select(
        //     //     'institution_person.id as staff_id',
        //     //     'institution_person.staff_number',
        //     //     'institution_person.file_number',
        //     //     'jobs.id  as rank_id',
        //     //     'jobs.name  as rank_name',
        //     //     'job_staff.start_date  as promotion_date',
        //     //     'job_categories.level  as level',
        //     //     'units.id as unit_id',
        //     //     'units.name as unit_name',
        //     // )
        //     // ->whereNull('jobs.deleted_at')
        //     // ->whereNull('job_staff.end_date')
        //     // ->whereRaw("year(job_staff.start_date) < ?",  [date('Y') - 3])
        //     // ->orderBy('job_categories.level')
        //     ->paginate(2);


        $promotionList = InstitutionPerson::query()
            ->active()
            ->promotion($year)
            ->otherRanks()
            ->rank()
            ->with([
                'institution',
                'person',
                'units',
                'ranks' => function ($query) {
                    $query->whereNull('job_staff.end_date');
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
                'unit' => $staff->units->count() > 0 ? [
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
