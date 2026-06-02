<?php

namespace App\Http\Controllers;

use App\Enums\LeaveRequestStatusEnum;
use App\Models\LeaveRequest;
use App\Models\Unit;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LeaveCalendarController extends Controller
{
    public function index(Request $request): Response
    {
        $month = $this->resolveMonth($request->input('month'));
        $start = $month->copy()->startOfMonth();
        $end = $month->copy()->endOfMonth();
        $unitId = $request->integer('unit_id') ?: null;

        $unitStaffIds = $unitId
            ? Unit::query()->whereKey($unitId)->first()?->staff()->pluck('institution_person.id')
            : null;

        $entries = LeaveRequest::query()
            ->where('status', LeaveRequestStatusEnum::Approved)
            ->whereDate('start_date', '<=', $end->toDateString())
            ->whereDate('end_date', '>=', $start->toDateString())
            ->when($unitStaffIds, fn ($query) => $query->whereIn('staff_id', $unitStaffIds))
            ->with(['staff.person', 'leaveType'])
            ->get()
            ->map(fn (LeaveRequest $leaveRequest): array => [
                'id' => $leaveRequest->id,
                'staff' => $leaveRequest->staff?->person?->full_name,
                'leave_type' => $leaveRequest->leaveType?->name,
                'color' => $leaveRequest->leaveType?->color,
                'start_date' => $leaveRequest->start_date?->format('Y-m-d'),
                'end_date' => $leaveRequest->end_date?->format('Y-m-d'),
            ])
            ->values();

        $today = now()->toDateString();
        $onLeaveToday = $entries
            ->filter(fn (array $entry): bool => $entry['start_date'] <= $today && $entry['end_date'] >= $today)
            ->values();

        return Inertia::render('LeaveCalendar/Index', [
            'month' => $month->format('Y-m'),
            'entries' => $entries,
            'onLeaveToday' => $onLeaveToday,
            'unitOptions' => Unit::query()->orderBy('name')
                ->get()
                ->map(fn (Unit $unit): array => ['value' => $unit->id, 'label' => $unit->name]),
            'filters' => ['unit_id' => $unitId],
        ]);
    }

    private function resolveMonth(?string $month): Carbon
    {
        try {
            return $month ? Carbon::createFromFormat('Y-m', $month)->startOfMonth() : now()->startOfMonth();
        } catch (\Throwable) {
            return now()->startOfMonth();
        }
    }
}
