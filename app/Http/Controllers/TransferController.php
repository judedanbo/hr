<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTransferRequest;
use App\Http\Requests\UpdateTransferRequest;
use App\Models\InstitutionPerson;
use App\Models\Unit;
use Carbon\Carbon;

// use Illuminate\Http\Request;

class TransferController extends Controller
{
    public function store(StoreTransferRequest $request, InstitutionPerson $staff)
    {
        $staff->units()->wherePivot('end_date', null)->update([
            'staff_unit.end_date' => Carbon::parse($request->start_date)->subDay(),
        ]);

        $staff->units()->attach($request->unit_id, [
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'remarks' => $request->remarks,
        ]);

        return redirect()->back()->with('success', 'Staff promoted successfully');
    }
    public function update(UpdateTransferRequest $request, InstitutionPerson $staff, Unit $unit)
    {
        if ($request->staff_id != $staff->id) {
            return redirect()->back()->with('error', 'Staff ID does not match');
        }

        $staff->units()->detach($unit);

        $staff->units()->attach($request->unit_id, $request->validated());

        return redirect()->back()->with('success', 'Staff transfer successfully updated');
    }

    public function delete()
    {
    }
}