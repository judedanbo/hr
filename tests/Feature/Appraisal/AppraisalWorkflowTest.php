<?php

namespace Tests\Feature\Appraisal;

use App\Enums\AppraisalStatusEnum;
use App\Models\Appraisal;
use App\Models\AppraisalCycle;
use App\Models\InstitutionPerson;
use App\Models\Person;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AppraisalWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;

    protected User $ownerUser;

    protected Appraisal $appraisal;

    protected function setUp(): void
    {
        parent::setUp();

        $this->superAdmin = User::factory()->create(['password_change_at' => now()]);
        $this->superAdmin->assignRole('super-administrator');

        $person = Person::factory()->create();
        $this->ownerUser = User::factory()->create(['person_id' => $person->id, 'password_change_at' => now()]);
        $this->ownerUser->assignRole('staff');
        $staff = InstitutionPerson::factory()->create(['person_id' => $person->id]);
        $reviewer = InstitutionPerson::factory()->create();

        $cycle = AppraisalCycle::factory()->create(['objectives_weight' => 100, 'competencies_weight' => 0]);
        $this->appraisal = Appraisal::factory()->create([
            'appraisal_cycle_id' => $cycle->id,
            'staff_id' => $staff->id,
            'reviewer_id' => $reviewer->id,
            'status' => AppraisalStatusEnum::ObjectivesAgreed,
        ]);
        $this->appraisal->objectives()->create(['title' => 'Deliver project', 'weight' => 100]);
    }

    public function test_full_lifecycle_to_completion(): void
    {
        $objectiveId = $this->appraisal->objectives()->first()->id;

        // Mid-year
        $this->actingAs($this->superAdmin)->post(route('appraisal.midyear.start', $this->appraisal))->assertRedirect();
        $this->assertSame(AppraisalStatusEnum::MidYearInProgress, $this->appraisal->fresh()->status);

        $this->actingAs($this->superAdmin)->post(route('appraisal.midyear.complete', $this->appraisal))->assertRedirect();
        $this->assertSame(AppraisalStatusEnum::MidYearCompleted, $this->appraisal->fresh()->status);

        // Open self-appraisal (creates competency ratings from the seeded library)
        $this->actingAs($this->superAdmin)->post(route('appraisal.self.open', $this->appraisal))->assertRedirect();
        $this->assertSame(AppraisalStatusEnum::SelfAppraisal, $this->appraisal->fresh()->status);
        $this->assertTrue($this->appraisal->competencyRatings()->exists());

        // Self-appraisal submission by the owner
        $this->actingAs($this->ownerUser)->post(route('appraisal.self.submit', $this->appraisal), [
            'objectives' => [['id' => $objectiveId, 'score' => 88]],
        ])->assertRedirect();
        $fresh = $this->appraisal->fresh();
        $this->assertSame(AppraisalStatusEnum::SupervisorReview, $fresh->status);
        $this->assertNotNull($fresh->self_submitted_at);

        // Supervisor review (scores) → reviewer review (reviewer is set)
        $this->actingAs($this->superAdmin)->post(route('appraisal.review.submit', $this->appraisal), [
            'objectives' => [['id' => $objectiveId, 'score' => 92]],
        ])->assertRedirect();
        $fresh = $this->appraisal->fresh();
        $this->assertSame(AppraisalStatusEnum::ReviewerReview, $fresh->status);
        $this->assertSame('92.00', (string) $fresh->overall_score);
        $this->assertSame('Outstanding', $fresh->overall_band);

        // Reviewer countersign
        $this->actingAs($this->superAdmin)->post(route('appraisal.countersign', $this->appraisal))->assertRedirect();
        $this->assertSame(AppraisalStatusEnum::AwaitingAcknowledgement, $this->appraisal->fresh()->status);

        // Employee acknowledgement
        $this->actingAs($this->ownerUser)->post(route('appraisal.acknowledge', $this->appraisal))->assertRedirect();
        $fresh = $this->appraisal->fresh();
        $this->assertSame(AppraisalStatusEnum::Completed, $fresh->status);
        $this->assertNotNull($fresh->acknowledged_at);

        // History recorded for each transition
        $this->assertGreaterThanOrEqual(6, $this->appraisal->statusHistories()->count());
    }

    public function test_review_without_reviewer_goes_straight_to_acknowledgement(): void
    {
        $this->appraisal->update(['reviewer_id' => null, 'status' => AppraisalStatusEnum::SupervisorReview]);
        $objectiveId = $this->appraisal->objectives()->first()->id;

        $this->actingAs($this->superAdmin)->post(route('appraisal.review.submit', $this->appraisal), [
            'objectives' => [['id' => $objectiveId, 'score' => 70]],
        ])->assertRedirect();

        $this->assertSame(AppraisalStatusEnum::AwaitingAcknowledgement, $this->appraisal->fresh()->status);
    }

    public function test_action_blocked_at_wrong_stage(): void
    {
        // appraisal is at ObjectivesAgreed; countersign is invalid here
        $this->actingAs($this->superAdmin)
            ->post(route('appraisal.countersign', $this->appraisal))
            ->assertStatus(422);
    }

    public function test_non_appraiser_cannot_submit_review(): void
    {
        $this->appraisal->update(['status' => AppraisalStatusEnum::SupervisorReview]);

        $stranger = User::factory()->create(['password_change_at' => now()]);
        $stranger->assignRole('staff');

        $this->actingAs($stranger)
            ->post(route('appraisal.review.submit', $this->appraisal), ['objectives' => []])
            ->assertForbidden();
    }

    public function test_reassign_updates_approvers(): void
    {
        $newAppraiser = InstitutionPerson::factory()->create();

        $this->actingAs($this->superAdmin)
            ->patch(route('appraisal.reassign', $this->appraisal), ['appraiser_id' => $newAppraiser->id])
            ->assertRedirect();

        $this->assertSame($newAppraiser->id, $this->appraisal->fresh()->appraiser_id);
    }
}
