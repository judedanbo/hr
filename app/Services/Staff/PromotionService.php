<?php

namespace App\Services\Staff;

use App\Contracts\Services\PromotionServiceInterface;
use App\Models\InstitutionPerson;
use App\Models\JobStaff;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PromotionService implements PromotionServiceInterface
{
    /**
     * Promote a staff member to a new rank.
     * Closes the previous open rank and creates a new rank assignment.
     */
    public function promote(InstitutionPerson $staff, int $newRankId, array $data): JobStaff
    {
        return DB::transaction(function () use ($staff, $newRankId, $data) {
            $startDate = isset($data['start_date']) ? Carbon::parse($data['start_date']) : Carbon::now();

            // Close any open rank assignment (set end_date to day before new start)
            $staff->ranks()->wherePivot('end_date', null)->update([
                'job_staff.end_date' => $startDate->copy()->subDay(),
            ]);

            // Attach the new rank
            $staff->ranks()->attach($newRankId, [
                'start_date' => $startDate,
                'end_date' => isset($data['end_date']) ? Carbon::parse($data['end_date']) : null,
                'remarks' => $data['remarks'] ?? null,
            ]);

            // Return the newly created JobStaff record
            return JobStaff::where('staff_id', $staff->id)
                ->where('job_id', $newRankId)
                ->latest('created_at')
                ->first();
        });
    }

    /**
     * Promote multiple staff members to a new rank.
     */
    public function promoteMultiple(array $staffIds, int $newRankId, array $data): int
    {
        $promoted = 0;

        DB::transaction(function () use ($staffIds, $newRankId, $data, &$promoted) {
            foreach ($staffIds as $staffId) {
                $staff = InstitutionPerson::find($staffId);
                if ($staff) {
                    $this->promote($staff, $newRankId, $data);
                    $promoted++;
                }
            }
        });

        return $promoted;
    }

    /**
     * Update an existing promotion record.
     */
    public function updatePromotion(InstitutionPerson $staff, int $rankId, array $data): JobStaff
    {
        return DB::transaction(function () use ($staff, $rankId, $data) {
            // Get the existing promotion record
            $existingRank = $staff->ranks()->where('job_id', $rankId)->first();

            if ($existingRank) {
                // Detach the old record
                $staff->ranks()->detach($rankId);
            }

            // Determine the new rank ID (may be same or different)
            $newRankId = $data['rank_id'] ?? $rankId;

            // Attach with updated data
            $staff->ranks()->attach($newRankId, [
                'start_date' => isset($data['start_date']) ? Carbon::parse($data['start_date']) : null,
                'end_date' => isset($data['end_date']) ? Carbon::parse($data['end_date']) : null,
                'remarks' => $data['remarks'] ?? null,
            ]);

            return JobStaff::where('staff_id', $staff->id)
                ->where('job_id', $newRankId)
                ->latest('created_at')
                ->first();
        });
    }

    /**
     * Delete a promotion record.
     */
    public function deletePromotion(InstitutionPerson $staff, int $rankId): void
    {
        $staff->ranks()->detach($rankId);
    }

    /**
     * Get staff eligible for promotion based on rank and batch.
     *
     * @param  int  $rankId  The current rank to check eligibility for
     * @param  string|null  $batch  Optional batch filter ('april' or 'october')
     */
    public function getEligibleForPromotion(int $rankId, ?string $batch = null): Collection
    {
        $query = InstitutionPerson::query()
            ->active()
            ->with(['person', 'ranks'])
            ->filterByRank($rankId);

        // Apply batch-specific scope
        if ($batch === 'april') {
            $query->toPromoteApril();
        } elseif ($batch === 'october') {
            $query->toPromoteOctober();
        } else {
            $query->toPromote();
        }

        return $query->get();
    }

    /**
     * Get the promotion history for a staff member.
     */
    public function getPromotionHistory(InstitutionPerson $staff): Collection
    {
        $staff->load(['ranks', 'person']);

        return $staff->ranks->map(function ($rank) use ($staff) {
            return [
                'id' => $rank->id,
                'name' => $rank->name,
                'staff_name' => $staff->person->full_name,
                'staff_id' => $rank->pivot->staff_id,
                'rank_id' => $rank->pivot->job_id,
                'start_date' => $rank->pivot->start_date?->format('d M Y'),
                'start_date_unix' => $rank->pivot->start_date?->format('Y-m-d'),
                'end_date' => $rank->pivot->end_date?->format('d M Y'),
                'end_date_unix' => $rank->pivot->end_date?->format('Y-m-d'),
                'remarks' => $rank->pivot->remarks,
                'distance' => $rank->pivot->start_date?->diffForHumans(),
                'years_in_rank' => $rank->pivot->start_date ? (int) $rank->pivot->start_date->diffInYears() : null,
            ];
        });
    }
}
