<?php

namespace App\Contracts\Services;

use App\Models\InstitutionPerson;
use App\Models\Status;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface SeparationServiceInterface
{
    /**
     * Separate a staff member (change their status from Active).
     * Creates a new status record and updates the staff end_date if not Active.
     *
     * @param  InstitutionPerson  $staff  The staff to separate
     * @param  string  $statusCode  The status code (from EmployeeStatusEnum)
     * @param  array  $data  Additional data (start_date, description, institution_id)
     * @return Status The created status record
     */
    public function changeStatus(InstitutionPerson $staff, string $statusCode, array $data): Status;

    /**
     * Update an existing status record.
     *
     * @param  Status  $status  The status to update
     * @param  array  $data  Updated status data
     * @return Status The updated status record
     */
    public function updateStatus(Status $status, array $data): Status;

    /**
     * Delete a status record.
     *
     * @param  Status  $status  The status to delete
     */
    public function deleteStatus(Status $status): void;

    /**
     * Get a paginated list of separated (non-active) staff.
     *
     * @param  array  $filters  Optional filters for the query
     * @param  int  $perPage  Number of items per page
     * @return LengthAwarePaginator Paginated list of separated staff
     */
    public function getSeparatedStaff(array $filters = [], int $perPage = 10): LengthAwarePaginator;

    /**
     * Get the status history for a staff member.
     *
     * @param  InstitutionPerson  $staff  The staff to get history for
     * @return Collection Collection of status records
     */
    public function getStatusHistory(InstitutionPerson $staff): Collection;
}
