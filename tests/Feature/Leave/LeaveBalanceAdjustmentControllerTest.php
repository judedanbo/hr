<?php

namespace Tests\Feature\Leave;

use App\Models\InstitutionPerson;
use App\Models\LeaveBalanceAdjustment;
use App\Models\LeaveType;
use App\Models\LeaveYear;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeaveBalanceAdjustmentControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;

    protected User $guestUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->superAdmin = User::factory()->create(['password_change_at' => now()]);
        $this->superAdmin->assignRole('super-administrator');

        $this->guestUser = User::factory()->create(['password_change_at' => now()]);
    }

    public function test_index_requires_permission(): void
    {
        $this->actingAs($this->guestUser)->get(route('leave-balance-adjustment.index'))->assertForbidden();
    }

    public function test_index_displays_adjustments(): void
    {
        LeaveBalanceAdjustment::factory()->count(2)->create();

        $this->actingAs($this->superAdmin)
            ->get(route('leave-balance-adjustment.index'))
            ->assertStatus(200)
            ->assertInertia(fn ($page) => $page->component('LeaveBalanceAdjustment/Index')->has('adjustments.data', 2)->has('staffOptions'));
    }

    public function test_store_records_an_adjustment(): void
    {
        $staff = InstitutionPerson::factory()->create();
        $type = LeaveType::factory()->create();
        $year = LeaveYear::factory()->create();

        $this->actingAs($this->superAdmin)
            ->post(route('leave-balance-adjustment.store'), [
                'staff_id' => $staff->id,
                'leave_type_id' => $type->id,
                'leave_year_id' => $year->id,
                'days' => -3,
                'reason' => 'Carry-over correction',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('leave_balance_adjustments', [
            'staff_id' => $staff->id, 'days' => -3, 'adjusted_by' => $this->superAdmin->id,
        ]);
    }

    public function test_store_rejects_zero_days(): void
    {
        $staff = InstitutionPerson::factory()->create();
        $type = LeaveType::factory()->create();
        $year = LeaveYear::factory()->create();

        $this->actingAs($this->superAdmin)
            ->post(route('leave-balance-adjustment.store'), [
                'staff_id' => $staff->id, 'leave_type_id' => $type->id, 'leave_year_id' => $year->id,
                'days' => 0, 'reason' => 'x',
            ])
            ->assertSessionHasErrors('days');
    }

    public function test_delete_soft_deletes_an_adjustment(): void
    {
        $adjustment = LeaveBalanceAdjustment::factory()->create();

        $this->actingAs($this->superAdmin)
            ->delete(route('leave-balance-adjustment.delete', $adjustment))
            ->assertRedirect();

        $this->assertSoftDeleted('leave_balance_adjustments', ['id' => $adjustment->id]);
    }
}
