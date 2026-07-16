<?php

namespace Tests\Unit;

use App\Models\ApprovalDelegation;
use App\Models\InstitutionPerson;
use App\Models\Unit;
use App\Services\ApproverResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApproverResolverTest extends TestCase
{
    use RefreshDatabase;

    private ApproverResolver $resolver;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resolver = new ApproverResolver;
    }

    private function assign(InstitutionPerson $staff, Unit $unit): void
    {
        $staff->units()->attach($unit->id, ['start_date' => now()->toDateString(), 'end_date' => null]);
    }

    public function test_resolves_the_head_of_the_current_unit(): void
    {
        $head = InstitutionPerson::factory()->create();
        $unit = Unit::factory()->create(['head_staff_id' => $head->id]);
        $staff = InstitutionPerson::factory()->create();
        $this->assign($staff, $unit);

        $this->assertSame($head->id, $this->resolver->resolve($staff)?->id);
    }

    public function test_walks_up_to_a_parent_units_head(): void
    {
        $head = InstitutionPerson::factory()->create();
        $parent = Unit::factory()->create(['head_staff_id' => $head->id]);
        $unit = Unit::factory()->create(['unit_id' => $parent->id, 'head_staff_id' => null]);
        $staff = InstitutionPerson::factory()->create();
        $this->assign($staff, $unit);

        $this->assertSame($head->id, $this->resolver->resolve($staff)?->id);
    }

    public function test_escalates_when_the_requester_is_their_own_units_head(): void
    {
        $topHead = InstitutionPerson::factory()->create();
        $parent = Unit::factory()->create(['head_staff_id' => $topHead->id]);
        $staff = InstitutionPerson::factory()->create();
        $unit = Unit::factory()->create(['unit_id' => $parent->id, 'head_staff_id' => $staff->id]);
        $this->assign($staff, $unit);

        $this->assertSame($topHead->id, $this->resolver->resolve($staff)?->id);
    }

    public function test_routes_to_an_active_delegate(): void
    {
        $head = InstitutionPerson::factory()->create();
        $delegate = InstitutionPerson::factory()->create();
        $unit = Unit::factory()->create(['head_staff_id' => $head->id]);
        $staff = InstitutionPerson::factory()->create();
        $this->assign($staff, $unit);

        ApprovalDelegation::factory()->active()->create([
            'delegator_id' => $head->id,
            'delegate_id' => $delegate->id,
        ]);

        $this->assertSame($delegate->id, $this->resolver->resolve($staff)?->id);
    }

    public function test_returns_null_when_no_head_exists(): void
    {
        $unit = Unit::factory()->create(['head_staff_id' => null]);
        $staff = InstitutionPerson::factory()->create();
        $this->assign($staff, $unit);

        $this->assertNull($this->resolver->resolve($staff));
    }
}
