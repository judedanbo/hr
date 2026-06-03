<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreLeavePlanningWindowRequest;
use App\Http\Requests\UpdateLeavePlanningWindowRequest;
use App\Models\LeavePlanningWindow;
use App\Models\LeaveYear;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class LeavePlanningWindowController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('LeavePlanningWindow/Index', [
            'windows' => LeavePlanningWindow::query()
                ->with('leaveYear')
                ->join('leave_years', 'leave_years.id', '=', 'leave_planning_windows.leave_year_id')
                ->orderByDesc('leave_years.year')
                ->select('leave_planning_windows.*')
                ->paginate()
                ->withQueryString()
                ->through(fn (LeavePlanningWindow $window): array => [
                    'id' => $window->id,
                    'leave_year_id' => $window->leave_year_id,
                    'year' => $window->leaveYear?->year,
                    'opens_at' => $window->opens_at?->format('Y-m-d H:i'),
                    'closes_at' => $window->closes_at?->format('Y-m-d H:i'),
                    'is_open' => $window->isOpen(),
                    'allow_after_close' => $window->allow_after_close,
                    'require_full_plan' => $window->require_full_plan,
                    'instructions' => $window->instructions,
                ]),
            'leaveYears' => LeaveYear::query()->orderByDesc('year')
                ->get()
                ->map(fn (LeaveYear $year): array => ['value' => $year->id, 'label' => (string) $year->year]),
        ]);
    }

    public function store(StoreLeavePlanningWindowRequest $request): RedirectResponse
    {
        LeavePlanningWindow::create($request->validated());

        return redirect()->route('leave-planning-window.index')->with('success', 'Planning window created.');
    }

    public function update(UpdateLeavePlanningWindowRequest $request, LeavePlanningWindow $leavePlanningWindow): RedirectResponse
    {
        $leavePlanningWindow->update($request->validated());

        return redirect()->route('leave-planning-window.index')->with('success', 'Planning window updated.');
    }

    public function delete(LeavePlanningWindow $leavePlanningWindow): RedirectResponse
    {
        $leavePlanningWindow->delete();

        return redirect()->back()->with('success', 'Planning window deleted.');
    }
}
