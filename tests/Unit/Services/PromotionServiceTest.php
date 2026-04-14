<?php

namespace Tests\Unit\Services;

use App\Contracts\Services\PromotionServiceInterface;
use App\Models\Institution;
use App\Models\InstitutionPerson;
use App\Models\Job;
use App\Models\JobStaff;
use App\Services\Staff\PromotionService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PromotionServiceTest extends TestCase
{
    use RefreshDatabase;

    protected PromotionServiceInterface $service;

    protected Institution $institution;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new PromotionService;
        $this->institution = Institution::factory()->create();
    }

    public function test_promote_creates_new_rank_assignment(): void
    {
        $staff = InstitutionPerson::factory()->create([
            'institution_id' => $this->institution->id,
        ]);
        $newRank = Job::factory()->create(['institution_id' => $this->institution->id]);

        $result = $this->service->promote($staff, $newRank->id, [
            'start_date' => '2024-01-01',
            'remarks' => 'Promotion for excellent performance',
        ]);

        $this->assertInstanceOf(JobStaff::class, $result);
        $this->assertEquals($newRank->id, $result->job_id);
        $this->assertEquals($staff->id, $result->staff_id);
        $this->assertEquals('Promotion for excellent performance', $result->remarks);
    }

    public function test_promote_closes_previous_open_rank(): void
    {
        $staff = InstitutionPerson::factory()->create([
            'institution_id' => $this->institution->id,
        ]);
        $oldRank = Job::factory()->create(['institution_id' => $this->institution->id]);
        $newRank = Job::factory()->create(['institution_id' => $this->institution->id]);

        // Assign initial rank
        $staff->ranks()->attach($oldRank->id, [
            'start_date' => '2020-01-01',
            'end_date' => null,
        ]);

        $this->service->promote($staff, $newRank->id, [
            'start_date' => '2024-01-01',
        ]);

        // Refresh the staff to get updated pivot data
        $staff->refresh();

        // Get the old rank's pivot data
        $oldRankPivot = $staff->ranks()->where('job_id', $oldRank->id)->first();
        $newRankPivot = $staff->ranks()->where('job_id', $newRank->id)->first();

        $this->assertNotNull($oldRankPivot->pivot->end_date);
        $this->assertNull($newRankPivot->pivot->end_date);
    }

    public function test_promote_multiple_promotes_several_staff_members(): void
    {
        $staff1 = InstitutionPerson::factory()->create([
            'institution_id' => $this->institution->id,
        ]);
        $staff2 = InstitutionPerson::factory()->create([
            'institution_id' => $this->institution->id,
        ]);
        $staff3 = InstitutionPerson::factory()->create([
            'institution_id' => $this->institution->id,
        ]);
        $newRank = Job::factory()->create(['institution_id' => $this->institution->id]);

        $count = $this->service->promoteMultiple(
            [$staff1->id, $staff2->id, $staff3->id],
            $newRank->id,
            ['start_date' => '2024-01-01']
        );

        $this->assertEquals(3, $count);

        // Verify each staff has the new rank
        foreach ([$staff1, $staff2, $staff3] as $staff) {
            $staff->refresh();
            $this->assertTrue($staff->ranks->contains('id', $newRank->id));
        }
    }

    public function test_update_promotion_modifies_existing_promotion(): void
    {
        $staff = InstitutionPerson::factory()->create([
            'institution_id' => $this->institution->id,
        ]);
        $rank = Job::factory()->create(['institution_id' => $this->institution->id]);

        // Create initial promotion
        $staff->ranks()->attach($rank->id, [
            'start_date' => '2023-01-01',
            'remarks' => 'Original remarks',
        ]);

        $result = $this->service->updatePromotion($staff, $rank->id, [
            'start_date' => '2023-06-01',
            'remarks' => 'Updated remarks',
        ]);

        $this->assertEquals('Updated remarks', $result->remarks);
        $this->assertEquals('2023-06-01', $result->start_date->format('Y-m-d'));
    }

    public function test_delete_promotion_removes_rank_assignment(): void
    {
        $staff = InstitutionPerson::factory()->create([
            'institution_id' => $this->institution->id,
        ]);
        $rank = Job::factory()->create(['institution_id' => $this->institution->id]);

        $staff->ranks()->attach($rank->id, [
            'start_date' => '2023-01-01',
        ]);

        $this->service->deletePromotion($staff, $rank->id);

        $staff->refresh();
        $this->assertFalse($staff->ranks->contains('id', $rank->id));
    }

    public function test_get_eligible_for_promotion_returns_staff_with_3_year_tenure(): void
    {
        $rank = Job::factory()->create(['institution_id' => $this->institution->id]);

        // Staff eligible (more than 3 years in rank)
        $eligibleStaff = InstitutionPerson::factory()->create([
            'institution_id' => $this->institution->id,
        ]);
        $eligibleStaff->ranks()->attach($rank->id, [
            'start_date' => Carbon::now()->subYears(4),
            'end_date' => null,
        ]);
        $eligibleStaff->statuses()->create([
            'status' => 'A',
            'start_date' => Carbon::now()->subYears(4),
            'institution_id' => $this->institution->id,
        ]);

        // Staff not eligible (less than 3 years in rank)
        $notEligibleStaff = InstitutionPerson::factory()->create([
            'institution_id' => $this->institution->id,
        ]);
        $notEligibleStaff->ranks()->attach($rank->id, [
            'start_date' => Carbon::now()->subYear(),
            'end_date' => null,
        ]);
        $notEligibleStaff->statuses()->create([
            'status' => 'A',
            'start_date' => Carbon::now()->subYear(),
            'institution_id' => $this->institution->id,
        ]);

        $eligible = $this->service->getEligibleForPromotion($rank->id);

        $this->assertTrue($eligible->contains('id', $eligibleStaff->id));
        $this->assertFalse($eligible->contains('id', $notEligibleStaff->id));
    }

    public function test_get_promotion_history_returns_all_ranks(): void
    {
        $staff = InstitutionPerson::factory()->create([
            'institution_id' => $this->institution->id,
        ]);
        $rank1 = Job::factory()->create(['institution_id' => $this->institution->id, 'name' => 'Junior']);
        $rank2 = Job::factory()->create(['institution_id' => $this->institution->id, 'name' => 'Senior']);

        $staff->ranks()->attach($rank1->id, [
            'start_date' => '2020-01-01',
            'end_date' => '2022-12-31',
        ]);
        $staff->ranks()->attach($rank2->id, [
            'start_date' => '2023-01-01',
            'end_date' => null,
        ]);

        $history = $this->service->getPromotionHistory($staff);

        $this->assertCount(2, $history);
        $this->assertTrue($history->pluck('name')->contains('Junior'));
        $this->assertTrue($history->pluck('name')->contains('Senior'));
    }
}
