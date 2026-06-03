<?php

namespace Tests\Feature\Leave;

use App\Models\LeavePlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeavePlanAdminControllerTest extends TestCase
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
        $this->actingAs($this->guestUser)->get(route('leave-plans.index'))->assertForbidden();
    }

    public function test_index_lists_only_submitted_plans(): void
    {
        LeavePlan::factory()->submitted()->count(2)->create();
        LeavePlan::factory()->create(); // draft — excluded

        $this->actingAs($this->superAdmin)
            ->get(route('leave-plans.index'))
            ->assertStatus(200)
            ->assertInertia(fn ($page) => $page->component('LeavePlan/All')->has('plans.data', 2));
    }

    public function test_show_displays_a_plan(): void
    {
        $plan = LeavePlan::factory()->submitted()->create();

        $this->actingAs($this->superAdmin)
            ->get(route('leave-plans.show', $plan))
            ->assertStatus(200)
            ->assertInertia(fn ($page) => $page->component('LeavePlan/Show')->where('plan.id', $plan->id));
    }
}
