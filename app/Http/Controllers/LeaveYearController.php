<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreLeaveYearRequest;
use App\Http\Requests\UpdateLeaveYearRequest;
use App\Models\LeaveYear;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;
use Inertia\Response;

class LeaveYearController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('LeaveYear/Index', [
            'leaveYears' => LeaveYear::query()
                ->withCount(['entitlements', 'holidays'])
                ->orderByDesc('year')
                ->paginate()
                ->withQueryString()
                ->through(fn (LeaveYear $leaveYear): array => [
                    'id' => $leaveYear->id,
                    'year' => $leaveYear->year,
                    'start_date' => $leaveYear->start_date?->format('Y-m-d'),
                    'end_date' => $leaveYear->end_date?->format('Y-m-d'),
                    'is_active' => $leaveYear->is_active,
                    'entitlements_count' => $leaveYear->entitlements_count,
                    'holidays_count' => $leaveYear->holidays_count,
                ]),
        ]);
    }

    public function store(StoreLeaveYearRequest $request): RedirectResponse
    {
        LeaveYear::create($request->validated());

        return redirect()->route('leave-year.index')->with('success', 'Leave year created.');
    }

    public function update(UpdateLeaveYearRequest $request, LeaveYear $leaveYear): RedirectResponse
    {
        $leaveYear->update($request->validated());

        return redirect()->route('leave-year.index')->with('success', 'Leave year updated.');
    }

    public function delete(LeaveYear $leaveYear): RedirectResponse
    {
        $leaveYear->delete();

        return redirect()->back()->with('success', 'Leave year deleted.');
    }

    public function list(): \Illuminate\Support\Collection
    {
        return LeaveYear::query()
            ->orderByDesc('year')
            ->get()
            ->map(fn (LeaveYear $leaveYear): array => [
                'value' => $leaveYear->id,
                'label' => (string) $leaveYear->year,
            ]);
    }

    public function cloneFromYear(LeaveYear $leaveYear): RedirectResponse
    {
        if (! Gate::allows('clone leave year')) {
            abort(403);
        }

        $data = Validator::make(request()->all(), [
            'source_leave_year_id' => ['required', 'integer', 'exists:leave_years,id', 'different:' . $leaveYear->id],
        ])->validate();

        $source = LeaveYear::query()
            ->with(['entitlements', 'holidays' => fn ($query) => $query->where('is_recurring', true)])
            ->findOrFail($data['source_leave_year_id']);

        DB::transaction(function () use ($source, $leaveYear): void {
            foreach ($source->entitlements as $entitlement) {
                $leaveYear->entitlements()->firstOrCreate(
                    [
                        'leave_type_id' => $entitlement->leave_type_id,
                        'job_category_id' => $entitlement->job_category_id,
                    ],
                    [
                        'days_allowed' => $entitlement->days_allowed,
                        'min_service_months' => $entitlement->min_service_months,
                        'notes' => $entitlement->notes,
                    ]
                );
            }

            foreach ($source->holidays as $holiday) {
                $targetDate = $holiday->date->copy()->year($leaveYear->year);

                $leaveYear->holidays()->firstOrCreate(
                    ['date' => $targetDate->toDateString()],
                    [
                        'name' => $holiday->name,
                        'is_recurring' => true,
                    ]
                );
            }
        });

        return redirect()->back()->with('success', 'Configuration cloned into ' . $leaveYear->year . '.');
    }
}
