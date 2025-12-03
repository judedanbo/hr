<?php

namespace App\Http\Controllers;

use App\Contracts\Services\SeparationServiceInterface;
use App\Http\Requests\StoreStaffStatusRequest;
use App\Http\Requests\UpdateStaffStatusRequest;
use App\Models\InstitutionPerson;
use App\Models\Status;
use Illuminate\Support\Facades\Gate;

class StaffStatusController extends Controller
{
    public function __construct(
        protected SeparationServiceInterface $separationService
    ) {}

    public function store(StoreStaffStatusRequest $request)
    {
        if (Gate::denies('create staff status')) {
            activity()
                ->causedBy(auth()->user())
                ->event('create staff status')
                ->withProperties([
                    'result' => 'failed',
                    'user_ip' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                ])
                ->log('Failed to create staff status for staff ID: ' . $request->staff_id);

            return redirect()->back()->with('error', 'You do not have permission to create a staff status');
        }

        $staff = InstitutionPerson::findOrFail($request->staff_id);

        $this->separationService->changeStatus($staff, $request->status, [
            'start_date' => $request->start_date,
            'description' => $request->description,
            'institution_id' => $request->institution_id ?? $staff->institution_id,
        ]);

        return redirect()->route('staff.index')->with('success', 'Staff status changed');
    }

    public function update(UpdateStaffStatusRequest $request, Status $staffStatus)
    {
        if (Gate::denies('edit staff status')) {
            activity()
                ->causedBy(auth()->user())
                ->performedOn($staffStatus)
                ->event('edit staff status')
                ->withProperties([
                    'result' => 'failed',
                    'user_ip' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                ])
                ->log('Failed to edit staff status for staff ID: ' . $staffStatus->staff_id);

            return redirect()->back()->with('error', 'You do not have permission to edit this staff status');
        }

        $this->separationService->updateStatus($staffStatus, $request->validated());

        return redirect()->back()->with('success', 'Staff status updated');
    }

    public function delete(Status $staffStatus)
    {
        if (Gate::denies('delete staff status')) {
            activity()
                ->causedBy(auth()->user())
                ->performedOn($staffStatus)
                ->event('delete staff status')
                ->withProperties([
                    'result' => 'failed',
                    'user_ip' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                ])
                ->log('Failed to delete staff status for staff ID: ' . $staffStatus->staff_id);

            return redirect()->back()->with('error', 'You do not have permission to delete this staff status');
        }

        $this->separationService->deleteStatus($staffStatus);

        return redirect()->back()->with('success', 'Staff status deleted');
    }
}
