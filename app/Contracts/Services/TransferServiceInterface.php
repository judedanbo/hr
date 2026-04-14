<?php

namespace App\Contracts\Services;

use App\Models\InstitutionPerson;
use App\Models\StaffUnit;
use Illuminate\Support\Collection;

interface TransferServiceInterface
{
    /**
     * Transfer a staff member to a new unit.
     * Creates a new unit assignment and optionally closes previous assignments.
     *
     * @param  InstitutionPerson  $staff  The staff to transfer
     * @param  int  $unitId  The new unit ID
     * @param  array  $data  Additional data (start_date, end_date, remarks)
     * @return StaffUnit The created transfer record
     */
    public function transfer(InstitutionPerson $staff, int $unitId, array $data): StaffUnit;

    /**
     * Update an existing transfer record.
     *
     * @param  InstitutionPerson  $staff  The staff whose transfer to update
     * @param  int  $oldUnitId  The old unit ID to detach
     * @param  array  $data  Updated transfer data including new unit_id
     * @return StaffUnit The updated transfer record
     */
    public function updateTransfer(InstitutionPerson $staff, int $oldUnitId, array $data): StaffUnit;

    /**
     * Delete a transfer record.
     *
     * @param  InstitutionPerson  $staff  The staff whose transfer to delete
     * @param  int  $unitId  The unit ID to delete
     */
    public function deleteTransfer(InstitutionPerson $staff, int $unitId): void;

    /**
     * Approve a pending transfer.
     * Sets the status to Approved and updates the start date.
     *
     * @param  InstitutionPerson  $staff  The staff whose transfer to approve
     * @param  int  $unitId  The unit ID to approve
     * @param  array  $data  Approval data (start_date)
     * @return StaffUnit The approved transfer record
     */
    public function approveTransfer(InstitutionPerson $staff, int $unitId, array $data): StaffUnit;

    /**
     * Get the transfer history for a staff member.
     *
     * @param  InstitutionPerson  $staff  The staff to get history for
     * @return Collection Collection of transfer records
     */
    public function getTransferHistory(InstitutionPerson $staff): Collection;
}
