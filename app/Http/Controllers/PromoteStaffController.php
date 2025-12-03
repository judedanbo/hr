<?php

namespace App\Http\Controllers;

use App\Contracts\Services\PromotionServiceInterface;
use App\Http\Requests\StorePromoteStaffRequest;
use App\Http\Requests\UpdatePromotionRequest;
use App\Models\InstitutionPerson;
use App\Models\Job;

class PromoteStaffController extends Controller
{
    public function __construct(
        protected PromotionServiceInterface $promotionService
    ) {}

    public function store(StorePromoteStaffRequest $request, InstitutionPerson $staff)
    {
        $this->promotionService->promote($staff, $request->rank_id, [
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

        $this->promotionService->updatePromotion($staff, $request->rank_id, [
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'remarks' => $request->remarks,
        ]);

        return redirect()->back()->with('success', 'Staff promotion successfully updated');
    }

    public function delete(InstitutionPerson $staff, Job $job)
    {
        $this->promotionService->deletePromotion($staff, $job->id);

        return redirect()->back()->with('success', 'Staff promotion successfully deleted');
    }
}
