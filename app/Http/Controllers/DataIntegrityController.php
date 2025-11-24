<?php

namespace App\Http\Controllers;

use App\Models\InstitutionPerson;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class DataIntegrityController extends Controller
{
    public function index()
    {
        if (Gate::denies('data-integrity.view')) {
            activity()
                ->causedBy(auth()->user())
                ->event('view')
                ->withProperties([
                    'result' => 'failed',
                    'user_ip' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                ])
                ->log('Attempted to view data integrity dashboard');

            return redirect()->route('dashboard')->with('error', 'You do not have permission to view data integrity checks.');
        }

        // Count staff with multiple active ranks
        $multipleRanksCount = InstitutionPerson::query()
            ->active()
            ->whereHas('ranks', function ($query) {
                $query->whereNull('job_staff.end_date');
            })
            ->with(['ranks' => function ($query) {
                $query->whereNull('job_staff.end_date');
            }])
            ->get()
            ->filter(fn($staff) => $staff->ranks->count() > 1)
            ->count();

        activity()
            ->causedBy(auth()->user())
            ->event('view')
            ->withProperties([
                'result' => 'success',
                'user_ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ])
            ->log('Viewed data integrity dashboard');

        return Inertia::render('DataIntegrity/Index', [
            'checks' => [
                [
                    'id' => 'multiple-ranks',
                    'title' => 'Staff with Multiple Active Ranks',
                    'description' => 'Staff members who have more than one rank with no end date',
                    'count' => $multipleRanksCount,
                    'severity' => $multipleRanksCount > 0 ? 'warning' : 'success',
                    'route' => route('data-integrity.multiple-ranks'),
                ],
            ],
        ]);
    }

    public function multipleRanks()
    {
        if (Gate::denies('data-integrity.view')) {
            activity()
                ->causedBy(auth()->user())
                ->event('view')
                ->withProperties([
                    'result' => 'failed',
                    'user_ip' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                ])
                ->log('Attempted to view multiple ranks issues');

            return redirect()->route('dashboard')->with('error', 'You do not have permission to view data integrity checks.');
        }

        $staffWithIssues = InstitutionPerson::query()
            ->active()
            ->whereHas('ranks', function ($query) {
                $query->whereNull('job_staff.end_date');
            })
            ->with(['ranks' => function ($query) {
                $query->whereNull('job_staff.end_date')->orderBy('job_staff.start_date', 'desc');
            }, 'person'])
            ->get()
            ->filter(fn($staff) => $staff->ranks->count() > 1)
            ->map(function ($staff) {
                return [
                    'id' => $staff->id,
                    'staff_number' => $staff->staff_number,
                    'file_number' => $staff->file_number,
                    'name' => $staff->person->full_name,
                    'active_ranks_count' => $staff->ranks->count(),
                    'ranks' => $staff->ranks->map(function ($rank) {
                        return [
                            'id' => $rank->id,
                            'pivot_id' => $rank->pivot->id,
                            'name' => $rank->name,
                            'start_date' => $rank->pivot->start_date->format('Y-m-d'),
                            'start_date_formatted' => $rank->pivot->start_date->format('d M Y'),
                            'end_date' => $rank->pivot->end_date,
                        ];
                    }),
                ];
            })
            ->values();

        activity()
            ->causedBy(auth()->user())
            ->event('view')
            ->withProperties([
                'result' => 'success',
                'count' => $staffWithIssues->count(),
                'user_ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ])
            ->log('Viewed staff with multiple active ranks');

        return Inertia::render('DataIntegrity/MultipleRanks', [
            'staff' => $staffWithIssues,
        ]);
    }

    public function fixMultipleRanks(InstitutionPerson $staff)
    {
        if (Gate::denies('data-integrity.fix')) {
            activity()
                ->causedBy(auth()->user())
                ->performedOn($staff)
                ->event('fix')
                ->withProperties([
                    'result' => 'failed',
                    'user_ip' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                ])
                ->log('Attempted to fix multiple ranks for staff');

            return redirect()->back()->with('error', 'You do not have permission to fix data integrity issues.');
        }

        DB::beginTransaction();
        try {
            // Get all active ranks ordered by start date (newest first)
            $activeRanks = $staff->ranks()
                ->whereNull('job_staff.end_date')
                ->orderBy('job_staff.start_date', 'desc')
                ->get();

            if ($activeRanks->count() <= 1) {
                return redirect()->back()->with('info', 'This staff member does not have multiple active ranks.');
            }

            // Keep the most recent rank active, set end_date for others
            $mostRecentRank = $activeRanks->first();
            $olderRanks = $activeRanks->skip(1);

            foreach ($olderRanks as $oldRank) {
                // Set end_date to one day before the next promotion
                $endDate = $mostRecentRank->pivot->start_date->copy()->subDay();
                $record = DB::table('job_staff')
                    ->where('staff_id', $oldRank->pivot->staff_id)
                    ->where('job_id', $oldRank->pivot->job_id)
                    ->whereNull('end_date');

                $update = $record
                    ->update(['end_date' => $endDate]);
            }

            activity()
                ->causedBy(auth()->user())
                ->performedOn($staff)
                ->event('fix')
                ->withProperties([
                    'result' => 'success',
                    'fixed_ranks' => $olderRanks->count(),
                    'kept_rank' => $mostRecentRank->name,
                    'user_ip' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                ])
                ->log('Fixed multiple ranks for staff');

            DB::commit();

            return redirect()->back()->with('success', "Fixed rank assignments for {$staff->person->full_name}. Set end dates for {$olderRanks->count()} older rank(s).");
        } catch (\Exception $e) {
            DB::rollBack();

            activity()
                ->causedBy(auth()->user())
                ->performedOn($staff)
                ->event('fix')
                ->withProperties([
                    'result' => 'error',
                    'error' => $e->getMessage(),
                    'user_ip' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                ])
                ->log('Error fixing multiple ranks for staff');

            return redirect()->back()->with('error', 'An error occurred while fixing the rank assignments.');
        }
    }

    public function bulkFixMultipleRanks()
    {
        if (Gate::denies('data-integrity.fix')) {
            activity()
                ->causedBy(auth()->user())
                ->event('bulk-fix')
                ->withProperties([
                    'result' => 'failed',
                    'user_ip' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                ])
                ->log('Attempted to bulk fix multiple ranks');

            return redirect()->back()->with('error', 'You do not have permission to fix data integrity issues.');
        }

        DB::beginTransaction();
        try {
            $staffWithIssues = InstitutionPerson::query()
                ->active()
                ->whereHas('ranks', function ($query) {
                    $query->whereNull('job_staff.end_date');
                })
                ->with(['ranks' => function ($query) {
                    $query->whereNull('job_staff.end_date')->orderBy('job_staff.start_date', 'desc');
                }])
                ->get()
                ->filter(fn($staff) => $staff->ranks->count() > 1);

            $fixedCount = 0;
            $totalRanksFixed = 0;

            foreach ($staffWithIssues as $staff) {
                $activeRanks = $staff->ranks->sortBy(function ($rank) {
                    return $rank->pivot->start_date->timestamp * -1;
                });
                // dd($activeRanks);
                $mostRecentRank = $activeRanks->first();
                $olderRanks = $activeRanks->skip(1);

                $endDate = $mostRecentRank->pivot->start_date->copy()->subDay();
                foreach ($olderRanks as $oldRank) {

                    DB::table('job_staff')
                        ->where('staff_id', $oldRank->pivot->staff_id)
                        ->where('job_id', $oldRank->pivot->job_id)
                        ->whereNull('end_date')
                        ->update(['end_date' => $endDate]);

                    $totalRanksFixed++;
                    $endDate = $oldRank->pivot->start_date->copy()->subDay();
                }

                $fixedCount++;
            }

            activity()
                ->causedBy(auth()->user())
                ->event('bulk-fix')
                ->withProperties([
                    'result' => 'success',
                    'staff_fixed' => $fixedCount,
                    'ranks_fixed' => $totalRanksFixed,
                    'user_ip' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                ])
                ->log('Bulk fixed multiple ranks issues');

            DB::commit();

            return redirect()->back()->with('success', "Successfully fixed rank assignments for {$fixedCount} staff member(s). Set end dates for {$totalRanksFixed} older rank(s).");
        } catch (\Exception $e) {
            DB::rollBack();

            activity()
                ->causedBy(auth()->user())
                ->event('bulk-fix')
                ->withProperties([
                    'result' => 'error',
                    'error' => $e->getMessage(),
                    'user_ip' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                ])
                ->log('Error bulk fixing multiple ranks');

            return redirect()->back()->with('error', 'An error occurred while fixing the rank assignments.');
        }
    }
}
