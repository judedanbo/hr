<?php

namespace App\Http\Controllers;

use App\Models\InstitutionPerson;
use App\Models\Status;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StaffStatusController extends Controller
{
    public function store(Request $request)
    {
        return $request;
        // validation
        DB::transaction(function () use ($request) {
            Status::where('staff_id', $request->staff_id)
                ->whereNull('end_date')
                ->update(['status.end_date' => Carbon::now()]);
            Status::create($request->all());
        });

        return redirect()->route('person.show', $request->person_id)->with('success', 'Staff status changed');
    }
}