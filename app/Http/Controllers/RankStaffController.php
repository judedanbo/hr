<?php

namespace App\Http\Controllers;

use App\Exports\RankAllListExport;
use App\Exports\RankPromotionListExport;
use App\Exports\RanksStaffExport;
use App\Models\Job;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

class RankStaffController extends Controller
{
    public function index($rank)
    {
        $staff = Job::find($rank)
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
            ->through(fn($staff) => [
                'id' => $staff->id,
                'file_number' => $staff->file_number,
                'staff_number' => $staff->staff_number,
                'name' => $staff->person->full_name,
                'current_unit' => [
                    'id' => $staff->units->first()?->id,
                    'name' => $staff->units->first()?->name,
                    'start_date' => $staff->units->first()?->pivot->start_date?->format('d M, Y'),
                    'remarks' => $staff->ranks->first()?->pivot->remarks,
                ],
                'last_promotion' => [
                    'start_date' => $staff->ranks->first()?->pivot->start_date?->format('d M, Y'),
                    'remarks' => $staff->ranks->first()?->pivot->remarks,
                ],

            ]);

        // Inertia::render('')
        // $staff->data = $staff->data->unique();
        return $staff;
    }

    public function promote($rank)
    {
        $staff = Job::find($rank)
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
                $query->whereYear('job_staff.start_date', '<=', Carbon::now()->subYears(3));
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
            ->through(fn($staff) => [
                'id' => $staff->id,
                'file_number' => $staff->file_number,
                'staff_number' => $staff->staff_number,
                'name' => $staff->person->full_name,
                'current_unit' => [
                    'id' => $staff->units->first()?->id,
                    'name' => $staff->units->first()?->name,
                    'start_date' => $staff->units->first()?->pivot->start_date?->format('d M, Y'),
                    'remarks' => $staff->ranks->first()?->pivot->remarks,
                ],
                'last_promotion' => [
                    'start_date' => $staff->ranks->first()?->pivot->start_date?->format('d M, Y'),
                    'remarks' => $staff->ranks->first()?->pivot->remarks,
                ],

            ]);

        return $staff;
    }

    public function active($rank)
    {
        $staff = Job::find($rank)
            ->activeStaff()
            ->active() // TODO Check for staff who has exited this ranks
            ->whereHas('ranks', function ($query) use ($rank) {
                $query->where('job_staff.job_id', $rank);
                $query->whereNull('job_staff.end_date');
            })
            ->with(['person', 'units', 'ranks'])
            ->paginate()
            ->withQueryString()
            ->through(fn($staff) => [
                'id' => $staff->id,
                'file_number' => $staff->file_number,
                'staff_number' => $staff->staff_number,
                'name' => $staff->person->full_name,
                'status' => $staff->status,
                'current_unit' => [
                    'id' => $staff->units->first()?->id,
                    'name' => $staff->units->first()?->name,
                    'start_date' => $staff->units->first()?->pivot->start_date?->format('d M, Y'),
                    'remarks' => $staff->ranks->first()?->pivot->remarks,
                ],
                'last_promotion' => [
                    'start_date' => $staff->ranks->first()?->pivot->start_date?->format('d M, Y'),
                    'remarks' => $staff->ranks->first()?->pivot->remarks,
                ],

            ]);

        // Inertia::render('')
        return $staff;
    }

    public function all($rank)
    {
        $staff = Job::find($rank)
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
            ->through(fn($staff) => [
                'id' => $staff->id,
                'file_number' => $staff->file_number,
                'staff_number' => $staff->staff_number,
                'name' => $staff->person?->full_name,
                'person_id' => $staff->person?->id,
                // 'current_rank' => [
                //     'id' => $staff->ranks->first()?->id,
                //     'name' => $staff->ranks->first()?->name,
                //     'start_date' => $staff->ranks->first()?->pivot->start_date?->format('d M, Y'),
                //     'remarks' => $staff->ranks->first()?->pivot->remarks,
                // ],
                'current_unit' => [
                    'id' => $staff->units->first()?->id,
                    'name' => $staff->units->first()?->name,
                    'start_date' => $staff->units->first()?->pivot->start_date?->format('d M, Y'),
                    'remarks' => $staff->units->first()?->pivot->remarks,
                ],
                'last_promotion' => [
                    'id' => $staff->ranks->first()?->id,
                    'name' => $staff->ranks->first()?->name,
                    'start_date' => $staff->ranks->first()?->pivot->start_date?->format('d M, Y'),
                    'remarks' => $staff->ranks->first()?->pivot->remarks,
                ],

            ]);

        // Inertia::render('')
        return $staff;
    }

    public function exportRank(Job $rank)
    {
        return Excel::download(new RanksStaffExport($rank), $rank->name . ' staff.xlsx');
    }

    public function exportPromotion(Job $rank)
    {
        if (!request()->user()->can('download unit promotion')) {
            activity()
                ->causedBy(request()->user())
                ->performedOn($rank)
                ->event('download')
                ->withProperties([
                    'result' => 'failed',
                    'user_ip' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                ])
                ->log('Promotion list for ' . $rank->name);
            return redirect()->back()->with('error', 'You are not authorized to download this file');
        }
        activity()
            ->causedBy(request()->user())
            ->performedOn($rank)
            ->event('download')
            ->withProperties([
                'result' => 'success',
                'user_ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ])
            ->log('Promotion list for ' . $rank->name);
        return Excel::download(new RankPromotionListExport($rank, request()->batch), request()->batch . ' ' . date('Y') . ' ' . Str::of($rank->name)->plural() . ' promotion list.xlsx');
    }

    public function exportAll(Job $rank)
    {
        return Excel::download(new RankAllListExport($rank), Str::of($rank->name)->plural() . ' all time list.xlsx');
    }
}
