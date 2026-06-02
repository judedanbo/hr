<?php

namespace App\Http\Controllers;

use App\Enums\GenderEnum;
use App\Http\Requests\StoreLeaveTypeRequest;
use App\Http\Requests\UpdateLeaveTypeRequest;
use App\Models\LeaveType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class LeaveTypeController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('LeaveType/Index', [
            'leaveTypes' => LeaveType::query()
                ->when(request()->search, function ($query, $search): void {
                    $query->where(function ($query) use ($search): void {
                        $query->where('name', 'like', '%' . $search . '%')
                            ->orWhere('code', 'like', '%' . $search . '%');
                    });
                })
                ->orderBy('name')
                ->paginate()
                ->withQueryString()
                ->through(fn (LeaveType $leaveType): array => [
                    'id' => $leaveType->id,
                    'name' => $leaveType->name,
                    'code' => $leaveType->code,
                    'requires_evidence' => $leaveType->requires_evidence,
                    'gender_restriction' => $leaveType->gender_restriction?->value,
                    'gender_restriction_label' => $leaveType->gender_restriction?->label(),
                    'counts_weekends' => $leaveType->counts_weekends,
                    'counts_holidays' => $leaveType->counts_holidays,
                    'min_notice_days' => $leaveType->min_notice_days,
                    'max_consecutive_days' => $leaveType->max_consecutive_days,
                    'max_concurrent_per_unit' => $leaveType->max_concurrent_per_unit,
                    'color' => $leaveType->color,
                    'is_active' => $leaveType->is_active,
                ]),
            'genders' => collect([GenderEnum::MALE, GenderEnum::FEMALE])
                ->map(fn (GenderEnum $gender): array => [
                    'value' => $gender->value,
                    'label' => $gender->label(),
                ]),
            'filters' => request()->only('search'),
        ]);
    }

    public function store(StoreLeaveTypeRequest $request): RedirectResponse
    {
        LeaveType::create($request->validated());

        return redirect()->route('leave-type.index')->with('success', 'Leave type created.');
    }

    public function update(UpdateLeaveTypeRequest $request, LeaveType $leaveType): RedirectResponse
    {
        $leaveType->update($request->validated());

        return redirect()->route('leave-type.index')->with('success', 'Leave type updated.');
    }

    public function delete(LeaveType $leaveType): RedirectResponse
    {
        $leaveType->delete();

        return redirect()->back()->with('success', 'Leave type deleted.');
    }

    public function list(): Collection
    {
        return LeaveType::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get()
            ->map(fn (LeaveType $leaveType): array => [
                'value' => $leaveType->id,
                'label' => $leaveType->name,
            ]);
    }
}
