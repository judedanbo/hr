<?php

namespace App\Http\Controllers;

use App\Enums\LeavePlanStatusEnum;
use App\Models\LeavePlan;
use App\Models\LeavePlanItem;
use Inertia\Inertia;
use Inertia\Response;

class LeavePlanAdminController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('LeavePlan/All', [
            'plans' => LeavePlan::query()
                ->where('status', LeavePlanStatusEnum::Submitted)
                ->with(['staff.person', 'leaveYear'])
                ->withCount('items')
                ->when(request()->search, function ($query, $search): void {
                    $query->whereHas('staff.person', function ($query) use ($search): void {
                        $query->where('first_name', 'like', '%' . $search . '%')
                            ->orWhere('surname', 'like', '%' . $search . '%');
                    });
                })
                ->latest('submitted_at')
                ->paginate()
                ->withQueryString()
                ->through(fn (LeavePlan $plan): array => [
                    'id' => $plan->id,
                    'staff' => $plan->staff?->person?->full_name,
                    'year' => $plan->leaveYear?->year,
                    'items_count' => $plan->items_count,
                    'submitted_at' => $plan->submitted_at?->format('Y-m-d H:i'),
                ]),
            'filters' => request()->only('search'),
        ]);
    }

    public function show(LeavePlan $plan): Response
    {
        $plan->load(['staff.person', 'leaveYear', 'items.leaveType']);

        return Inertia::render('LeavePlan/Show', [
            'plan' => [
                'id' => $plan->id,
                'staff' => $plan->staff?->person?->full_name,
                'year' => $plan->leaveYear?->year,
                'status' => $plan->status->value,
                'submitted_at' => $plan->submitted_at?->format('Y-m-d H:i'),
                'items' => $plan->items->map(fn (LeavePlanItem $item): array => [
                    'id' => $item->id,
                    'leave_type' => $item->leaveType?->name,
                    'start_date' => $item->start_date?->format('Y-m-d'),
                    'end_date' => $item->end_date?->format('Y-m-d'),
                    'proposed_days' => $item->proposed_days,
                    'note' => $item->note,
                ])->values(),
            ],
        ]);
    }
}
