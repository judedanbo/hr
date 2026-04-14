<?php

namespace App\Contracts\Services;

use App\Models\InstitutionPerson;
use App\Models\JobStaff;
use Illuminate\Support\Collection;

interface PromotionServiceInterface
{
    /**
     * Promote a staff member to a new rank.
     * Closes the previous open rank and creates a new rank assignment.
     *
     * @param  InstitutionPerson  $staff  The staff to promote
     * @param  int  $newRankId  The new rank (job) ID
     * @param  array  $data  Additional data (start_date, end_date, remarks)
     * @return JobStaff The created promotion record
     */
    public function promote(InstitutionPerson $staff, int $newRankId, array $data): JobStaff;

    /**
     * Promote multiple staff members to a new rank.
     *
     * @param  array  $staffIds  Array of staff IDs to promote
     * @param  int  $newRankId  The new rank (job) ID
     * @param  array  $data  Additional data (start_date, end_date, remarks)
     * @return int Number of staff successfully promoted
     */
    public function promoteMultiple(array $staffIds, int $newRankId, array $data): int;

    /**
     * Update an existing promotion record.
     *
     * @param  InstitutionPerson  $staff  The staff whose promotion to update
     * @param  int  $rankId  The rank ID to update
     * @param  array  $data  Updated promotion data
     * @return JobStaff The updated promotion record
     */
    public function updatePromotion(InstitutionPerson $staff, int $rankId, array $data): JobStaff;

    /**
     * Delete a promotion record.
     *
     * @param  InstitutionPerson  $staff  The staff whose promotion to delete
     * @param  int  $rankId  The rank ID to delete
     */
    public function deletePromotion(InstitutionPerson $staff, int $rankId): void;

    /**
     * Get staff eligible for promotion based on rank and batch.
     *
     * @param  int  $rankId  The rank to check eligibility for
     * @param  string|null  $batch  Optional batch filter ('april' or 'october')
     * @return Collection Collection of eligible staff
     */
    public function getEligibleForPromotion(int $rankId, ?string $batch = null): Collection;

    /**
     * Get the promotion history for a staff member.
     *
     * @param  InstitutionPerson  $staff  The staff to get history for
     * @return Collection Collection of promotion records
     */
    public function getPromotionHistory(InstitutionPerson $staff): Collection;
}
