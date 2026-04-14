<?php

namespace Tests\Feature;

use App\Models\Institution;
use App\Models\InstitutionPerson;
use App\Models\Job;
use App\Models\JobCategory;
use App\Models\Person;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StaffPromotionTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Institution $institution;

    protected InstitutionPerson $staff;

    protected Job $currentRank;

    protected Job $newRank;

    protected function setUp(): void
    {
        parent::setUp();

        // Create institution
        $this->institution = Institution::factory()->create();

        // Create job category and ranks
        $category = JobCategory::factory()->create(['institution_id' => $this->institution->id]);
        $this->currentRank = Job::factory()->create([
            'name' => 'Junior Officer',
            'institution_id' => $this->institution->id,
            'job_category_id' => $category->id,
        ]);
        $this->newRank = Job::factory()->create([
            'name' => 'Senior Officer',
            'institution_id' => $this->institution->id,
            'job_category_id' => $category->id,
        ]);

        // Create person with staff record
        $person = Person::factory()->create();
        $person->institution()->attach($this->institution->id, [
            'staff_number' => 'STF001',
            'hire_date' => now()->subYears(3),
        ]);
        $this->staff = InstitutionPerson::where('person_id', $person->id)->first();

        // Attach current rank
        $this->staff->ranks()->attach($this->currentRank->id, [
            'start_date' => now()->subYears(2),
        ]);

        // Create authorized user
        $adminPerson = Person::factory()->create();
        $adminPerson->institution()->attach($this->institution->id, [
            'staff_number' => 'ADMIN001',
            'hire_date' => now()->subYears(5),
        ]);

        $this->user = User::factory()->create([
            'person_id' => $adminPerson->id,
            'password_change_at' => now(),
        ]);
        $this->user->givePermissionTo('create staff promotion');
        $this->user->givePermissionTo('update staff promotion');
        $this->user->givePermissionTo('delete staff promotion');
        $this->user->givePermissionTo('view all staff');
    }

    // ===================
    // AUTHORIZATION TESTS
    // ===================

    public function test_promotion_requires_authentication(): void
    {
        $response = $this->post(route('staff.promote.store', $this->staff), [
            'staff_id' => $this->staff->id,
            'rank_id' => $this->newRank->id,
            'start_date' => now()->format('Y-m-d'),
        ]);

        $response->assertRedirect('/login');
    }

    public function test_promotion_requires_permission(): void
    {
        $userWithoutPermission = User::factory()->create();

        $response = $this->actingAs($userWithoutPermission)
            ->post(route('staff.promote.store', $this->staff), [
                'staff_id' => $this->staff->id,
                'rank_id' => $this->newRank->id,
                'start_date' => now()->format('Y-m-d'),
            ]);

        $response->assertForbidden();
    }

    // ===================
    // PROMOTION STORE TESTS
    // ===================

    public function test_can_promote_staff_with_valid_data(): void
    {
        $promotionData = [
            'staff_id' => $this->staff->id,
            'rank_id' => $this->newRank->id,
            'start_date' => now()->format('Y-m-d'),
            'remarks' => 'Promoted based on performance',
        ];

        $response = $this->actingAs($this->user)
            ->post(route('staff.promote.store', $this->staff), $promotionData);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Verify the new rank is attached
        $this->assertDatabaseHas('job_staff', [
            'staff_id' => $this->staff->id,
            'job_id' => $this->newRank->id,
        ]);
    }

    public function test_promotion_closes_previous_rank_end_date(): void
    {
        $startDate = now()->format('Y-m-d');

        $response = $this->actingAs($this->user)
            ->post(route('staff.promote.store', $this->staff), [
                'staff_id' => $this->staff->id,
                'rank_id' => $this->newRank->id,
                'start_date' => $startDate,
            ]);

        $response->assertRedirect();

        // Check that previous rank has an end date set
        $previousRankPivot = $this->staff->ranks()
            ->where('job_id', $this->currentRank->id)
            ->first();

        $this->assertNotNull($previousRankPivot->pivot->end_date);
    }

    public function test_promotion_with_end_date_is_valid(): void
    {
        $promotionData = [
            'staff_id' => $this->staff->id,
            'rank_id' => $this->newRank->id,
            'start_date' => now()->format('Y-m-d'),
            'end_date' => now()->addYear()->format('Y-m-d'),
            'remarks' => 'Acting promotion',
        ];

        $response = $this->actingAs($this->user)
            ->post(route('staff.promote.store', $this->staff), $promotionData);

        $response->assertRedirect();
        $response->assertSessionHas('success');
    }

    // ===================
    // VALIDATION TESTS
    // ===================

    public function test_promotion_requires_staff_id(): void
    {
        $response = $this->actingAs($this->user)
            ->post(route('staff.promote.store', $this->staff), [
                'rank_id' => $this->newRank->id,
                'start_date' => now()->format('Y-m-d'),
            ]);

        $response->assertSessionHasErrors('staff_id');
    }

    public function test_promotion_requires_rank_id(): void
    {
        $response = $this->actingAs($this->user)
            ->post(route('staff.promote.store', $this->staff), [
                'staff_id' => $this->staff->id,
                'start_date' => now()->format('Y-m-d'),
            ]);

        $response->assertSessionHasErrors('rank_id');
    }

    public function test_promotion_requires_start_date(): void
    {
        $response = $this->actingAs($this->user)
            ->post(route('staff.promote.store', $this->staff), [
                'staff_id' => $this->staff->id,
                'rank_id' => $this->newRank->id,
            ]);

        $response->assertSessionHasErrors('start_date');
    }

    public function test_promotion_requires_valid_staff_id(): void
    {
        $response = $this->actingAs($this->user)
            ->post(route('staff.promote.store', $this->staff), [
                'staff_id' => 99999,
                'rank_id' => $this->newRank->id,
                'start_date' => now()->format('Y-m-d'),
            ]);

        $response->assertSessionHasErrors('staff_id');
    }

    public function test_promotion_end_date_must_be_after_start_date(): void
    {
        $response = $this->actingAs($this->user)
            ->post(route('staff.promote.store', $this->staff), [
                'staff_id' => $this->staff->id,
                'rank_id' => $this->newRank->id,
                'start_date' => now()->format('Y-m-d'),
                'end_date' => now()->subMonth()->format('Y-m-d'),
            ]);

        $response->assertSessionHasErrors('end_date');
    }

    // ===================
    // PROMOTION UPDATE TESTS
    // ===================

    public function test_can_update_promotion_record(): void
    {
        // First create a promotion
        $this->staff->ranks()->attach($this->newRank->id, [
            'start_date' => now()->subMonth(),
        ]);

        $updateData = [
            'staff_id' => $this->staff->id,
            'rank_id' => $this->newRank->id,
            'start_date' => now()->format('Y-m-d'),
            'remarks' => 'Updated promotion record',
        ];

        $response = $this->actingAs($this->user)
            ->patch(route('staff.promote.update', [$this->staff, $this->newRank]), $updateData);

        $response->assertRedirect();
    }

    public function test_update_fails_if_staff_id_mismatch(): void
    {
        $otherPerson = Person::factory()->create();
        $otherPerson->institution()->attach($this->institution->id, [
            'staff_number' => 'STF002',
            'hire_date' => now()->subYear(),
        ]);
        $otherStaff = InstitutionPerson::where('person_id', $otherPerson->id)->first();

        $updateData = [
            'staff_id' => $otherStaff->id, // Different staff ID
            'rank_id' => $this->newRank->id,
            'start_date' => now()->format('Y-m-d'),
        ];

        $response = $this->actingAs($this->user)
            ->patch(route('staff.promote.update', [$this->staff, $this->newRank]), $updateData);

        $response->assertSessionHas('error');
    }

    // ===================
    // PROMOTION DELETE TESTS
    // ===================

    public function test_can_delete_promotion_record(): void
    {
        // First create a promotion to delete
        $this->staff->ranks()->attach($this->newRank->id, [
            'start_date' => now()->subMonth(),
        ]);

        $response = $this->actingAs($this->user)
            ->delete(route('staff.promote.delete', [$this->staff, $this->newRank]));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Verify the rank is detached
        $this->assertDatabaseMissing('job_staff', [
            'staff_id' => $this->staff->id,
            'job_id' => $this->newRank->id,
        ]);
    }

    // ===================
    // EDGE CASE TESTS
    // ===================

    public function test_staff_can_have_multiple_rank_history(): void
    {
        // Create third rank
        $category = JobCategory::factory()->create(['institution_id' => $this->institution->id]);
        $thirdRank = Job::factory()->create([
            'name' => 'Principal Officer',
            'institution_id' => $this->institution->id,
            'job_category_id' => $category->id,
        ]);

        // First promotion
        $this->actingAs($this->user)
            ->post(route('staff.promote.store', $this->staff), [
                'staff_id' => $this->staff->id,
                'rank_id' => $this->newRank->id,
                'start_date' => now()->subMonth()->format('Y-m-d'),
            ]);

        // Second promotion
        $this->actingAs($this->user)
            ->post(route('staff.promote.store', $this->staff), [
                'staff_id' => $this->staff->id,
                'rank_id' => $thirdRank->id,
                'start_date' => now()->format('Y-m-d'),
            ]);

        // Staff should have 3 ranks in history (original + 2 promotions)
        $this->assertEquals(3, $this->staff->ranks()->count());
    }

    public function test_promotion_with_remarks_stores_correctly(): void
    {
        $remarks = 'Exceptional performance in Q3 2024';

        $this->actingAs($this->user)
            ->post(route('staff.promote.store', $this->staff), [
                'staff_id' => $this->staff->id,
                'rank_id' => $this->newRank->id,
                'start_date' => now()->format('Y-m-d'),
                'remarks' => $remarks,
            ]);

        $this->assertDatabaseHas('job_staff', [
            'staff_id' => $this->staff->id,
            'job_id' => $this->newRank->id,
            'remarks' => $remarks,
        ]);
    }
}
