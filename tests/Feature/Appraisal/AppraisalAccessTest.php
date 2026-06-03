<?php

namespace Tests\Feature\Appraisal;

use App\Models\Appraisal;
use App\Models\AppraisalCycle;
use App\Models\InstitutionPerson;
use App\Models\Person;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AppraisalAccessTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->superAdmin = User::factory()->create(['password_change_at' => now()]);
        $this->superAdmin->assignRole('super-administrator');
    }

    private function appraisalForUser(User $user): Appraisal
    {
        $staff = InstitutionPerson::factory()->create(['person_id' => $user->person_id]);

        return Appraisal::factory()->create([
            'appraisal_cycle_id' => AppraisalCycle::factory(),
            'staff_id' => $staff->id,
        ]);
    }

    public function test_index_requires_permission(): void
    {
        $guest = User::factory()->create(['password_change_at' => now()]);

        $this->actingAs($guest)->get(route('appraisal.index'))->assertForbidden();
    }

    public function test_index_lists_appraisals_for_authorized_user(): void
    {
        Appraisal::factory()->count(2)->create(['appraisal_cycle_id' => AppraisalCycle::factory()]);

        $this->actingAs($this->superAdmin)
            ->get(route('appraisal.index'))
            ->assertStatus(200)
            ->assertInertia(fn ($page) => $page->component('Appraisal/Index')->has('appraisals.data', 2));
    }

    public function test_owner_can_view_their_appraisal(): void
    {
        $person = Person::factory()->create();
        $user = User::factory()->create(['person_id' => $person->id, 'password_change_at' => now()]);
        $user->assignRole('staff');
        $appraisal = $this->appraisalForUser($user);

        $this->actingAs($user)
            ->get(route('appraisal.show', $appraisal))
            ->assertStatus(200)
            ->assertInertia(fn ($page) => $page->component('Appraisal/Show')->where('appraisal.id', $appraisal->id));
    }

    public function test_unrelated_staff_cannot_view_others_appraisal(): void
    {
        $ownerPerson = Person::factory()->create();
        $ownerUser = User::factory()->create(['person_id' => $ownerPerson->id, 'password_change_at' => now()]);
        $appraisal = $this->appraisalForUser($ownerUser);

        $otherPerson = Person::factory()->create();
        $otherUser = User::factory()->create(['person_id' => $otherPerson->id, 'password_change_at' => now()]);
        $otherUser->assignRole('staff');
        InstitutionPerson::factory()->create(['person_id' => $otherPerson->id]);

        $this->actingAs($otherUser)
            ->get(route('appraisal.show', $appraisal))
            ->assertForbidden();
    }

    public function test_my_appraisal_index_lists_only_own(): void
    {
        $person = Person::factory()->create();
        $user = User::factory()->create(['person_id' => $person->id, 'password_change_at' => now()]);
        $user->assignRole('staff');
        $this->appraisalForUser($user);

        // an unrelated appraisal
        Appraisal::factory()->create(['appraisal_cycle_id' => AppraisalCycle::factory()]);

        $this->actingAs($user)
            ->get(route('my-appraisal.index'))
            ->assertStatus(200)
            ->assertInertia(fn ($page) => $page->component('MyAppraisal/Index')->has('appraisals', 1));
    }

    public function test_initiate_requires_permission(): void
    {
        $guest = User::factory()->create(['password_change_at' => now()]);
        $cycle = AppraisalCycle::factory()->create();

        $this->actingAs($guest)->post(route('appraisal-cycle.initiate', $cycle))->assertForbidden();
    }
}
