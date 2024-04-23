<?php

namespace App\Http\Controllers;

use App\Exports\RankAllListExport;
use App\Exports\RankPromotionListExport;
use App\Exports\RanksStaffExport;
use App\Models\Job;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Str;

class RankStaffController extends Controller
{
    public function index($rank)
    {
        $staff =  Job::find($rank)
            ->activeStaff()
            ->active() // TODO Check for staff who has exited this ranks
            ->when(request()->search, function ($query, $search) {
                $query->whereHas('person', function ($query) use ($search) {
                    $query->search($search);
                });
            })
            ->whereHas('ranks', function ($query) use ($rank) {
                // $query->searchRank(request()->search);
                $query->whereNull('job_staff.end_date');
                $query->where('job_staff.job_id', $rank);
            })
            ->with(['person', 'units', 'ranks'])
            ->paginate()
            ->withQueryString()
            ->through(fn ($staff) => [
                'id' => $staff->id,
                'file_number' => $staff->file_number,
                'staff_number' => $staff->staff_number,
                'name' => $staff->person->full_name,
                'current_unit' => [
                    'id' => $staff->units->first()?->id,
                    'name' => $staff->units->first()?->name,
                    'start_date' => $staff->units->first()?->pivot->start_date->format('d M, Y'),
                    'remarks' => $staff->ranks->first()?->pivot->remarks,
                ],
                'last_promotion' => [
                    'start_date' => $staff->ranks->first()?->pivot->start_date->format('d M, Y'),
                    'remarks' => $staff->ranks->first()?->pivot->remarks,
                ]

            ]);
        // Inertia::render('')
        // $staff->data = $staff->data->unique();
        return $staff;
    }

    function promote($rank)
    {
        $staff =  Job::find($rank)
            ->activeStaff()
            ->active() // TODO Check for staff who has exited this ranks
            ->when(request()->search, function ($query, $search) {
                $query->whereHas('person', function ($query) use ($search) {
                    $query->search($search);
                });
            })
            ->whereHas('ranks', function ($query) use ($rank) {
                $query->whereNull('job_staff.end_date');
                $query->where('job_staff.job_id', $rank);
                $query->whereYear('job_staff.start_date', '<=', now()->year - 3);
            })
            ->with(['person', 'units', 'ranks'])
            ->paginate()
            ->withQueryString()
            ->through(fn ($staff) => [
                'id' => $staff->id,
                'file_number' => $staff->file_number,
                'staff_number' => $staff->staff_number,
                'name' => $staff->person->full_name,
                'current_unit' => [
                    'id' => $staff->units->first()?->id,
                    'name' => $staff->units->first()?->name,
                    'start_date' => $staff->units->first()?->pivot->start_date->format('d M, Y'),
                    'remarks' => $staff->ranks->first()?->pivot->remarks,
                ],
                'last_promotion' => [
                    'start_date' => $staff->ranks->first()?->pivot->start_date->format('d M, Y'),
                    'remarks' => $staff->ranks->first()?->pivot->remarks,
                ]

            ]);
        // Inertia::render('')
        return $staff;
    }
    function active($rank)
    {
        $staff =  Job::find($rank)
            ->activeStaff()
            ->active() // TODO Check for staff who has exited this ranks
            ->whereHas('ranks', function ($query) use ($rank) {
                $query->where('job_staff.job_id', $rank);
                $query->whereNull('job_staff.end_date');
            })
            ->with(['person', 'units', 'ranks'])
            ->paginate()
            ->withQueryString()
            ->through(fn ($staff) => [
                'id' => $staff->id,
                'file_number' => $staff->file_number,
                'staff_number' => $staff->staff_number,
                'name' => $staff->person->full_name,
                'status' => $staff->status,
                'current_unit' => [
                    'id' => $staff->units->first()?->id,
                    'name' => $staff->units->first()?->name,
                    'start_date' => $staff->units->first()?->pivot->start_date->format('d M, Y'),
                    'remarks' => $staff->ranks->first()?->pivot->remarks,
                ],
                'last_promotion' => [
                    'start_date' => $staff->ranks->first()?->pivot->start_date->format('d M, Y'),
                    'remarks' => $staff->ranks->first()?->pivot->remarks,
                ]

            ]);
        // Inertia::render('')
        return $staff;
    }
    function all($rank)
    {
        $staff =  Job::find($rank)
            ->staff()
            ->when(request()->search, function ($query, $search) {
                $query->whereHas('person', function ($query) use ($search) {
                    $query->search($search);
                });
            })
            ->whereHas('ranks', function ($query) use ($rank) {
                $query->where('job_staff.job_id', $rank);
            })
            ->with(['person', 'units', 'ranks'])
            ->paginate()
            ->withQueryString()
            ->through(fn ($staff) => [
                'id' => $staff->id,
                'file_number' => $staff->file_number,
                'staff_number' => $staff->staff_number,
                'name' => $staff->person?->full_name,
                'person_id' => $staff->person?->id,
                // 'current_rank' => [
                //     'id' => $staff->ranks->first()?->id,
                //     'name' => $staff->ranks->first()?->name,
                //     'start_date' => $staff->ranks->first()?->pivot->start_date->format('d M, Y'),
                //     'remarks' => $staff->ranks->first()?->pivot->remarks,
                // ],
                'current_unit' => [
                    'id' => $staff->units->first()?->id,
                    'name' => $staff->units->first()?->name,
                    'start_date' => $staff->units->first()?->pivot->start_date->format('d M, Y'),
                    'remarks' => $staff->units->first()?->pivot->remarks,
                ],
                'last_promotion' => [
                    'id' => $staff->ranks->first()?->id,
                    'name' => $staff->ranks->first()?->name,
                    'start_date' => $staff->ranks->first()?->pivot->start_date->format('d M, Y'),
                    'remarks' => $staff->ranks->first()?->pivot->remarks,
                ]

            ]);
        // Inertia::render('')
        return $staff;
    }

    function exportRank(Job $rank)
    {
        return Excel::download(new RanksStaffExport($rank), $rank->name . ' staff.xlsx');
    }
    function exportPromotion(Job $rank)
    {
        return Excel::download(new RankPromotionListExport($rank), Str::of($rank->name)->plural() . ' promotion list.xlsx');
    }
    function exportAll(Job $rank)
    {
        return Excel::download(new RankAllListExport($rank), Str::of($rank->name)->plural() . ' all time list.xlsx');
    }
}
