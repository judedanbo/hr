<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreStaffStatusRequest;
use App\Http\Requests\UpdateStaffStatusRequest;
use App\Models\Institution;
use App\Models\InstitutionPerson;
use App\Models\Status;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class StaffStatusController extends Controller
{
    public function store(StoreStaffStatusRequest $request)
    {
        // return $request->all();
        DB::transaction(function () use ($request) {

            $staff = InstitutionPerson::findOrFail($request->staff_id);
            if ($request->status !== "A") {
                // InstitutionPerson::where('id', $request->staff_id)
                $staff->update(['end_date' => $request->start_date ?? Carbon::now()]);
            } else {
                // InstitutionPerson::where('id', $request->staff_id)

                $staff->update(['end_date' => null]);
            }
            $status  = Status::where('staff_id', $request->staff_id)
                ->whereNull('end_date');
            // dd($status->get()->first()->status);
            activity()
                ->causedBy(auth()->user())
                ->performedOn($staff)
                ->event('changed staff status')
                ->withProperties([
                    'result' => 'success',
                    'old_status' => $status->first()->status ?? null,
                    'new_status' => $request->status,
                    'user_ip' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                ])
                ->log('changed staff status for staff ID: ' . $request->staff_id);
            $status->update(['end_date' => Carbon::parse($request->start_date)->subDays(1)]);
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
