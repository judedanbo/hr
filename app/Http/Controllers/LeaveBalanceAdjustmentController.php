<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreLeaveBalanceAdjustmentRequest;
use App\Models\InstitutionPerson;
use App\Models\LeaveBalanceAdjustment;
use App\Models\LeaveType;
use App\Models\LeaveYear;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class LeaveBalanceAdjustmentController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('LeaveBalanceAdjustment/Index', [
            'adjustments' => LeaveBalanceAdjustment::query()
                ->with(['staff.person', 'leaveType', 'leaveYear', 'adjustedBy'])
                ->latest('id')
                ->paginate()
                ->withQueryString()
                ->through(fn (LeaveBalanceAdjustment $adjustment): array => [
                    'id' => $adjustment->id,
                    'staff' => $adjustment->staff?->person?->full_name,
                    'leave_type' => $adjustment->leaveType?->name,
                    'year' => $adjustment->leaveYear?->year,
                    'days' => $adjustment->days,
                    'reason' => $adjustment->reason,
                    'by' => $adjustment->adjustedBy?->name,
                ]),
            'staffOptions' => InstitutionPerson::query()->active()->with('person')->get()
                ->map(fn (InstitutionPerson $s): array => [
                    'value' => $s->id,
                    'label' => trim(($s->person?->full_name ?? 'Staff') . ' — ' . $s->staff_number),
                ])->values(),
            'leaveTypes' => LeaveType::query()->where('is_active', true)->orderBy('name')->get()
                ->map(fn (LeaveType $t): array => ['value' => $t->id, 'label' => $t->name]),
            'leaveYears' => LeaveYear::query()->orderByDesc('year')->get()
                ->map(fn (LeaveYear $y): array => ['value' => $y->id, 'label' => (string) $y->year]),
        ]);
    }

    public function store(StoreLeaveBalanceAdjustmentRequest $request): RedirectResponse
    {
        LeaveBalanceAdjustment::create([
            ...$request->validated(),
            'adjusted_by' => $request->user()->id,
        ]);

        return redirect()->route('leave-balance-adjustment.index')->with('success', 'Balance adjustment recorded.');
    }

    public function delete(LeaveBalanceAdjustment $leaveBalanceAdjustment): RedirectResponse
    {
        $leaveBalanceAdjustment->delete();

        return redirect()->back()->with('success', 'Adjustment removed.');
    }
}
