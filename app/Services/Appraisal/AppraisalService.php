<?php

namespace App\Services\Appraisal;

use App\Enums\AppraisalStatusEnum;
use App\Models\Appraisal;
use App\Models\AppraisalCycle;
use App\Models\InstitutionPerson;
use App\Models\Unit;
use App\Models\User;
use App\Notifications\AppraisalActionRequiredNotification;
use Illuminate\Support\Facades\DB;

class AppraisalService
{
    /**
     * Generate appraisal records for all active staff in the given cycle.
     *
     * Existing appraisals for the cycle are left untouched (idempotent), so the
     * action can be safely re-run to pick up newly added staff.
     *
     * @return int number of appraisals created
     */
    public function initiateCycle(AppraisalCycle $cycle): int
    {
        $created = 0;

        InstitutionPerson::query()
            ->active()
            ->with(['units' => fn ($query) => $query->wherePivotNull('end_date')])
            ->chunkById(200, function ($staffChunk) use ($cycle, &$created) {
                foreach ($staffChunk as $staff) {
                    $unit = $staff->units->first();
                    $chain = $this->resolveApproverChain($staff, $unit);

                    $appraisal = Appraisal::firstOrCreate(
                        ['appraisal_cycle_id' => $cycle->id, 'staff_id' => $staff->id],
                        [
                            'unit_id' => $unit?->id,
                            'appraiser_id' => $chain['appraiser_id'],
                            'reviewer_id' => $chain['reviewer_id'],
                            'status' => AppraisalStatusEnum::DraftObjectives,
                        ],
                    );

                    if ($appraisal->wasRecentlyCreated) {
                        $created++;
                    }
                }
            });

        return $created;
    }

    /**
     * Resolve the appraiser and reviewer for a staff member by walking up the
     * unit hierarchy. The staff member is never their own approver, which
     * naturally escalates a unit head up to the parent unit's head.
     *
     * @return array{appraiser_id: int|null, reviewer_id: int|null}
     */
    public function resolveApproverChain(InstitutionPerson $staff, ?Unit $unit): array
    {
        $heads = [];
        $current = $unit;

        while ($current) {
            $head = $current->head_staff_id;

            if ($head && $head !== $staff->id && ! in_array($head, $heads, true)) {
                $heads[] = $head;
            }

            $current = $current->parent;
        }

        return [
            'appraiser_id' => $heads[0] ?? null,
            'reviewer_id' => $heads[1] ?? null,
        ];
    }

    /**
     * Transition an appraisal to a new status, recording history, logging the
     * activity, and (optionally) notifying the next actor.
     */
    public function transition(Appraisal $appraisal, AppraisalStatusEnum $status, ?User $actor = null, ?string $comment = null): Appraisal
    {
        return DB::transaction(function () use ($appraisal, $status, $actor, $comment) {
            $appraisal->update(['status' => $status]);

            $appraisal->statusHistories()->create([
                'status' => $status,
                'actor_id' => $actor?->id,
                'comment' => $comment,
            ]);

            activity()
                ->causedBy($actor)
                ->performedOn($appraisal)
                ->event('appraisal status changed')
                ->withProperties([
                    'status' => $status->value,
                    'comment' => $comment,
                ])
                ->log('Appraisal #' . $appraisal->id . ' moved to ' . $status->label());

            return $appraisal->refresh();
        });
    }

    /**
     * Notify the staff member who owns an approver/staff role on an appraisal.
     */
    public function notifyStaff(?int $staffId, Appraisal $appraisal, string $action, string $body): void
    {
        if (! $staffId) {
            return;
        }

        $staff = InstitutionPerson::with('person.user')->find($staffId);
        $user = $staff?->person?->user;

        if ($user) {
            $user->notify(new AppraisalActionRequiredNotification($appraisal, $action, $body));
        }
    }
}
