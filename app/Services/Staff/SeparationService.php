<?php

namespace App\Services\Staff;

use App\Contracts\Services\SeparationServiceInterface;
use App\Models\InstitutionPerson;
use App\Models\Status;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SeparationService implements SeparationServiceInterface
{
    /**
     * Change a staff member's status.
     * Creates a new status record and updates the staff end_date if not Active.
     */
    public function changeStatus(InstitutionPerson $staff, string $statusCode, array $data): Status
    {
        return DB::transaction(function () use ($staff, $statusCode, $data) {
            $startDate = isset($data['start_date']) ? Carbon::parse($data['start_date']) : Carbon::now();

            // Update staff end_date based on status
            if ($statusCode !== 'A') {
                // Non-active status: set end_date
                $staff->update(['end_date' => $startDate]);
            } else {
                // Active status: clear end_date
                $staff->update(['end_date' => null]);
            }

            // Close any existing open status
            Status::where('staff_id', $staff->id)
                ->whereNull('end_date')
                ->update(['end_date' => $startDate->copy()->subDay()]);

            // Log activity for status change
            $oldStatus = Status::where('staff_id', $staff->id)
                ->whereNotNull('end_date')
                ->latest('end_date')
                ->first();

            activity()
                ->causedBy(auth()->user())
                ->performedOn($staff)
                ->event('changed staff status')
                ->withProperties([
                    'result' => 'success',
                    'old_status' => $oldStatus?->status ?? null,
                    'new_status' => $statusCode,
                    'user_ip' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                ])
                ->log('changed staff status for staff ID: ' . $staff->id);

            // Create the new status record
            return Status::create([
                'staff_id' => $staff->id,
                'status' => $statusCode,
                'description' => $data['description'] ?? null,
                'institution_id' => $data['institution_id'] ?? $staff->institution_id,
                'start_date' => $startDate,
                'end_date' => isset($data['end_date']) ? Carbon::parse($data['end_date']) : null,
            ]);
        });
    }

    /**
     * Update an existing status record.
     */
    public function updateStatus(Status $status, array $data): Status
    {
        $status->update($data);

        return $status->fresh();
    }

    /**
     * Delete a status record.
     */
    public function deleteStatus(Status $status): void
    {
        $status->delete();
    }

    /**
     * Get a paginated list of separated (non-active) staff.
     */
    public function getSeparatedStaff(array $filters = [], int $perPage = 10): LengthAwarePaginator
    {
        $query = InstitutionPerson::query()
            ->retired()
            ->with([
                'person' => function ($query) {
                    $query->with(['contacts', 'identities']);
                },
                'statuses',
                'ranks',
                'units',
            ])
            ->currentRank()
            ->currentUnit();

        // Apply optional filters
        if (isset($filters['search'])) {
            $query->search($filters['search']);
        }

        if (isset($filters['status'])) {
            $query->whereHas('statuses', function ($q) use ($filters) {
                $q->where('status', $filters['status'])
                    ->whereNull('end_date');
            });
        }

        return $query->paginate($perPage)->withQueryString();
    }

    /**
     * Get the status history for a staff member.
     */
    public function getStatusHistory(InstitutionPerson $staff): Collection
    {
        $staff->load('statuses');

        return $staff->statuses->map(function ($status) {
            return [
                'id' => $status->id,
                'status' => $status->status,
                'status_display' => $status->status?->name,
                'description' => $status->description,
                'start_date' => $status->start_date?->format('Y-m-d'),
                'start_date_display' => $status->start_date?->format('d M Y'),
                'end_date' => $status->end_date?->format('Y-m-d'),
                'end_date_display' => $status->end_date?->format('d M Y'),
                'is_current' => $status->end_date === null,
            ];
        });
    }
}
