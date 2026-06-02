<?php

namespace Tests\Feature\Leave;

use App\Models\ApprovalDelegation;
use App\Models\InstitutionPerson;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeaveDelegationControllerTest extends TestCase
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
        $this->actingAs($this->guestUser)->get(route('leave-delegation.index'))->assertForbidden();
    }

    public function test_index_displays_delegations(): void
    {
        ApprovalDelegation::factory()->count(2)->create();

        $this->actingAs($this->superAdmin)
            ->get(route('leave-delegation.index'))
            ->assertStatus(200)
            ->assertInertia(fn ($page) => $page->component('LeaveDelegation/Index')->has('delegations.data', 2)->has('staffOptions'));
    }

    public function test_store_creates_a_delegation(): void
    {
        $delegator = InstitutionPerson::factory()->create();
        $delegate = InstitutionPerson::factory()->create();

        $this->actingAs($this->superAdmin)
            ->post(route('leave-delegation.store'), [
                'delegator_id' => $delegator->id,
                'delegate_id' => $delegate->id,
                'start_date' => '2030-01-01',
                'end_date' => '2030-01-31',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('approval_delegations', ['delegator_id' => $delegator->id, 'delegate_id' => $delegate->id]);
    }

    public function test_store_rejects_self_delegation(): void
    {
        $staff = InstitutionPerson::factory()->create();

        $this->actingAs($this->superAdmin)
            ->post(route('leave-delegation.store'), [
                'delegator_id' => $staff->id,
                'delegate_id' => $staff->id,
                'start_date' => '2030-01-01',
                'end_date' => '2030-01-31',
            ])
            ->assertSessionHasErrors('delegate_id');
    }

    public function test_delete_soft_deletes_a_delegation(): void
    {
        $delegation = ApprovalDelegation::factory()->create();

        $this->actingAs($this->superAdmin)
            ->delete(route('leave-delegation.delete', $delegation))
            ->assertRedirect();

        $this->assertSoftDeleted('approval_delegations', ['id' => $delegation->id]);
    }
}
