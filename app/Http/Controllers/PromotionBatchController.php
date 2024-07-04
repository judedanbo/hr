<?php

namespace App\Http\Controllers;

use App\Models\InstitutionPerson;
use App\Models\Job;
use App\Models\JobStaff;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\JobCategory;

class PromotionBatchController extends Controller
{

    public function index()
    {
        $ranks = Job::query()
            ->otherRanks()
            ->withCount([
                'staff',
                'activeStaff',
                'staffToPromote',
                'staffToPromoteApril',
                'staffToPromoteOctober'
            ])
            ->when(request()->search, function ($query, $search) {
                $query->searchRank($search);
            })
            ->orderBy(JobCategory::select('level')
                ->whereColumn('job_categories.id', 'jobs.job_category_id'))
            ->paginate()
            ->withQueryString()
            ->through(fn ($promotion) => [
                'job_id' => $promotion->id,
                'job_name' => $promotion->name,
                'april' => $promotion->staff_to_promote_april_count,
                'october' => $promotion->staff_to_promote_october_count,
                'staff_to_promote' => $promotion->staff_to_promote_count,
                'all_staff' => $promotion->active_staff_count,
            ]);
        // return $ranks;
        return Inertia::render('PromotionRank/Index', [
            'promotions' =>  $ranks,
            'filters' => [
                'search' => Request()->search,
            ],
        ]);
    }

    public function show(Request $request, $year)
    {
        $staff =  Job::find($request->rank)
            ->activeStaff()
            ->active() // TODO Check for staff who has exited this ranks
            ->when(request()->search, function ($query, $search) {
                $query->whereHas('person', function ($query) use ($search) {
                    $query->search($search);
                });
            })
            ->whereHas('ranks', function ($query) use ($request) {
                $query->whereNull('job_staff.end_date');
                $query->where('job_staff.job_id', $request->rank);
                $query->whereYear('job_staff.start_date', '<=', now()->year - 3);
                $query->when(request()->batch == 'april', function ($query) {
                    $query->where(function ($query) {
                        $query->whereRaw('month(job_staff.start_date) IN (1, 2, 3, 11, 12)');
                        $query->orWhere(function ($query) {
                            $query->whereMonth('job_staff.start_date', 4);
                            $query->whereDay('job_staff.start_date', 1);
                        });
                        $query->orWhere(function ($query) {
                            $query->whereMonth('job_staff.start_date', 10);
                            $query->whereDay('job_staff.start_date', '>', 1);
                        });
                    });
                });
                $query->when(request()->batch == 'october', function ($query) {
                    $query->where(function ($query) {
                        $query->whereRaw('month(job_staff.start_date) IN (5, 6, 7, 8, 9)');
                        $query->orWhere(function ($query) {
                            $query->whereMonth('job_staff.start_date', 10);
                            $query->whereDay('job_staff.start_date', 1);
                        });
                        $query->orWhere(function ($query) {
                            $query->whereMonth('job_staff.start_date', 4);
                            $query->whereDay('job_staff.start_date', '>', 1);
                        });
                    });
                });
            })
            ->with(['person', 'units', 'ranks'])
            ->paginate()
            ->withQueryString()
            ->through(fn ($staff) => [
                'staff_id' => $staff->id,
                'staff_number' => $staff->staff_number,
                'file_number' => $staff->file_number,
                'staff_name' => $staff->person->full_name,
                'retirement_date' => $staff->person->date_of_birth->addYears(60)->format('d M, Y'),
                'retirement_date_diff' => $staff->person->date_of_birth->addYears(60)->diffForHumans(),
                'institution' => $staff->institution->name,
                'unit' => $staff->units->count() > 0 ? [
                    'id' => $staff->units->first()->id,
                    'name' => $staff->units->first()->name,
                    'start_date' => $staff->units->first()->pivot->start_date?->format('d M, Y'),
                    'start_date_diff' => $staff->units->first()->pivot->start_date?->diffForHumans(),
                ] : [],
                'rank_id' => $staff->ranks->first()->id,
                'rank_name' => $staff->ranks->first()->name,
                'remarks' => $staff->ranks->first()->pivot->remarks,
                'start_date' => $staff->ranks->first()->pivot->start_date->format('d M, Y'),
                'start_date_diff' => $staff->ranks->first()->pivot->start_date->diffForHumans(),
                'now' => date('Y-m-d'),
            ]);
        // $promotionList = InstitutionPerson::query()
        //     ->active()
        //     ->join('job_staff', 'institution_person.id', '=', 'job_staff.staff_id')
        //     ->join('jobs', 'job_staff.job_id', '=', 'jobs.id')
        //     ->join('job_categories', 'jobs.job_category_id', '=', 'job_categories.id')
        //     ->with(['person', 'ranks', 'units'])
        //     ->orderByRaw('job_categories.level')
        //     ->whereNull('jobs.deleted_at')
        //     ->whereNull('job_staff.end_date')
        //     ->where('job_staff.job_id', $request->rank)
        //     ->when($request->period, function ($query, $request) {
        //         if ($request == 'april') {
        //             $query->whereRaw('month(job_staff.start_date) in (1, 2, 3, 4, 11, 12)');
        //         }
        //         if ($request == 'october') {
        //             $query->whereRaw('month(job_staff.start_date) in (5, 6, 7, 8, 9, 10)');
        //         }
        //     })
        //     ->whereRaw("year(job_staff.start_date) < " . date('Y') - 3)
        //     ->paginate()
        //     ->withQueryString()
        //     ->through(fn ($staff) => [
        //         'staff_id' => $staff->id,
        //         'staff_number' => $staff->staff_number,
        //         'file_number' => $staff->file_number,
        //         'staff_name' => $staff->person->full_name,
        //         'retirement_date' => $staff->person->date_of_birth->addYears(60)->format('d M, Y'),
        //         'retirement_date_diff' => $staff->person->date_of_birth->addYears(60)->diffForHumans(),
        //         'institution' => $staff->institution->name,
        //         'unit' => $staff->units->count() > 0 ? [
        //             'id' => $staff->units?->first()?->id,
        //             'name' => $staff->units?->first()?->name,
        //             'start_date' => $staff->units?->first()?->pivot->start_date?->format('d M, Y'),
        //             'start_date_diff' => $staff->units?->first()?->pivot->start_date?->diffForHumans(),
        //         ] : [],
        //         'rank_id' => $staff->ranks?->first()?->id,
        //         'rank_name' => $staff->ranks?->first()?->name,
        //         'remarks' => $staff->ranks?->first()?->pivot->remarks,
        //         'start_date' => $staff->ranks?->first()?->pivot->start_date?->format('d M, Y'),
        //         'start_date_diff' => $staff->ranks?->first()?->pivot->start_date?->diffForHumans(),
        //         'now' => date('Y-m-d'),
        //     ]);

        return Inertia::render(
            'PromotionRank/Show',
            [
                'promotions' => $staff,
                'rank' => $request->rank,
                'filters' => [
                    'search' => request()->search,
                    'year' => $year
                ],
            ]
        );
    }
}
