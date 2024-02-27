<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePromotionListRequest;
use App\Models\Institution;
use App\Models\InstitutionPerson;
use Carbon\Carbon;
use Illuminate\Http\Request;

class PromoteAllStaffController extends Controller
{
    public function save(StorePromotionListRequest $request){
    
        $staffData = $request->validated();
        // return $staffData['rank_id'];
        $selectedStaff = InstitutionPerson::whereIn('id', $staffData['staff'])->each(function($staff) use($staffData){
            $staff->ranks()->wherePivot('end_date', null)->update([
                'job_staff.end_date' => Carbon::parse($staffData['start_date'])->subDay(),
            ]);
            $staff->ranks()->attach($staffData['rank_id'], [
                'start_date' => $staffData['start_date'],
                // 'end_date' => $staffData->promoteAll.end_date,
                // 'remarks' => $staffData->promoteAll.remarks,
            ]);
        });
        return redirect()->back()->with('success', 'Staff promoted successfully');
        // Get all staff
        // Loop through all staff
        // Promote each staff
        // Return success message
    }
}
