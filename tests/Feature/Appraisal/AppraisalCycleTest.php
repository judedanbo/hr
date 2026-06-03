<?php

namespace Tests\Feature\Appraisal;

use App\Models\AppraisalCycle;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AppraisalCycleTest extends TestCase
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

    /**
     * @return array<string, mixed>
     */
    protected function validData(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Appraisal Cycle 2026',
            'year' => 2026,
            'objective_window_start' => '2026-01-01',
            'objective_window_end' => '2026-01-31',
            'midyear_window_start' => '2026-06-01',
            'midyear_window_end' => '2026-06-30',
            'final_window_start' => '2026-12-01',
            'final_window_end' => '2026-12-31',
            'objectives_weight' => 70,
            'competencies_weight' => 30,
            'status' => 'draft',
        ], $overrides);
    }

    public function test_index_requires_authentication(): void
    {
        $this->get(route('appraisal-cycle.index'))->assertRedirect(route('login'));
    }

    public function test_index_requires_permission(): void
    {
        $this->actingAs($this->guestUser)->get(route('appraisal-cycle.index'))->assertForbidden();
    }

    public function test_index_displays_cycles(): void
    {
        AppraisalCycle::factory()->count(2)->create();

        $this->actingAs($this->superAdmin)
            ->get(route('appraisal-cycle.index'))
            ->assertStatus(200)
            ->assertInertia(fn ($page) => $page->component('AppraisalCycle/Index')->has('cycles.data', 2)->has('statuses'));
    }

    public function test_store_creates_cycle(): void
    {
        $this->actingAs($this->superAdmin)
            ->post(route('appraisal-cycle.store'), $this->validData())
            ->assertRedirect(route('appraisal-cycle.index'));

        $this->assertDatabaseHas('appraisal_cycles', ['name' => 'Appraisal Cycle 2026', 'year' => 2026]);
    }

    public function test_store_rejects_weights_not_summing_to_100(): void
    {
        $this->actingAs($this->superAdmin)
            ->post(route('appraisal-cycle.store'), $this->validData(['objectives_weight' => 60, 'competencies_weight' => 30]))
            ->assertSessionHasErrors('competencies_weight');

        $this->assertDatabaseCount('appraisal_cycles', 0);
    }

    public function test_store_requires_permission(): void
    {
        $this->actingAs($this->guestUser)
            ->post(route('appraisal-cycle.store'), $this->validData())
            ->assertForbidden();
    }

    public function test_update_modifies_cycle(): void
    {
        $cycle = AppraisalCycle::factory()->create();

        $this->actingAs($this->superAdmin)
            ->patch(route('appraisal-cycle.update', $cycle), $this->validData(['name' => 'Renamed Cycle']))
            ->assertRedirect(route('appraisal-cycle.index'));

        $this->assertDatabaseHas('appraisal_cycles', ['id' => $cycle->id, 'name' => 'Renamed Cycle']);
    }

    public function test_delete_soft_deletes_cycle(): void
    {
        $cycle = AppraisalCycle::factory()->create();

        $this->actingAs($this->superAdmin)
            ->delete(route('appraisal-cycle.delete', $cycle))
            ->assertRedirect();

        $this->assertSoftDeleted('appraisal_cycles', ['id' => $cycle->id]);
    }

    public function test_show_displays_cycle(): void
    {
        $cycle = AppraisalCycle::factory()->create();

        $this->actingAs($this->superAdmin)
            ->get(route('appraisal-cycle.show', $cycle))
            ->assertStatus(200)
            ->assertInertia(fn ($page) => $page->component('AppraisalCycle/Show')->where('cycle.id', $cycle->id));
    }
}
