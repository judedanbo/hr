<?php

namespace Tests\Feature\Leave;

use App\Models\LeaveEntitlement;
use App\Models\LeaveType;
use App\Models\LeaveYear;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeaveEntitlementControllerTest extends TestCase
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
        $this->actingAs($this->guestUser)->get(route('leave-entitlement.index'))->assertForbidden();
    }

    public function test_index_displays_entitlements_with_options(): void
    {
        LeaveEntitlement::factory()->count(2)->create();

        $this->actingAs($this->superAdmin)
            ->get(route('leave-entitlement.index'))
            ->assertStatus(200)
            ->assertInertia(fn ($page) => $page
                ->component('LeaveEntitlement/Index')
                ->has('entitlements.data', 2)
                ->has('leaveYears')
                ->has('leaveTypes')
                ->has('jobCategories'));
    }

    public function test_store_creates_a_default_entitlement(): void
    {
        $year = LeaveYear::factory()->create();
        $type = LeaveType::factory()->create();

        $this->actingAs($this->superAdmin)
            ->post(route('leave-entitlement.store'), [
                'leave_year_id' => $year->id,
                'leave_type_id' => $type->id,
                'job_category_id' => null,
                'days_allowed' => 21,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('leave_entitlements', [
            'leave_year_id' => $year->id,
            'leave_type_id' => $type->id,
            'job_category_id' => null,
            'days_allowed' => 21,
            'min_service_months' => 0,
        ]);
    }

    public function test_store_rejects_duplicate_year_type_category(): void
    {
        $year = LeaveYear::factory()->create();
        $type = LeaveType::factory()->create();
        LeaveEntitlement::factory()->create([
            'leave_year_id' => $year->id,
            'leave_type_id' => $type->id,
            'job_category_id' => null,
        ]);

        $this->actingAs($this->superAdmin)
            ->post(route('leave-entitlement.store'), [
                'leave_year_id' => $year->id,
                'leave_type_id' => $type->id,
                'job_category_id' => null,
                'days_allowed' => 15,
            ])
            ->assertSessionHasErrors('leave_type_id');
    }

    public function test_update_modifies_an_entitlement(): void
    {
        $entitlement = LeaveEntitlement::factory()->create(['days_allowed' => 10]);

        $this->actingAs($this->superAdmin)
            ->patch(route('leave-entitlement.update', $entitlement), [
                'leave_year_id' => $entitlement->leave_year_id,
                'leave_type_id' => $entitlement->leave_type_id,
                'job_category_id' => null,
                'days_allowed' => 25,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('leave_entitlements', ['id' => $entitlement->id, 'days_allowed' => 25]);
    }

    public function test_delete_soft_deletes_an_entitlement(): void
    {
        $entitlement = LeaveEntitlement::factory()->create();

        $this->actingAs($this->superAdmin)
            ->delete(route('leave-entitlement.delete', $entitlement))
            ->assertRedirect();

        $this->assertSoftDeleted('leave_entitlements', ['id' => $entitlement->id]);
    }
}
