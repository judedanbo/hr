<?php

namespace App\Http\Controllers;

use App\Enums\GenderEnum;
use App\Models\Job;
use Illuminate\Http\JsonResponse;

class RankStaffStatsController extends Controller
{
    public function __invoke(Job $job): JsonResponse
    {
        $job->loadCount([
            'activeStaff as total',
            'activeStaff as male' => function ($query) {
                $query->whereHas('person', fn ($q) => $q->where('gender', GenderEnum::MALE));
            },
            'activeStaff as female' => function ($query) {
                $query->whereHas('person', fn ($q) => $q->where('gender', GenderEnum::FEMALE));
            },
        ])->load(['activeStaff.person:id,first_name,surname,other_names,gender']);

        return response()->json([
            'job' => [
                'id' => $job->id,
                'name' => $job->name,
            ],
            'stats' => [
                'total' => $job->total,
                'male' => $job->male,
                'female' => $job->female,
            ],
            'staff' => $job->activeStaff->map(fn ($staff) => [
                'id' => $staff->id,
                'staff_number' => $staff->staff_number,
                'name' => $staff->person->full_name ?? $staff->person->first_name . ' ' . $staff->person->surname,
                'gender' => $staff->person->gender?->label(),
            ]),
        ]);
    }
}
