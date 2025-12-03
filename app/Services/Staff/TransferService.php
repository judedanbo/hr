<?php

namespace App\Services\Staff;

use App\Contracts\Services\TransferServiceInterface;
use App\Enums\TransferStatusEnum;
use App\Models\InstitutionPerson;
use App\Models\StaffUnit;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class TransferService implements TransferServiceInterface
{
    /**
     * Transfer a staff member to a new unit.
     * Creates a new unit assignment and optionally closes previous assignments.
     */
    public function transfer(InstitutionPerson $staff, int $unitId, array $data): StaffUnit
    {
        return DB::transaction(function () use ($staff, $unitId, $data) {
            $startDate = isset($data['start_date']) ? Carbon::parse($data['start_date']) : null;

            // Close any open unit assignments (except for the same unit)
            if ($startDate) {
                $staff->units()->wherePivot('end_date', null)
                    ->wherePivot('unit_id', '<>', $unitId)
                    ->update([
                        'staff_unit.end_date' => $startDate->copy()->subDay(),
                    ]);
            }

            // Determine initial status based on whether start_date is provided
            $status = $startDate ? TransferStatusEnum::Approved : TransferStatusEnum::Pending;

            // Attach the new unit
            $staff->units()->attach($unitId, [
                'start_date' => $startDate,
                'end_date' => isset($data['end_date']) ? Carbon::parse($data['end_date']) : null,
                'remarks' => $data['remarks'] ?? null,
                'status' => $status,
            ]);

            // Return the newly created StaffUnit record
            return StaffUnit::where('staff_unit.staff_id', $staff->id)
                ->where('staff_unit.unit_id', $unitId)
                ->latest('staff_unit.created_at')
                ->first();
        });
    }

    /**
     * Update an existing transfer record.
     */
    public function updateTransfer(InstitutionPerson $staff, int $oldUnitId, array $data): StaffUnit
    {
        return DB::transaction(function () use ($staff, $oldUnitId, $data) {
            // Detach the old unit assignment
            $staff->units()->detach($oldUnitId);

            // Determine the new unit ID (may be same or different)
            $newUnitId = $data['unit_id'] ?? $oldUnitId;

            // Attach with updated data
            $staff->units()->attach($newUnitId, [
                'start_date' => isset($data['start_date']) ? Carbon::parse($data['start_date']) : null,
                'end_date' => isset($data['end_date']) ? Carbon::parse($data['end_date']) : null,
                'remarks' => $data['remarks'] ?? null,
                'status' => $data['status'] ?? TransferStatusEnum::Pending,
            ]);

            return StaffUnit::where('staff_unit.staff_id', $staff->id)
                ->where('staff_unit.unit_id', $newUnitId)
                ->latest('staff_unit.created_at')
                ->first();
        });
    }

    /**
     * Delete a transfer record.
     */
    public function deleteTransfer(InstitutionPerson $staff, int $unitId): void
    {
        $staff->units()->detach($unitId);
    }

    /**
     * Approve a pending transfer.
     * Sets the status to Approved and updates the start date.
     */
    public function approveTransfer(InstitutionPerson $staff, int $unitId, array $data): StaffUnit
    {
        return DB::transaction(function () use ($staff, $unitId, $data) {
            $startDate = isset($data['start_date']) ? Carbon::parse($data['start_date']) : Carbon::now();

            // Close any other open unit assignments
            $staff->units()->wherePivot('end_date', null)
                ->wherePivot('unit_id', '<>', $unitId)
                ->update([
                    'staff_unit.end_date' => $startDate->copy()->subDay(),
                ]);

            // Update the pending transfer to approved
            $staff->units()->updateExistingPivot($unitId, [
                'status' => TransferStatusEnum::Approved,
                'start_date' => $startDate,
            ]);

            return StaffUnit::where('staff_unit.staff_id', $staff->id)
                ->where('staff_unit.unit_id', $unitId)
                ->first();
        });
    }

    /**
     * Get the transfer history for a staff member.
     */
    public function getTransferHistory(InstitutionPerson $staff): Collection
    {
        $staff->load(['units.parent']);

        return $staff->units->map(function ($unit) {
            return [
                'unit_id' => $unit->id,
                'unit_name' => $unit->name,
                'staff_id' => $unit->pivot->staff_id,
                'department' => $unit->parent?->name,
                'department_short_name' => $unit->parent?->short_name,
                'status' => $unit->pivot->status?->label(),
                'status_color' => $unit->pivot->status?->color(),
                'start_date' => $unit->pivot->start_date?->format('d M Y'),
                'start_date_unix' => $unit->pivot->start_date?->format('Y-m-d'),
                'end_date' => $unit->pivot->end_date?->format('d M Y'),
                'end_date_unix' => $unit->pivot->end_date?->format('Y-m-d'),
                'distance' => $unit->pivot->start_date?->diffForHumans(),
                'remarks' => $unit->pivot->remarks,
            ];
        });
    }
}
