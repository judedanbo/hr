<?php

namespace App\Http\Controllers;

use App\Models\Appraisal;
use Illuminate\Http\Request;
use Inertia\Inertia;

class MyAppraisalController extends Controller
{
    public function index(Request $request)
    {
        $staffIds = $request->user()->staffIds();

        return Inertia::render('MyAppraisal/Index', [
            'appraisals' => Appraisal::query()
                ->whereIn('staff_id', $staffIds)
                ->with(['cycle:id,name,year', 'appraiser.person', 'reviewer.person'])
                ->latest()
                ->get()
                ->map(fn ($appraisal) => [
                    'id' => $appraisal->id,
                    'cycle' => $appraisal->cycle?->name,
                    'year' => $appraisal->cycle?->year,
                    'appraiser_name' => $appraisal->appraiser?->person?->full_name ?? 'Unassigned',
                    'reviewer_name' => $appraisal->reviewer?->person?->full_name ?? 'Unassigned',
                    'status' => $appraisal->status->value,
                    'status_label' => $appraisal->status->label(),
                    'status_color' => $appraisal->status->color(),
                    'overall_score' => $appraisal->overall_score !== null ? (float) $appraisal->overall_score : null,
                    'overall_band' => $appraisal->overall_band,
                ]),
        ]);
    }
}
