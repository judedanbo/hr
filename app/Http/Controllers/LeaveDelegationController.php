<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreApprovalDelegationRequest;
use App\Http\Requests\UpdateApprovalDelegationRequest;
use App\Models\ApprovalDelegation;
use App\Models\InstitutionPerson;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class LeaveDelegationController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('LeaveDelegation/Index', [
            'delegations' => ApprovalDelegation::query()
                ->with(['delegator.person', 'delegate.person'])
                ->latest('id')
                ->paginate()
                ->withQueryString()
                ->through(fn (ApprovalDelegation $delegation): array => [
                    'id' => $delegation->id,
                    'delegator_id' => $delegation->delegator_id,
                    'delegate_id' => $delegation->delegate_id,
                    'delegator' => $delegation->delegator?->person?->full_name,
                    'delegate' => $delegation->delegate?->person?->full_name,
                    'start_date' => $delegation->start_date?->format('Y-m-d'),
                    'end_date' => $delegation->end_date?->format('Y-m-d'),
                    'is_active' => $delegation->isActive(),
                    'reason' => $delegation->reason,
                ]),
            'staffOptions' => $this->staffOptions(),
        ]);
    }

    public function store(StoreApprovalDelegationRequest $request): RedirectResponse
    {
        ApprovalDelegation::create($request->validated());

        return redirect()->route('leave-delegation.index')->with('success', 'Delegation created.');
    }

    public function update(UpdateApprovalDelegationRequest $request, ApprovalDelegation $leaveDelegation): RedirectResponse
    {
        $leaveDelegation->update($request->validated());

        return redirect()->route('leave-delegation.index')->with('success', 'Delegation updated.');
    }

    public function delete(ApprovalDelegation $leaveDelegation): RedirectResponse
    {
        $leaveDelegation->delete();

        return redirect()->back()->with('success', 'Delegation removed.');
    }

    /**
     * @return array<int, array{value: int, label: string}>
     */
    private function staffOptions(): array
    {
        return InstitutionPerson::query()
            ->active()
            ->with('person')
            ->get()
            ->map(fn (InstitutionPerson $staff): array => [
                'value' => $staff->id,
                'label' => trim(($staff->person?->full_name ?? 'Staff') . ' — ' . $staff->staff_number),
            ])
            ->values()
            ->all();
    }
}
