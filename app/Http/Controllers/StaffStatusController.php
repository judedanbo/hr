<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreStaffStatusRequest;
use App\Http\Requests\UpdateStaffStatusRequest;
use App\Models\Status;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class StaffStatusController extends Controller
{
    public function store(StoreStaffStatusRequest $request)
    {
        // return $request->all();
        DB::transaction(function () use ($request) {
            Status::where('staff_id', $request->staff_id)
                ->whereNull('end_date')
                ->update(['status.end_date' => Carbon::parse($request->start_date)->subDays(1)]);
            Status::create($request->all());
        });

        return redirect()->back()->with('success', 'Staff status changed');
    }

    public function update(UpdateStaffStatusRequest $request, Status $staffStatus)
    {
        $staffStatus->update($request->validated());

        return redirect()->back()->with('success', 'Staff status updated');
    }

    public function delete(Status $staffStatus)
    {
        $staffStatus->delete();

        return redirect()->back()->with('success', 'Staff status deleted');
    }
}
