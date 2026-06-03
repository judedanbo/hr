<?php

namespace Tests\Feature\Appraisal;

use App\Enums\AppraisalStatusEnum;
use App\Models\AppraisalCycle;
use App\Models\InstitutionPerson;
use App\Models\Status;
use App\Models\Unit;
use App\Services\Appraisal\AppraisalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AppraisalServiceTest extends TestCase
{
    use RefreshDatabase;

    protected AppraisalService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(AppraisalService::class);
    }

    private function activeStaff(): InstitutionPerson
    {
        $staff = InstitutionPerson::factory()->create();
        Status::factory()->active()->create(['staff_id' => $staff->id, 'institution_id' => $staff->institution_id]);

        return $staff;
    }

    public function test_resolve_chain_routes_to_unit_head_then_ancestor(): void
    {
        $parent = Unit::factory()->department()->create();
        $child = Unit::factory()->create(['unit_id' => $parent->id]);

        $unitHead = $this->activeStaff();
        $deptHead = $this->activeStaff();
        $child->update(['head_staff_id' => $unitHead->id]);
        $parent->update(['head_staff_id' => $deptHead->id]);

        $staff = $this->activeStaff();

        $chain = $this->service->resolveApproverChain($staff, $child);

        $this->assertSame($unitHead->id, $chain['appraiser_id']);
        $this->assertSame($deptHead->id, $chain['reviewer_id']);
    }

    public function test_unit_head_escalates_to_parent(): void
    {
        $parent = Unit::factory()->department()->create();
        $child = Unit::factory()->create(['unit_id' => $parent->id]);

        $deptHead = $this->activeStaff();
        $parent->update(['head_staff_id' => $deptHead->id]);

        // The staff member IS the head of the child unit, so they cannot appraise themselves.
        $staff = $this->activeStaff();
        $child->update(['head_staff_id' => $staff->id]);

        $chain = $this->service->resolveApproverChain($staff, $child);

        $this->assertSame($deptHead->id, $chain['appraiser_id']);
        $this->assertNull($chain['reviewer_id']);
    }

    public function test_initiate_cycle_creates_appraisals_for_active_staff_only(): void
    {
        $cycle = AppraisalCycle::factory()->create();

        $unit = Unit::factory()->create();
        $head = $this->activeStaff();
        $unit->update(['head_staff_id' => $head->id]);

        $staff = $this->activeStaff();
        $staff->units()->attach($unit->id, ['start_date' => now()->subYear()]);

        // Separated staff (no active status) should be skipped.
        $separated = InstitutionPerson::factory()->create();
        Status::factory()->retired()->create(['staff_id' => $separated->id, 'institution_id' => $separated->institution_id]);

        $created = $this->service->initiateCycle($cycle);

        // head + staff are both active and get appraisals.
        $this->assertSame(2, $created);
        $this->assertDatabaseHas('appraisals', [
            'appraisal_cycle_id' => $cycle->id,
            'staff_id' => $staff->id,
            'appraiser_id' => $head->id,
            'unit_id' => $unit->id,
            'status' => AppraisalStatusEnum::DraftObjectives->value,
        ]);
        $this->assertDatabaseMissing('appraisals', ['staff_id' => $separated->id]);
    }

    public function test_initiate_cycle_is_idempotent(): void
    {
        $cycle = AppraisalCycle::factory()->create();
        $this->activeStaff();

        $first = $this->service->initiateCycle($cycle);
        $second = $this->service->initiateCycle($cycle);

        $this->assertSame(1, $first);
        $this->assertSame(0, $second);
        $this->assertDatabaseCount('appraisals', 1);
    }
}
