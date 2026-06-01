<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreLeaveEntitlementRequest;
use App\Http\Requests\UpdateLeaveEntitlementRequest;
use App\Models\JobCategory;
use App\Models\LeaveEntitlement;
use App\Models\LeaveType;
use App\Models\LeaveYear;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class LeaveEntitlementController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('LeaveEntitlement/Index', [
            'entitlements' => LeaveEntitlement::query()
                ->with(['leaveYear', 'leaveType', 'jobCategory'])
                ->join('leave_years', 'leave_years.id', '=', 'leave_entitlements.leave_year_id')
                ->orderByDesc('leave_years.year')
                ->select('leave_entitlements.*')
                ->paginate()
                ->withQueryString()
                ->through(fn (LeaveEntitlement $entitlement): array => [
                    'id' => $entitlement->id,
                    'leave_year_id' => $entitlement->leave_year_id,
                    'leave_type_id' => $entitlement->leave_type_id,
                    'job_category_id' => $entitlement->job_category_id,
                    'year' => $entitlement->leaveYear?->year,
                    'leave_type' => $entitlement->leaveType?->name,
                    'job_category' => $entitlement->jobCategory?->name ?? 'All categories',
                    'days_allowed' => $entitlement->days_allowed,
                    'min_service_months' => $entitlement->min_service_months,
                    'notes' => $entitlement->notes,
                ]),
            'leaveYears' => LeaveYear::query()->orderByDesc('year')
                ->get()
                ->map(fn (LeaveYear $year): array => ['value' => $year->id, 'label' => (string) $year->year]),
            'leaveTypes' => LeaveType::query()->orderBy('name')
                ->get()
                ->map(fn (LeaveType $type): array => ['value' => $type->id, 'label' => $type->name]),
            'jobCategories' => JobCategory::query()->orderBy('name')
                ->get()
                ->map(fn (JobCategory $category): array => ['value' => $category->id, 'label' => $category->name]),
        ]);
    }

    public function store(StoreLeaveEntitlementRequest $request): RedirectResponse
    {
        LeaveEntitlement::create($request->validated());

        return redirect()->route('leave-entitlement.index')->with('success', 'Entitlement created.');
    }

    public function update(UpdateLeaveEntitlementRequest $request, LeaveEntitlement $leaveEntitlement): RedirectResponse
    {
        $leaveEntitlement->update($request->validated());

        return redirect()->route('leave-entitlement.index')->with('success', 'Entitlement updated.');
    }

    public function delete(LeaveEntitlement $leaveEntitlement): RedirectResponse
    {
        $leaveEntitlement->delete();

        return redirect()->back()->with('success', 'Entitlement deleted.');
    }
}
