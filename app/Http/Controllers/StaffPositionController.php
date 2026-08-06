<?php

namespace App\Http\Controllers;

use App\Contracts\Services\StaffPositionServiceInterface;
use App\DataTransferObjects\PositionAssignmentResult;
use App\Http\Requests\StoreStaffPositionRequest;
use App\Http\Requests\UpdateStaffPositionRequest;
use App\Models\InstitutionPerson;
use App\Models\PositionStaff;
use Illuminate\Http\RedirectResponse;

class StaffPositionController extends Controller
{
    public function __construct(
        protected StaffPositionServiceInterface $staffPositionService
    ) {}

    public function store(StoreStaffPositionRequest $request, InstitutionPerson $staff): RedirectResponse
    {
        $result = $this->staffPositionService->assign(
            $staff,
            (int) $request->validated('position_id'),
            $request->validated()
        );

        return $this->redirectWith($result, 'Position assigned successfully.');
    }

    public function update(UpdateStaffPositionRequest $request, InstitutionPerson $staff, PositionStaff $staffPosition): RedirectResponse
    {
        abort_unless($staffPosition->staff_id === $staff->id, 404);

        $result = $this->staffPositionService->update($staffPosition, $request->validated());

        return $this->redirectWith($result, 'Position updated successfully.');
    }

    public function destroy(InstitutionPerson $staff, PositionStaff $staffPosition): RedirectResponse
    {
        abort_unless($staffPosition->staff_id === $staff->id, 404);

        $this->staffPositionService->delete($staffPosition);

        return redirect()->back()->with('success', 'Position removed successfully.');
    }

    /**
     * Role grants can be skipped without failing the assignment, so a warning
     * rides alongside the success message rather than replacing it.
     */
    protected function redirectWith(PositionAssignmentResult $result, string $message): RedirectResponse
    {
        $redirect = redirect()->back()->with('success', $message);

        return $result->warning
            ? $redirect->with('warning', $result->warning)
            : $redirect;
    }
}
