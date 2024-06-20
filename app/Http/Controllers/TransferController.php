<?php

namespace App\Http\Controllers;

use App\Enums\TransferStatusEnum;
use App\Http\Requests\StoreTransferRequest;
use App\Http\Requests\UpdateTransferRequest;
use App\Models\InstitutionPerson;
use Carbon\Carbon;

// use Illuminate\Http\Request;

class TransferController extends Controller
{
    public function store(StoreTransferRequest $request, InstitutionPerson $staff)
    {
        $staff->units()->wherePivot('end_date', null)
            ->wherePivot('unit_id', '<>', $request->unit_id)
            ->update([
                'staff_unit.end_date' => Carbon::parse($request->start_date)->subDay(),
            ]);

        $staff->units()->attach($request->unit_id, [
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'remarks' => $request->remarks,
        ]);

        if ($request->start_date !== null) {
            $staff->units()->updateExistingPivot($request->unit_id, [
                'status' => TransferStatusEnum::Approved,
            ]);
            return redirect()->back()->with('success', 'Staff promoted successfully');
        }

        return redirect()->back()->with('success', 'Staff promotion record created successfully');
    }
    public function update(UpdateTransferRequest $request, InstitutionPerson $staff, $unit)
    {
        if ($request->staff_id != $staff->id) {
            return redirect()->back()->with('error', 'Staff ID does not match');
        }
        // return $unit . ' ' . $request->unit_id;
        $staff->units()->detach($unit);

        $staff->units()->attach($request->unit_id, $request->validated());

        return redirect()->back()->with('success', 'Staff transfer successfully updated');
    }

    public function delete(InstitutionPerson $staff, $unit)
    {
        $staff->units()->detach($unit);
        return redirect()->back()->with('success', 'Transfer has  been successfully deleted ');
    }

    public function approve(UpdateTransferRequest $request, InstitutionPerson $staff, $unit)
    {
        $staff->units()->wherePivot('end_date', null)
            ->wherePivot('unit_id', '<>', $request->unit_id)
            ->update([
                'staff_unit.end_date' => Carbon::parse($request->start_date)->subDay(),
            ]);
        $staff->units()->updateExistingPivot($unit, [
            'status' => TransferStatusEnum::Approved,
            'start_date' => $request->start_date,
        ]);
        return redirect()->back()->with('success', 'Transfer has  been successfully approved ');
    }
}
