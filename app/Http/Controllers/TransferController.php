<?php

namespace App\Http\Controllers;

use App\Contracts\Services\TransferServiceInterface;
use App\Http\Requests\StoreTransferRequest;
use App\Http\Requests\UpdateTransferRequest;
use App\Models\InstitutionPerson;

class TransferController extends Controller
{
    public function __construct(
        protected TransferServiceInterface $transferService
    ) {}

    public function store(StoreTransferRequest $request, InstitutionPerson $staff)
    {
        $this->transferService->transfer($staff, $request->unit_id, [
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'remarks' => $request->remarks,
        ]);

        $message = $request->start_date !== null
            ? 'Staff transferred successfully'
            : 'Staff transfer record created successfully';

        return redirect()->back()->with('success', $message);
    }

    public function update(UpdateTransferRequest $request, InstitutionPerson $staff, $unit)
    {
        if ($request->staff_id != $staff->id) {
            return redirect()->back()->with('error', 'Staff ID does not match');
        }

        $this->transferService->updateTransfer($staff, $unit, $request->validated());

        return redirect()->back()->with('success', 'Staff transfer successfully updated');
    }

    public function delete(InstitutionPerson $staff, $unit)
    {
        $this->transferService->deleteTransfer($staff, $unit);

        return redirect()->back()->with('success', 'Transfer has been successfully deleted');
    }

    public function approve(UpdateTransferRequest $request, InstitutionPerson $staff, $unit)
    {
        $this->transferService->approveTransfer($staff, $unit, [
            'start_date' => $request->start_date,
        ]);

        return redirect()->back()->with('success', 'Transfer has been successfully approved');
    }
}
