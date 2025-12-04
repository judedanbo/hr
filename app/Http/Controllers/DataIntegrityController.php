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
            ->filter(fn ($staff) => $staff->ranks->count() > 1)
            ->count();

        // Count staff without units
        $staffWithoutUnitsCount = InstitutionPerson::query()
            ->active()
            ->whereDoesntHave('units', function ($query) {
                $query->whereNull('staff_unit.end_date');
            })
            ->count();

        // Count staff without ranks
        $staffWithoutRanksCount = InstitutionPerson::query()
            ->active()
            ->whereDoesntHave('ranks', function ($query) {
                $query->whereNull('job_staff.end_date');
            })
            ->count();

        // Count staff with invalid date ranges (end_date before start_date)
        $invalidDateRangesCount = InstitutionPerson::query()
            ->active()
            ->where(function ($query) {
                // Check ranks with invalid dates
                $query->whereHas('ranks', function ($query) {
                    $query->whereNotNull('job_staff.end_date')
                        ->whereRaw('job_staff.end_date < job_staff.start_date');
                });
                // Check units with invalid dates
                $query->orWhereHas('units', function ($query) {
                    $query->whereNotNull('staff_unit.end_date')
                        ->whereRaw('staff_unit.end_date < staff_unit.start_date');
                });
            })
            ->count();

        // Count separated staff still marked as active
        $separatedButActiveCount = InstitutionPerson::query()
            ->active()
            ->whereHas('statuses', function ($query) {
                $query->whereIn('status', [
                    \App\Enums\EmployeeStatusEnum::Left->value,
                    \App\Enums\EmployeeStatusEnum::Termination->value,
                    \App\Enums\EmployeeStatusEnum::Resignation->value,
                    \App\Enums\EmployeeStatusEnum::Retired->value,
                    \App\Enums\EmployeeStatusEnum::Dismissed->value,
                    \App\Enums\EmployeeStatusEnum::Deceased->value,
                    \App\Enums\EmployeeStatusEnum::Voluntary->value,
                ])->where(function ($query) {
                    $query->whereNull('end_date')
                        ->orWhere('end_date', '>', now());
                });
            })
            ->count();

        // Count staff without profile pictures
        $staffWithoutPicturesCount = InstitutionPerson::query()
            ->active()
            ->whereHas('person', function ($query) {
                $query->whereNull('image')
                    ->orWhere('image', '');
            })
            ->count();

        // Count active staff with expired status end_date (only checks latest status)
        $expiredActiveStatusCount = InstitutionPerson::query()
            ->whereHas('statuses', function ($query) {
                $query->where('status', \App\Enums\EmployeeStatusEnum::Active->value)
                    ->whereNotNull('end_date')
                    ->where('end_date', '<=', now())
                    ->whereRaw('status.id = (SELECT s.id FROM status s WHERE s.staff_id = status.staff_id AND s.deleted_at IS NULL ORDER BY s.start_date DESC LIMIT 1)');
            })
            ->count();

        // Count staff with multiple active unit assignments
        $multipleUnitsCount = InstitutionPerson::query()
            ->active()
            ->whereHas('units', function ($query) {
                $query->whereNull('staff_unit.end_date');
            })
            ->with(['units' => function ($query) {
                $query->whereNull('staff_unit.end_date');
            }])
            ->get()
            ->filter(fn ($staff) => $staff->units->count() > 1)
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
                [
                    'id' => 'staff-without-units',
                    'title' => 'Staff without Units',
                    'description' => 'Active staff members who have no current unit assignment',
                    'count' => $staffWithoutUnitsCount,
                    'severity' => $staffWithoutUnitsCount > 0 ? 'error' : 'success',
                    // 'route' => route('data-integrity.staff-without-units'),
                ],
                [
                    'id' => 'staff-without-ranks',
                    'title' => 'Staff without Ranks',
                    'description' => 'Active staff members who have no current rank assignment',
                    'count' => $staffWithoutRanksCount,
                    'severity' => $staffWithoutRanksCount > 0 ? 'error' : 'success',
                    'route' => route('data-integrity.staff-without-ranks'),
                ],
                [
                    'id' => 'invalid-date-ranges',
                    'title' => 'Invalid Date Ranges',
                    'description' => 'Staff with end dates that come before start dates in rank or unit assignments',
                    'count' => $invalidDateRangesCount,
                    'severity' => $invalidDateRangesCount > 0 ? 'error' : 'success',
                    'route' => route('data-integrity.invalid-date-ranges'),
                ],
                [
                    'id' => 'separated-but-active',
                    'title' => 'Separated Staff Still Active',
                    'description' => 'Staff marked as active but have a separation date in the past',
                    'count' => $separatedButActiveCount,
                    'severity' => $separatedButActiveCount > 0 ? 'warning' : 'success',
                    'route' => route('data-integrity.separated-but-active'),
                ],
                [
                    'id' => 'staff-without-pictures',
                    'title' => 'Staff without Profile Pictures',
                    'description' => 'Active staff members who do not have a profile picture uploaded',
                    'count' => $staffWithoutPicturesCount,
                    'severity' => $staffWithoutPicturesCount > 0 ? 'warning' : 'success',
                    'route' => route('data-integrity.staff-without-pictures'),
                ],
                [
                    'id' => 'expired-active-status',
                    'title' => 'Active Staff with Expired Status',
                    'description' => 'Staff with active status but the status end date is today or in the past',
                    'count' => $expiredActiveStatusCount,
                    'severity' => $expiredActiveStatusCount > 0 ? 'warning' : 'success',
                    'route' => route('data-integrity.expired-active-status'),
                ],
                [
                    'id' => 'multiple-unit-assignments',
                    'title' => 'Staff with Multiple Unit Assignments',
                    'description' => 'Active staff members assigned to more than one unit simultaneously',
                    'count' => $multipleUnitsCount,
                    'severity' => $multipleUnitsCount > 0 ? 'warning' : 'success',
                    'route' => route('data-integrity.multiple-unit-assignments'),
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
            ->filter(fn ($staff) => $staff->ranks->count() > 1)
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
                ->filter(fn ($staff) => $staff->ranks->count() > 1);

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

    public function staffWithoutUnits()
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
                ->log('Attempted to view staff without units');

            return redirect()->route('dashboard')->with('error', 'You do not have permission to view data integrity checks.');
        }

        $staffWithoutUnits = InstitutionPerson::query()
            ->active()
            ->whereDoesntHave('units', function ($query) {
                $query->whereNull('staff_unit.end_date');
            })
            ->with('person')
            ->get()
            ->map(function ($staff) {
                return [
                    'id' => $staff->id,
                    'staff_number' => $staff->staff_number,
                    'file_number' => $staff->file_number,
                    'name' => $staff->person->full_name,
                    'hire_date' => $staff->hire_date?->format('Y-m-d'),
                    'hire_date_formatted' => $staff->hire_date?->format('d M Y'),
                ];
            });

        activity()
            ->causedBy(auth()->user())
            ->event('view')
            ->withProperties([
                'result' => 'success',
                'count' => $staffWithoutUnits->count(),
                'user_ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ])
            ->log('Viewed staff without units');

        return Inertia::render('DataIntegrity/StaffWithoutUnits', [
            'staff' => $staffWithoutUnits,
        ]);
    }

    public function staffWithoutRanks()
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
                ->log('Attempted to view staff without ranks');

            return redirect()->route('dashboard')->with('error', 'You do not have permission to view data integrity checks.');
        }

        $staffWithoutRanks = InstitutionPerson::query()
            ->active()
            ->whereDoesntHave('ranks', function ($query) {
                $query->whereNull('job_staff.end_date');
            })
            ->with('person')
            ->get()
            ->map(function ($staff) {
                return [
                    'id' => $staff->id,
                    'staff_number' => $staff->staff_number,
                    'file_number' => $staff->file_number,
                    'name' => $staff->person->full_name,
                    'hire_date' => $staff->hire_date?->format('Y-m-d'),
                    'hire_date_formatted' => $staff->hire_date?->format('d M Y'),
                ];
            });

        activity()
            ->causedBy(auth()->user())
            ->event('view')
            ->withProperties([
                'result' => 'success',
                'count' => $staffWithoutRanks->count(),
                'user_ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ])
            ->log('Viewed staff without ranks');

        return Inertia::render('DataIntegrity/StaffWithoutRanks', [
            'staff' => $staffWithoutRanks,
        ]);
    }

    public function invalidDateRanges()
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
                ->log('Attempted to view invalid date ranges');

            return redirect()->route('dashboard')->with('error', 'You do not have permission to view data integrity checks.');
        }

        $staffWithInvalidDates = InstitutionPerson::query()
            ->active()
            ->with(['person', 'ranks' => function ($query) {
                $query->whereNotNull('job_staff.end_date')
                    ->whereRaw('job_staff.end_date < job_staff.start_date');
            }, 'units' => function ($query) {
                $query->whereNotNull('staff_unit.end_date')
                    ->whereRaw('staff_unit.end_date < staff_unit.start_date');
            }])
            ->where(function ($query) {
                $query->whereHas('ranks', function ($query) {
                    $query->whereNotNull('job_staff.end_date')
                        ->whereRaw('job_staff.end_date < job_staff.start_date');
                });
                $query->orWhereHas('units', function ($query) {
                    $query->whereNotNull('staff_unit.end_date')
                        ->whereRaw('staff_unit.end_date < staff_unit.start_date');
                });
            })
            ->get()
            ->map(function ($staff) {
                $invalidRanks = $staff->ranks->filter(function ($rank) {
                    return $rank->pivot->end_date && $rank->pivot->end_date < $rank->pivot->start_date;
                })->map(function ($rank) {
                    return [
                        'type' => 'rank',
                        'pivot_id' => $rank->pivot->id,
                        'name' => $rank->name,
                        'start_date' => $rank->pivot->start_date->format('Y-m-d'),
                        'end_date' => $rank->pivot->end_date->format('Y-m-d'),
                        'start_date_formatted' => $rank->pivot->start_date->format('d M Y'),
                        'end_date_formatted' => $rank->pivot->end_date->format('d M Y'),
                    ];
                });

                $invalidUnits = $staff->units->filter(function ($unit) {
                    return $unit->pivot->end_date && $unit->pivot->end_date < $unit->pivot->start_date;
                })->map(function ($unit) {
                    return [
                        'type' => 'unit',
                        'pivot_id' => $unit->pivot->id,
                        'name' => $unit->name,
                        'start_date' => $unit->pivot->start_date->format('Y-m-d'),
                        'end_date' => $unit->pivot->end_date->format('Y-m-d'),
                        'start_date_formatted' => $unit->pivot->start_date->format('d M Y'),
                        'end_date_formatted' => $unit->pivot->end_date->format('d M Y'),
                    ];
                });

                return [
                    'id' => $staff->id,
                    'staff_number' => $staff->staff_number,
                    'file_number' => $staff->file_number,
                    'name' => $staff->person->full_name,
                    'invalid_count' => $invalidRanks->count() + $invalidUnits->count(),
                    'invalid_assignments' => $invalidRanks->concat($invalidUnits)->values(),
                ];
            })
            ->filter(fn ($staff) => $staff['invalid_count'] > 0)
            ->values();

        activity()
            ->causedBy(auth()->user())
            ->event('view')
            ->withProperties([
                'result' => 'success',
                'count' => $staffWithInvalidDates->count(),
                'user_ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ])
            ->log('Viewed invalid date ranges');

        return Inertia::render('DataIntegrity/InvalidDateRanges', [
            'staff' => $staffWithInvalidDates,
        ]);
    }

    public function fixInvalidDateRanges(InstitutionPerson $staff)
    {
        if (Gate::denies('data-integrity.fix')) {
            return redirect()->back()->with('error', 'You do not have permission to fix data integrity issues.');
        }

        DB::beginTransaction();
        try {
            $fixed = 0;

            // Fix invalid rank dates - set end_date to null
            $invalidRanks = DB::table('job_staff')
                ->where('staff_id', $staff->id)
                ->whereNotNull('end_date')
                ->whereRaw('end_date < start_date')
                ->update(['end_date' => null]);

            $fixed += $invalidRanks;

            // Fix invalid unit dates - set end_date to null
            $invalidUnits = DB::table('staff_unit')
                ->where('staff_id', $staff->id)
                ->whereNotNull('end_date')
                ->whereRaw('end_date < start_date')
                ->update(['end_date' => null]);

            $fixed += $invalidUnits;

            activity()
                ->causedBy(auth()->user())
                ->performedOn($staff)
                ->event('fix')
                ->withProperties([
                    'result' => 'success',
                    'fixed_count' => $fixed,
                    'user_ip' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                ])
                ->log('Fixed invalid date ranges for staff');

            DB::commit();

            return redirect()->back()->with('success', "Fixed {$fixed} invalid date range(s) for {$staff->person->full_name} by setting end dates to null.");
        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->back()->with('error', 'An error occurred while fixing the date ranges.');
        }
    }

    public function bulkFixInvalidDateRanges()
    {
        if (Gate::denies('data-integrity.fix')) {
            return redirect()->back()->with('error', 'You do not have permission to fix data integrity issues.');
        }

        DB::beginTransaction();
        try {
            // Fix all invalid rank dates
            $fixedRanks = DB::table('job_staff')
                ->whereNotNull('end_date')
                ->whereRaw('end_date < start_date')
                ->update(['end_date' => null]);

            // Fix all invalid unit dates
            $fixedUnits = DB::table('staff_unit')
                ->whereNotNull('end_date')
                ->whereRaw('end_date < start_date')
                ->update(['end_date' => null]);

            $totalFixed = $fixedRanks + $fixedUnits;

            activity()
                ->causedBy(auth()->user())
                ->event('bulk-fix')
                ->withProperties([
                    'result' => 'success',
                    'ranks_fixed' => $fixedRanks,
                    'units_fixed' => $fixedUnits,
                    'total_fixed' => $totalFixed,
                    'user_ip' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                ])
                ->log('Bulk fixed invalid date ranges');

            DB::commit();

            return redirect()->back()->with('success', "Successfully fixed {$totalFixed} invalid date range(s) ({$fixedRanks} ranks, {$fixedUnits} units) by setting end dates to null.");
        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->back()->with('error', 'An error occurred while fixing the date ranges.');
        }
    }

    public function separatedButActive()
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
                ->log('Attempted to view separated but active staff');

            return redirect()->route('dashboard')->with('error', 'You do not have permission to view data integrity checks.');
        }

        $separatedButActive = InstitutionPerson::query()
            ->active()
            ->whereHas('statuses', function ($query) {
                $query->whereIn('status', [
                    \App\Enums\EmployeeStatusEnum::Left->value,
                    \App\Enums\EmployeeStatusEnum::Termination->value,
                    \App\Enums\EmployeeStatusEnum::Resignation->value,
                    \App\Enums\EmployeeStatusEnum::Retired->value,
                    \App\Enums\EmployeeStatusEnum::Dismissed->value,
                    \App\Enums\EmployeeStatusEnum::Deceased->value,
                    \App\Enums\EmployeeStatusEnum::Voluntary->value,
                ])->where(function ($query) {
                    $query->whereNull('end_date')
                        ->orWhere('end_date', '>', now());
                });
            })
            ->with(['person', 'statuses' => function ($query) {
                $query->whereIn('status', [
                    \App\Enums\EmployeeStatusEnum::Left->value,
                    \App\Enums\EmployeeStatusEnum::Termination->value,
                    \App\Enums\EmployeeStatusEnum::Resignation->value,
                    \App\Enums\EmployeeStatusEnum::Retired->value,
                    \App\Enums\EmployeeStatusEnum::Dismissed->value,
                    \App\Enums\EmployeeStatusEnum::Deceased->value,
                    \App\Enums\EmployeeStatusEnum::Voluntary->value,
                ])->where(function ($query) {
                    $query->whereNull('end_date')
                        ->orWhere('end_date', '>', now());
                });
            }])
            ->get()
            ->map(function ($staff) {
                return [
                    'id' => $staff->id,
                    'staff_number' => $staff->staff_number,
                    'file_number' => $staff->file_number,
                    'name' => $staff->person->full_name,
                    'separation_status' => $staff->statuses->first()?->status,
                    'separation_date' => $staff->statuses->first()?->start_date?->format('Y-m-d'),
                    'separation_date_formatted' => $staff->statuses->first()?->start_date?->format('d M Y'),
                ];
            });

        activity()
            ->causedBy(auth()->user())
            ->event('view')
            ->withProperties([
                'result' => 'success',
                'count' => $separatedButActive->count(),
                'user_ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ])
            ->log('Viewed separated but active staff');

        return Inertia::render('DataIntegrity/SeparatedButActive', [
            'staff' => $separatedButActive,
        ]);
    }

    public function staffWithoutPictures()
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
                ->log('Attempted to view staff without pictures');

            return redirect()->route('data-integrity.index')->with('error', 'You do not have permission to view data integrity checks.');
        }

        $staffWithoutPictures = InstitutionPerson::query()
            ->active()
            ->whereHas('person', function ($query) {
                $query->whereNull('image')
                    ->orWhere('image', '');
            })
            ->with([
                'person',
                'currentUnit.unit.parent',
            ])
            ->currentUnit()
            ->get()
            ->map(function ($staff) {
                $unit = $staff->currentUnit?->unit;
                $parent = $unit?->parent;

                // Determine department (top-level unit with no parent or type = DEP)
                $department = null;
                if ($parent) {
                    // If unit has a parent, check if parent is a department
                    if ($parent->type === \App\Enums\UnitType::DEPARTMENT->value || ! $parent->unit_id) {
                        $department = $parent;
                    }
                } elseif ($unit && ($unit->type === \App\Enums\UnitType::DEPARTMENT->value || ! $unit->unit_id)) {
                    // Staff is directly in a department
                    $department = $unit;
                    $unit = null; // Clear unit since they're assigned to department directly
                }

                return [
                    'id' => $staff->id,
                    'staff_number' => $staff->staff_number,
                    'file_number' => $staff->file_number,
                    'name' => $staff->person->full_name,
                    'hire_date' => $staff->hire_date?->format('Y-m-d'),
                    'hire_date_formatted' => $staff->hire_date?->format('d M Y'),
                    'unit' => $unit ? [
                        'id' => $unit->id,
                        'name' => $unit->name,
                        'type' => $unit->type,
                    ] : null,
                    'department' => $department ? [
                        'id' => $department->id,
                        'name' => $department->name,
                        'type' => $department->type,
                    ] : null,
                ];
            })
            ->groupBy(function ($staff) {
                return $staff['department']['name'] ?? 'No Department';
            })
            ->map(function ($departmentStaff) {
                return $departmentStaff->groupBy(function ($staff) {
                    return $staff['unit']['name'] ?? 'No Unit';
                });
            });

        // Calculate total count from grouped data
        $totalCount = $staffWithoutPictures->reduce(function ($carry, $departmentGroup) {
            return $carry + $departmentGroup->reduce(function ($unitCarry, $unitGroup) {
                return $unitCarry + $unitGroup->count();
            }, 0);
        }, 0);

        activity()
            ->causedBy(auth()->user())
            ->event('view')
            ->withProperties([
                'result' => 'success',
                'count' => $totalCount,
                'user_ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ])
            ->log('Viewed staff without pictures');

        return Inertia::render('DataIntegrity/StaffWithoutPictures', [
            'staff' => $staffWithoutPictures,
        ]);
    }

    public function expiredActiveStatus()
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
                ->log('Attempted to view expired active status');

            return redirect()->route('data-integrity.index')->with('error', 'You do not have permission to view data integrity checks.');
        }

        $expiredActiveStatus = InstitutionPerson::query()
            ->whereHas('statuses', function ($query) {
                $query->where('status', \App\Enums\EmployeeStatusEnum::Active->value)
                    ->whereNotNull('end_date')
                    ->where('end_date', '<=', now())
                    ->whereRaw('status.id = (SELECT s.id FROM status s WHERE s.staff_id = status.staff_id AND s.deleted_at IS NULL ORDER BY s.start_date DESC LIMIT 1)');
            })
            ->with([
                'person',
                'currentUnit.unit.parent',
                'statuses' => function ($query) {
                    $query->latest('start_date')->limit(1);
                },
            ])
            ->currentUnit()
            ->currentRank()
            ->get()
            ->map(function ($staff) {
                $unit = $staff->currentUnit?->unit;
                $parent = $unit?->parent;

                // Determine department (top-level unit with no parent or type = DEP)
                $department = null;
                if ($parent) {
                    if ($parent->type === \App\Enums\UnitType::DEPARTMENT->value || ! $parent->unit_id) {
                        $department = $parent;
                    }
                } elseif ($unit && ($unit->type === \App\Enums\UnitType::DEPARTMENT->value || ! $unit->unit_id)) {
                    $department = $unit;
                    $unit = null;
                }

                return [
                    'id' => $staff->id,
                    'staff_number' => $staff->staff_number,
                    'file_number' => $staff->file_number,
                    'name' => $staff->person->full_name,
                    'hire_date' => $staff->hire_date?->format('Y-m-d'),
                    'hire_date_formatted' => $staff->hire_date?->format('d M Y'),
                    'status_end_date' => $staff->statuses->first()?->end_date?->format('Y-m-d'),
                    'status_end_date_formatted' => $staff->statuses->first()?->end_date?->format('d M Y'),
                    'current_rank' => $staff->currentRank?->job?->name,
                    'unit' => $unit ? [
                        'id' => $unit->id,
                        'name' => $unit->name,
                        'type' => $unit->type,
                    ] : null,
                    'department' => $department ? [
                        'id' => $department->id,
                        'name' => $department->name,
                        'type' => $department->type,
                    ] : null,
                ];
            })
            ->groupBy(function ($staff) {
                return $staff['department']['name'] ?? 'No Department';
            })
            ->map(function ($departmentStaff) {
                return $departmentStaff->groupBy(function ($staff) {
                    return $staff['unit']['name'] ?? 'No Unit';
                });
            });

        // Calculate total count from grouped data
        $totalCount = $expiredActiveStatus->reduce(function ($carry, $departmentGroup) {
            return $carry + $departmentGroup->reduce(function ($unitCarry, $unitGroup) {
                return $unitCarry + $unitGroup->count();
            }, 0);
        }, 0);

        activity()
            ->causedBy(auth()->user())
            ->event('view')
            ->withProperties([
                'result' => 'success',
                'count' => $totalCount,
                'user_ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ])
            ->log('Viewed expired active status');

        return Inertia::render('DataIntegrity/ExpiredActiveStatus', [
            'staff' => $expiredActiveStatus,
        ]);
    }

    public function multipleUnitAssignments()
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
                ->log('Attempted to view multiple unit assignments');

            return redirect()->route('data-integrity.index')->with('error', 'You do not have permission to view data integrity checks.');
        }

        $staffWithMultipleUnits = InstitutionPerson::query()
            ->active()
            ->whereHas('units', function ($query) {
                $query->whereNull('staff_unit.end_date');
            })
            ->with(['units' => function ($query) {
                $query->whereNull('staff_unit.end_date')->orderBy('staff_unit.start_date', 'desc');
            }, 'person'])
            ->get()
            ->filter(fn ($staff) => $staff->units->count() > 1)
            ->map(function ($staff) {
                return [
                    'id' => $staff->id,
                    'staff_number' => $staff->staff_number,
                    'file_number' => $staff->file_number,
                    'name' => $staff->person->full_name,
                    'active_units_count' => $staff->units->count(),
                    'units' => $staff->units->map(function ($unit) {
                        return [
                            'id' => $unit->id,
                            'pivot_id' => $unit->pivot->id,
                            'name' => $unit->name,
                            'type' => $unit->type,
                            'start_date' => $unit->pivot->start_date?->format('Y-m-d'),
                            'start_date_formatted' => $unit->pivot->start_date?->format('d M Y'),
                            'end_date' => $unit->pivot->end_date,
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
                'count' => $staffWithMultipleUnits->count(),
                'user_ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ])
            ->log('Viewed staff with multiple unit assignments');

        return Inertia::render('DataIntegrity/MultipleUnitAssignments', [
            'staff' => $staffWithMultipleUnits,
        ]);
    }
}
