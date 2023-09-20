<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreStaffStatusRequest;
use App\Models\InstitutionPerson;
use App\Models\Status;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StaffStatusController extends Controller
{
    public function store(StoreStaffStatusRequest $request)
    {
        DB::transaction(function () use ($request) {
            Status::where('staff_id', $request->staff_id)
                ->whereNull('end_date')
                ->update(['status.end_date' => Carbon::parse($request->start_date)->subDays(1)]);
            Status::create($request->all());
        });

        return redirect()->back()->with('success', 'Staff status changed');
    }
}