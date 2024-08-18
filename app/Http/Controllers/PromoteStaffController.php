<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePromoteStaffRequest;
use App\Http\Requests\UpdatePromotionRequest;
use App\Models\InstitutionPerson;
use App\Models\Job;
use Carbon\Carbon;

class PromoteStaffController extends Controller
{
    public function store(StorePromoteStaffRequest $request, InstitutionPerson $staff)
    {
        $staff->ranks()->wherePivot('end_date', null)->update([
            'job_staff.end_date' => Carbon::parse($request->start_date)->subDay(),
        ]);
        $staff->ranks()->attach($request->rank_id, [
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'remarks' => $request->remarks,
        ]);

        return redirect()->back()->with('success', 'Staff promoted successfully');
    }

    public function update(UpdatePromotionRequest $request, InstitutionPerson $staff)
    {
        if ($request->staff_id != $staff->id) {
            return redirect()->back()->with('error', 'Staff ID does not match');
        }
        $jobStaff = $staff->ranks()->where('job_id', $request->rank_id)->first();
        // return  $jobStaff;
        if ($request->rank_id === $jobStaff?->pivot->job_id) {
            $staff->ranks()->detach($jobStaff->pivot->job_id);
        }
        $staff->ranks()->attach($request->rank_id, [
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'remarks' => $request->remarks,
        ]);

        return redirect()->back()->with('success', 'Staff promotion successfully updated');
    }

    public function delete(InstitutionPerson $staff, Job $job)
    {
        $staff->ranks()->detach($job->id);

        return redirect()->back()->with('success', 'Staff promotion successfully deleted');
    }
}
