<?php

namespace Tests\Feature\Appraisal;

use App\Enums\AppraisalStatusEnum;
use App\Models\Appraisal;
use App\Models\AppraisalCycle;
use App\Models\InstitutionPerson;
use App\Models\Person;
use App\Models\User;
use App\Notifications\AppraisalActionRequiredNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AppraisalObjectiveTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;

    protected User $ownerUser;

    protected InstitutionPerson $staff;

    protected Appraisal $appraisal;

    protected function setUp(): void
    {
        parent::setUp();

        $this->superAdmin = User::factory()->create(['password_change_at' => now()]);
        $this->superAdmin->assignRole('super-administrator');

        $person = Person::factory()->create();
        $this->ownerUser = User::factory()->create(['person_id' => $person->id, 'password_change_at' => now()]);
        $this->ownerUser->assignRole('staff');
        $this->staff = InstitutionPerson::factory()->create(['person_id' => $person->id]);

        $cycle = AppraisalCycle::factory()->create();
        $this->appraisal = Appraisal::factory()->create([
            'appraisal_cycle_id' => $cycle->id,
            'staff_id' => $this->staff->id,
            'status' => AppraisalStatusEnum::DraftObjectives,
        ]);
    }

    public function test_owner_can_add_objective(): void
    {
        $this->actingAs($this->ownerUser)
            ->post(route('appraisal.objective.store', $this->appraisal), [
                'title' => 'Improve turnaround time',
                'weight' => 50,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('appraisal_objectives', [
            'appraisal_id' => $this->appraisal->id,
            'title' => 'Improve turnaround time',
        ]);
    }

    public function test_non_owner_staff_cannot_add_objective(): void
    {
        $otherPerson = Person::factory()->create();
        $other = User::factory()->create(['person_id' => $otherPerson->id, 'password_change_at' => now()]);
        $other->assignRole('staff');
        InstitutionPerson::factory()->create(['person_id' => $otherPerson->id]);

        $this->actingAs($other)
            ->post(route('appraisal.objective.store', $this->appraisal), ['title' => 'Sneaky', 'weight' => 10])
            ->assertForbidden();
    }

    public function test_cannot_edit_objectives_once_agreed(): void
    {
        $this->appraisal->update(['status' => AppraisalStatusEnum::ObjectivesAgreed]);

        $this->actingAs($this->superAdmin)
            ->post(route('appraisal.objective.store', $this->appraisal), ['title' => 'Late', 'weight' => 10])
            ->assertStatus(422);
    }

    public function test_agree_requires_weights_to_total_100(): void
    {
        $this->appraisal->objectives()->create(['title' => 'A', 'weight' => 60]);

        $this->actingAs($this->superAdmin)
            ->post(route('appraisal.objectives.agree', $this->appraisal))
            ->assertSessionHasErrors('objectives');

        $this->assertSame(AppraisalStatusEnum::DraftObjectives, $this->appraisal->fresh()->status);
    }

    public function test_agree_transitions_and_records_history_and_notifies(): void
    {
        Notification::fake();

        $this->appraisal->objectives()->create(['title' => 'A', 'weight' => 60]);
        $this->appraisal->objectives()->create(['title' => 'B', 'weight' => 40]);

        $this->actingAs($this->superAdmin)
            ->post(route('appraisal.objectives.agree', $this->appraisal))
            ->assertRedirect();

        $this->assertSame(AppraisalStatusEnum::ObjectivesAgreed, $this->appraisal->fresh()->status);
        $this->assertDatabaseHas('appraisal_status_histories', [
            'appraisal_id' => $this->appraisal->id,
            'status' => AppraisalStatusEnum::ObjectivesAgreed->value,
        ]);
        Notification::assertSentTo($this->ownerUser, AppraisalActionRequiredNotification::class);
    }
}
